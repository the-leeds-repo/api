<?php

namespace App\Models;

use App\Models\Mutators\ReportMutators;
use App\Models\Relationships\ReportRelationships;
use App\Models\Scopes\ReportScopes;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

class Report extends Model
{
    use ReportMutators;
    use ReportRelationships;
    use ReportScopes;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Created a report record and a file record.
     * Then delegates the physical file creation to a `generateReportName` method.
     *
     * @param \App\Models\ReportType $type
     * @param \Carbon\CarbonImmutable|null $startsAt
     * @param \Carbon\CarbonImmutable|null $endsAt
     * @throws \Exception
     * @return \App\Models\Report
     */
    public static function generate(ReportType $type, CarbonImmutable $startsAt = null, CarbonImmutable $endsAt = null): self
    {
        // Generate the file name.
        $filename = sprintf(
            '%s_%s_%s.csv',
            Date::now()->format('Y-m-d_H-i'),
            Str::slug(config('app.name')),
            Str::slug($type->name)
        );

        // Create the file record.
        $file = File::create([
            'filename' => $filename,
            'mime_type' => 'text/csv',
            'is_private' => true,
        ]);

        // Create the report record.
        $report = static::create([
            'report_type_id' => $type->id,
            'file_id' => $file->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        // Get the name for the report generation method.
        $methodName = 'generate' . ucfirst(Str::camel($type->name));

        // Throw exception if the report type does not have a generate method.
        if (!method_exists($report, $methodName)) {
            throw new Exception("The report type [{$type->name}] does not have a corresponding generate method");
        }

        return $report->$methodName($startsAt, $endsAt);
    }

    /**
     * @return \App\Models\Report
     */
    public function generateUsersExport(): self
    {
        $headings = [
            'User Reference ID',
            'User First Name',
            'User Last Name',
            'Email address',
            'Highest Permission Level',
            'Organisation/Service Permission Levels',
            'Organisation/Service IDs',
        ];

        $data = [$headings];

        User::query()
            ->with('userRoles.role', 'organisations', 'services')
            ->chunk(200, function (Collection $users) use (&$data) {
                // Loop through each user in the chunk.
                $users->each(function (User $user) use (&$data) {
                    // Compile the highest roles for a service/organisation.
                    $highestRole = $user->highestRole();

                    if (in_array($highestRole->name ?? null, [Role::NAME_SUPER_ADMIN, Role::NAME_GLOBAL_ADMIN])) {
                        // If the highest role is super admin or global admin.
                        $allPermissions = [];
                        $allIds = [];
                    } else {
                        // If the highest role is anything else.
                        $allPermissions = [];
                        $allIds = [];

                        // Append the organisation details.
                        $user->organisations
                            ->each(function (Organisation $organisation) use (&$allPermissions, &$allIds) {
                                $allPermissions[] = Role::NAME_ORGANISATION_ADMIN;
                                $allIds[] = $organisation->id;
                            });

                        // Append the service details.
                        $user->services
                            ->reject(function (Service $service) use ($allIds) {
                                return in_array($service->organisation_id, $allIds);
                            })
                            ->each(function (Service $service) use ($user, &$allPermissions, &$allIds) {
                                $allPermissions[] = $user->hasRoleCached(Role::serviceAdmin(), $service)
                                    ? Role::NAME_SERVICE_ADMIN
                                    : Role::NAME_SERVICE_WORKER;
                                $allIds[] = $service->id;
                            });
                    }

                    // Append a row to the data array.
                    $data[] = [
                        $user->id,
                        $user->first_name,
                        $user->last_name,
                        $user->email,
                        $highestRole->name ?? null,
                        implode(',', $allPermissions),
                        implode(',', $allIds),
                    ];
                });
            });

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }

    /**
     * @return \App\Models\Report
     */
    public function generateServicesExport(): self
    {
        $headings = [
            'Organisation',
            'Org Reference ID',
            'Org Email',
            'Org Phone',
            'Service Reference ID',
            'Service Name',
            'Service Web Address',
            'Service Contact Name',
            'Last Updated',
            'Referral Type',
            'Referral Contact',
            'Status',
            'Locations Delivered At',
            'Date Created',
            'Freshness',
            'Taxonomy Topics',
        ];

        $data = [$headings];

        Service::query()
            ->with('organisation', 'serviceLocations.location', 'taxonomies')
            ->chunk(200, function (Collection $services) use (&$data) {
                // Loop through each service in the chunk.
                $services->each(function (Service $service) use (&$data) {
                    // Append a row to the data array.
                    $data[] = [
                        $service->organisation->name,
                        $service->organisation->id,
                        $service->organisation->email,
                        $service->organisation->phone,
                        $service->id,
                        $service->name,
                        $service->url,
                        $service->contact_name,
                        $service->updated_at->format(CarbonImmutable::ISO8601),
                        $service->referral_method,
                        $service->referral_email,
                        $service->status,
                        $service->serviceLocations->map(function (ServiceLocation $serviceLocation): string {
                            return $serviceLocation->location->full_address;
                        })->implode(';'),
                        optional($service->created_at)->format(CarbonImmutable::ISO8601),
                        $service->freshness(),
                        $service->taxonomies->map(function (Taxonomy $taxonomy): string {
                            return $taxonomy->name;
                        })->implode(';'),
                    ];
                });
            });

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }

    /**
     * @return \App\Models\Report
     */
    public function generateOrganisationsExport(): self
    {
        $headings = [
            'Organisation Reference ID',
            'Organisation Name',
            'Number of Services',
            'Organisation Email',
            'Organisation Phone',
            'Organisation URL',
            'Number of Accounts Attributed',
            'Hide Organisation from Public View',
            'CiviCRM ID',
            'Sync with CiviCRM Enabled',
        ];

        $data = [$headings];

        Organisation::query()
            ->withCount('services', 'nonAdminUsers')
            ->chunk(200, function (Collection $organisations) use (&$data) {
                // Loop through each service in the chunk.
                $organisations->each(function (Organisation $organisation) use (&$data) {
                    // Append a row to the data array.
                    $data[] = [
                        $organisation->id,
                        $organisation->name,
                        $organisation->services_count,
                        $organisation->email,
                        $organisation->phone,
                        $organisation->url,
                        $organisation->non_admin_users_count,
                        (int)$organisation->is_hidden,
                        $organisation->civi_id,
                        (int)$organisation->civi_sync_enabled,
                    ];
                });
            });

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }

    /**
     * @return \App\Models\Report
     */
    public function generateLocationsExport(): self
    {
        $headings = [
            'Address Line 1',
            'Address Line 2',
            'Address Line 3',
            'City',
            'County',
            'Postcode',
            'Country',
            'Number of Services Delivered at The Location',
        ];

        $data = [$headings];

        Location::query()
            ->withCount('services')
            ->chunk(200, function (Collection $locations) use (&$data) {
                // Loop through each location in the chunk.
                $locations->each(function (Location $location) use (&$data) {
                    // Append a row to the data array.
                    $data[] = [
                        $location->address_line_1,
                        $location->address_line_2,
                        $location->address_line_3,
                        $location->city,
                        $location->county,
                        $location->postcode,
                        $location->country,
                        $location->services_count,
                    ];
                });
            });

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }

    /**
     * @param \Carbon\CarbonImmutable|null $startsAt
     * @param \Carbon\CarbonImmutable|null $endsAt
     * @return \App\Models\Report
     */
    public function generateReferralsExport(CarbonImmutable $startsAt = null, CarbonImmutable $endsAt = null): self
    {
        // Update the date range fields if passed.
        if ($startsAt && $endsAt) {
            $this->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
        }

        $headings = [
            'Referred to Organisation ID',
            'Referred to Organisation',
            'Referred to Service ID',
            'Referred to Service Name',
            'Date Made',
            'Date Complete',
            'Self/Champion',
            'Refer from organisation',
            'Date Consent Provided',
        ];

        $data = [$headings];

        Referral::query()
            ->with('service.organisation', 'latestCompletedStatusUpdate', 'organisationTaxonomy')
            ->when($startsAt && $endsAt, function (Builder $query) use ($startsAt, $endsAt) {
                // When date range provided, filter referrals which were created between the date range.
                $query->whereBetween(table(Referral::class, 'created_at'), [$startsAt, $endsAt]);
            })
            ->chunk(200, function (Collection $referrals) use (&$data) {
                // Loop through each referral in the chunk.
                $referrals->each(function (Referral $referral) use (&$data) {
                    // Append a row to the data array.
                    $data[] = [
                        $referral->service->organisation->id,
                        $referral->service->organisation->name,
                        $referral->service->id,
                        $referral->service->name,
                        optional($referral->created_at)->format(CarbonImmutable::ISO8601),
                        $referral->isCompleted()
                            ? $referral->latestCompletedStatusUpdate->created_at->format(CarbonImmutable::ISO8601)
                            : '',
                        $referral->isSelfReferral() ? 'Self' : 'Champion',
                        $referral->isSelfReferral() ? null : $referral->organisationName(),
                        optional($referral->referral_consented_at)->format(CarbonImmutable::ISO8601),
                    ];
                });
            });

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }

    /**
     * @param \Carbon\CarbonImmutable|null $startsAt
     * @param \Carbon\CarbonImmutable|null $endsAt
     * @return \App\Models\Report
     */
    public function generateFeedbackExport(CarbonImmutable $startsAt = null, CarbonImmutable $endsAt = null): self
    {
        // Update the date range fields if passed.
        if ($startsAt && $endsAt) {
            $this->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
        }

        $headings = [
            'Date Submitted',
            'Feedback Content',
            'Page URL',
        ];

        $data = [$headings];

        PageFeedback::query()
            ->when($startsAt && $endsAt, function (Builder $query) use ($startsAt, $endsAt) {
                // When date range provided, filter page feedback which were created between the date range.
                $query->whereBetween(table(PageFeedback::class, 'created_at'), [$startsAt, $endsAt]);
            })
            ->chunk(200, function (Collection $pageFeedbacks) use (&$data) {
                // Loop through each page feedback in the chunk.
                $pageFeedbacks->each(function (PageFeedback $pageFeedback) use (&$data) {
                    // Append a row to the data array.
                    $data[] = [
                        optional($pageFeedback->created_at)->toDateString(),
                        $pageFeedback->feedback,
                        $pageFeedback->url,
                    ];
                });
            });

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }

    /**
     * @param \Carbon\CarbonImmutable|null $startsAt
     * @param \Carbon\CarbonImmutable|null $endsAt
     * @return \App\Models\Report
     */
    public function generateAuditLogsExport(CarbonImmutable $startsAt = null, CarbonImmutable $endsAt = null): self
    {
        // Update the date range fields if passed.
        if ($startsAt && $endsAt) {
            $this->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
        }

        $headings = [
            'Action',
            'Description',
            'User',
            'Date/Time',
            'IP Address',
            'User Agent',
        ];

        $data = [$headings];

        Audit::query()
            ->with('user')
            ->when($startsAt && $endsAt, function (Builder $query) use ($startsAt, $endsAt) {
                // When date range provided, filter page feedback which were created between the date range.
                $query->whereBetween(table(Audit::class, 'created_at'), [$startsAt, $endsAt]);
            })
            ->chunk(200, function (Collection $audits) use (&$data) {
                // Loop through each audit in the chunk.
                $audits->each(function (Audit $audit) use (&$data) {
                    // Append a row to the data array.
                    $data[] = [
                        $audit->action,
                        $audit->description,
                        optional($audit->user)->full_name,
                        optional($audit->created_at)->format(CarbonImmutable::ISO8601),
                        $audit->ip_address,
                        $audit->user_agent,
                    ];
                });
            });

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }

    /**
     * @param \Carbon\CarbonImmutable|null $startsAt
     * @param \Carbon\CarbonImmutable|null $endsAt
     * @return \App\Models\Report
     */
    public function generateSearchHistoriesExport(CarbonImmutable $startsAt = null, CarbonImmutable $endsAt = null): self
    {
        // Update the date range fields if passed.
        if ($startsAt && $endsAt) {
            $this->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
        }

        $headings = [
            'Date made',
            'Search Text',
            'Number Services Returned',
            'Coordinates (Latitude,Longitude)',
        ];

        $data = [$headings];

        SearchHistory::query()
            ->withFilledQuery()
            ->when($startsAt && $endsAt, function (Builder $query) use ($startsAt, $endsAt) {
                // When date range provided, filter search history which were created between the date range.
                $query->whereBetween(table(SearchHistory::class, 'created_at'), [$startsAt, $endsAt]);
            })
            ->chunk(200, function (Collection $searchHistories) use (&$data) {
                // Loop through each search history in the chunk.
                $searchHistories->each(function (SearchHistory $searchHistory) use (&$data) {
                    $query = Arr::dot($searchHistory->query);

                    $searchQuery = $query['query.bool.must.bool.should.0.match.name.query'] ?? null;
                    $lat = $query['sort.0._geo_distance.service_locations.location.lat'] ?? null;
                    $lon = $query['sort.0._geo_distance.service_locations.location.lon'] ?? null;
                    $coordinate = (!$lat !== null && $lon !== null) ? implode(',', [$lat, $lon]) : null;

                    // Append a row to the data array.
                    $data[] = [
                        optional($searchHistory->created_at)->toDateString(),
                        $searchQuery,
                        $searchHistory->count,
                        $coordinate,
                    ];
                });
            });

        // Upload the report.
        $this->file->upload(array_to_csv($data));

        return $this;
    }
}
