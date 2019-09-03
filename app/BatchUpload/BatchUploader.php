<?php

namespace App\BatchUpload;

use App\Models\Collection;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\RegularOpeningHour;
use App\Models\Service;
use App\Models\ServiceLocation;
use App\Models\Taxonomy;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BatchUploader
{
    /**
     * @var \PhpOffice\PhpSpreadsheet\Reader\Xlsx
     */
    protected $reader;

    /**
     * BatchUploader constructor.
     */
    public function __construct()
    {
        $this->reader = new XlsxReader();
        $this->reader->setReadDataOnly(true);
    }

    /**
     * Validates and then uploads the file.
     *
     * @param string $filePath
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \Exception
     */
    public function upload(string $filePath)
    {
        // Load the spreadsheet.
        $spreadsheet = $this->reader->load($filePath);

        // Load each worksheet.
        $organisationsSheet = $spreadsheet->getSheetByName('Organisations');
        $servicesSheet = $spreadsheet->getSheetByName('Services');
        $topicsSheet = $spreadsheet->getSheetByName('Topics');
        $snomedSheet = $spreadsheet->getSheetByName('SNOMED');

        // Convert the worksheets to associative arrays.
        $organisations = $this->toArray($organisationsSheet);
        $services = $this->toArray($servicesSheet);
        $topics = $this->toArray($topicsSheet);
        $snomedCodes = $this->toArray($snomedSheet);

        // Process.
        DB::transaction(
            function () use (
                &$organisations,
                &$services,
                &$topics,
                &$snomedCodes
            ) {
                Service::disableSearchSyncing();

                $this->truncateTables();

                // Process topics.
                $this->processTopics($topics);

                // Process SNOMED codes.
                $this->processSnomedCodes($topics, $snomedCodes);

                // Process organisations.
                $this->processOrganisations($organisations);

                // Process services.
                $this->processServices($organisations, $topics, $services);

                Service::enableSearchSyncing();
                Artisan::call('tlr:reindex-elasticsearch');
            }
        );
    }

    /**
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return array
     */
    protected function toArray(Worksheet $sheet): array
    {
        $array = $sheet->toArray();
        $headings = $array[0];
        $contents = [];

        foreach ($array as $rowIndex => $rowValue) {
            if ($rowIndex === 0) {
                continue;
            }

            $resource = [];

            foreach ($headings as $column => $heading) {
                $resource[$heading] = $rowValue[$column];
            }

            $contents[] = $resource;
        }

        return $contents;
    }

    /**
     * Truncate tables.
     */
    protected function truncateTables()
    {
        // Delete topic taxonomies.
        Taxonomy::category()->children()->each(
            function (Taxonomy $topic) {
                $topic->delete();
            }
        );

        // Delete SNOMED collections.
        Collection::query()->snomed()->get()->each(
            function (Collection $snomedCode) {
                $snomedCode->delete();
            }
        );

        // Delete organisations.
        Organisation::all()->each(function (Organisation $organisation) {
            $organisation->delete();
        });
    }

    /**
     * @param array $topics
     */
    protected function processTopics(array &$topics)
    {
        $topLevelTaxonomy = Taxonomy::category();

        // Give each topic a UUID.
        foreach ($topics as &$topic) {
            $topic['_id'] = Str::uuid()->toString();
        }

        // Map each topic's parent_id to a UUID.
        foreach ($topics as &$topic) {
            // Set parent ID of top level topics to Category taxonomy.
            if ($topic['parent_id'] === null) {
                $topic['_parent_id'] = $topLevelTaxonomy->id;
                continue;
            }

            $topic['_parent_id'] = Arr::first(
                $topics,
                function (array $parentTopic) use ($topic) {
                    return $topic['parent_id'] === $parentTopic['id'];
                }
            )['_id'];
        }

        // Persist the topics.
        foreach ($topics as &$topic) {
            Taxonomy::create([
                'id' => $topic['_id'],
                'parent_id' => $topic['_parent_id'],
                'name' => $topic['name'],
                'order' => 1,
            ]);
        }
    }

    /**
     * @param array $topics
     * @param array $snomedCodes
     */
    protected function processSnomedCodes(array &$topics, array &$snomedCodes)
    {
        // Map each SNOMED code's topic IDs to their UUID.
        foreach ($snomedCodes as $row => &$snomedCode) {
            $topicIds = explode(';', $snomedCode['topic_ids']);

            $linkedTopics = array_filter(
                $topics,
                function (array $topic) use ($topicIds): bool {
                    return in_array((string)$topic['id'], $topicIds);
                }
            );

            $snomedCode['_topic_ids'] = Arr::pluck(
                $linkedTopics,
                '_id'
            );
        }

        // Persist the SNOMED codes.
        foreach ($snomedCodes as &$snomedCode) {
            $snomedCode['_model'] = Collection::create([
                'type' => Collection::TYPE_SNOMED,
                'name' => $snomedCode['code'],
                'meta' => [
                    'name' => $snomedCode['name'] ?: null,
                ],
                'order' => 1,
            ]);
        }

        // Persist the SNOMED topic links.
        foreach ($snomedCodes as &$snomedCode) {
            $snomedCode['_model']->syncCollectionTaxonomies(
                Taxonomy::query()->whereIn(
                    'id',
                    $snomedCode['_topic_ids']
                )->get()
            );
        }
    }

    /**
     * @param array $organisations
     */
    protected function processOrganisations(array &$organisations)
    {
        // Give each organisation a UUID.
        foreach ($organisations as &$organisation) {
            $organisation['_id'] = Str::uuid()->toString();
        }

        // Persist the organisations.
        foreach ($organisations as &$organisation) {
            Organisation::create([
                'id' => $organisation['_id'],
                'slug' => Str::slug(
                    implode(' ', preg_split(
                        '/(?=[A-Z])/',
                        $organisation['slug']
                    ))
                ),
                'name' => $organisation['name'],
                'description' => $organisation['description'] ?: 'No description.',
                'url' => $organisation['url'] ?: 'https://example.com/no-url-provided',
                'email' => $organisation['email'] ?: 'no-url-provided@example.com',
                'phone' => $organisation['phone'] ?: '00000000000',
            ]);
        }
    }

    /**
     * @param array $organisations
     * @param array $topics
     * @param array $services
     */
    protected function processServices(
        array &$organisations,
        array &$topics,
        array &$services
    ) {
        // Map each service's organisation_id to a UUID.
        foreach ($services as &$service) {
            $service['_organisation_id'] = Arr::first(
                $organisations,
                function (array $organisation) use ($service) {
                    return $service['organisation_id'] === $organisation['id'];
                }
            )['_id'] ?? null;
        }

        // Filter out services that don't have an organisation ID.
        $services = array_filter($services, function (array $service): bool {
            return $service['_organisation_id'] !== null;
        });

        // Map each service's topic IDs to their UUID.
        foreach ($services as $row => &$service) {
            $topicIds = explode(';', $service['Topics']);

            $linkedTopics = array_filter(
                $topics,
                function (array $topic) use ($topicIds): bool {
                    return in_array((string)$topic['id'], $topicIds);
                }
            );

            $service['_topic_ids'] = Arr::pluck(
                $linkedTopics,
                '_id'
            );
        }

        // Persist the services.
        foreach ($services as &$service) {
            try {
                $service['_model'] = Service::create([
                    'organisation_id' => $service['_organisation_id'],
                    'slug' => Str::slug(
                        implode(' ', preg_split(
                            '/(?=[A-Z])/',
                            $service['slug']
                        ))
                    ) . '-' . mt_rand(1, 999),
                    'name' => Str::limit($service['name'], 255, ''),
                    'type' => Service::TYPE_SERVICE,
                    'status' => Service::STATUS_ACTIVE,
                    'intro' => Str::limit($service['intro'], 255, '') ?: 'No intro provided.',
                    'description' => sanitize_markdown(
                        $service['description'] ?: 'No description provided.'
                    ),
                    'wait_time' => null,
                    'is_free' => $service['is_free'] === 'Yes',
                    'fees_text' => Str::limit($service['fees_text'], 255, '') ?: null,
                    'fees_url' => null,
                    'testimonial' => null,
                    'video_embed' => null,
                    'url' => $service['url'] ?: 'https://example.com/no-url-provided',
                    'contact_name' => null,
                    'contact_phone' => $service['contact_phone'] ?: null,
                    'contact_email' => $service['contact_email'] ?: null,
                    'show_referral_disclaimer' => false,
                    'referral_method' => Service::REFERRAL_METHOD_NONE,
                    'referral_button_text' => null,
                    'referral_email' => null,
                    'referral_url' => null,
                    'logo_file_id' => null,
                    'last_modified_at' => Date::now(),
                ]);

                $service['_model']->serviceCriterion()->create([
                    'age_group' => null,
                    'disability' => null,
                    'employment' => null,
                    'gender' => null,
                    'housing' => null,
                    'income' => null,
                    'language' => null,
                    'other' => null,
                ]);

                $service['_model']->syncServiceTaxonomies(
                    Taxonomy::query()->whereIn(
                        'id',
                        $service['_topic_ids']
                    )->get()
                );

                if (
                    (string)$service['address_line_1'] !== ''
                    && (string)$service['city'] !== ''
                    && (string)$service['county'] !== ''
                    && (string)$service['postcode'] !== ''
                    && (string)$service['country'] !== ''
                ) {
                    $location = new Location([
                        'address_line_1' => $service['address_line_1'],
                        'address_line_2' => null,
                        'address_line_3' => null,
                        'city' => $service['city'],
                        'county' => $service['county'],
                        'postcode' => $service['postcode'],
                        'country' => $service['country'],
                        'accessibility_info' => null,
                        'has_wheelchair_access' => false,
                        'has_induction_loop' => false,
                        'image_file_id' => null,
                    ]);
                    $location->updateCoordinate()->save();

                    $serviceLocation = ServiceLocation::create([
                        'service_id' => $service['_model']->id,
                        'location_id' => $location->id,
                        'name' => null,
                        'image_file_id' => null,
                    ]);

                    if ($service['open_monday'] === 'yes') {
                        $serviceLocation->regularOpeningHours()->create([
                            'frequency' => RegularOpeningHour::FREQUENCY_WEEKLY,
                            'weekday' => RegularOpeningHour::WEEKDAY_MONDAY,
                            'day_of_month' => null,
                            'occurrence_of_month' => null,
                            'starts_at' => null,
                            'opens_at' => '00:00:00',
                            'closes_at' => '23:59:59',
                        ]);
                    }

                    if ($service['open_tuesday'] === 'yes') {
                        $serviceLocation->regularOpeningHours()->create([
                            'frequency' => RegularOpeningHour::FREQUENCY_WEEKLY,
                            'weekday' => RegularOpeningHour::WEEKDAY_TUESDAY,
                            'day_of_month' => null,
                            'occurrence_of_month' => null,
                            'starts_at' => null,
                            'opens_at' => '00:00:00',
                            'closes_at' => '23:59:59',
                        ]);
                    }

                    if ($service['open_wednesday'] === 'yes') {
                        $serviceLocation->regularOpeningHours()->create([
                            'frequency' => RegularOpeningHour::FREQUENCY_WEEKLY,
                            'weekday' => RegularOpeningHour::WEEKDAY_WEDNESDAY,
                            'day_of_month' => null,
                            'occurrence_of_month' => null,
                            'starts_at' => null,
                            'opens_at' => '00:00:00',
                            'closes_at' => '23:59:59',
                        ]);
                    }

                    if ($service['open_thursday'] === 'yes') {
                        $serviceLocation->regularOpeningHours()->create([
                            'frequency' => RegularOpeningHour::FREQUENCY_WEEKLY,
                            'weekday' => RegularOpeningHour::WEEKDAY_THURSDAY,
                            'day_of_month' => null,
                            'occurrence_of_month' => null,
                            'starts_at' => null,
                            'opens_at' => '00:00:00',
                            'closes_at' => '23:59:59',
                        ]);
                    }

                    if ($service['open_friday'] === 'yes') {
                        $serviceLocation->regularOpeningHours()->create([
                            'frequency' => RegularOpeningHour::FREQUENCY_WEEKLY,
                            'weekday' => RegularOpeningHour::WEEKDAY_FRIDAY,
                            'day_of_month' => null,
                            'occurrence_of_month' => null,
                            'starts_at' => null,
                            'opens_at' => '00:00:00',
                            'closes_at' => '23:59:59',
                        ]);
                    }

                    if ($service['open_saturday'] === 'yes') {
                        $serviceLocation->regularOpeningHours()->create([
                            'frequency' => RegularOpeningHour::FREQUENCY_WEEKLY,
                            'weekday' => RegularOpeningHour::WEEKDAY_SATURDAY,
                            'day_of_month' => null,
                            'occurrence_of_month' => null,
                            'starts_at' => null,
                            'opens_at' => '00:00:00',
                            'closes_at' => '23:59:59',
                        ]);
                    }

                    if ($service['open_sunday'] === 'yes') {
                        $serviceLocation->regularOpeningHours()->create([
                            'frequency' => RegularOpeningHour::FREQUENCY_WEEKLY,
                            'weekday' => RegularOpeningHour::WEEKDAY_SUNDAY,
                            'day_of_month' => null,
                            'occurrence_of_month' => null,
                            'starts_at' => null,
                            'opens_at' => '00:00:00',
                            'closes_at' => '23:59:59',
                        ]);
                    }
                }
            } catch (\Exception $exception) {
                logger()->error($exception);
            }
        }
    }
}
