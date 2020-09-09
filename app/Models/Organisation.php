<?php

namespace App\Models;

use App\Http\Requests\Organisation\UpdateRequest as UpdateOrganisationRequest;
use App\Models\Mutators\OrganisationMutators;
use App\Models\Relationships\OrganisationRelationships;
use App\Models\Scopes\OrganisationScopes;
use App\Rules\FileIsMimeType;
use App\UpdateRequest\AppliesUpdateRequests;
use App\UpdateRequest\UpdateRequests;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

class Organisation extends Model implements AppliesUpdateRequests
{
    use OrganisationMutators;
    use OrganisationRelationships;
    use OrganisationScopes;
    use UpdateRequests;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_hidden' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Check if the update request is valid.
     *
     * @param \App\Models\UpdateRequest $updateRequest
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validateUpdateRequest(UpdateRequest $updateRequest): Validator
    {
        $rules = (new UpdateOrganisationRequest())
            ->setUserResolver(function () use ($updateRequest) {
                return $updateRequest->user;
            })
            ->merge(['organisation' => $this])
            ->rules();

        // Remove the pending assignment rule since the file is now uploaded.
        $rules['logo_file_id'] = [
            'nullable',
            'exists:files,id',
            new FileIsMimeType(File::MIME_TYPE_PNG),
        ];

        return ValidatorFacade::make($updateRequest->data, $rules);
    }

    /**
     * Apply the update request.
     *
     * @param \App\Models\UpdateRequest $updateRequest
     * @return \App\Models\UpdateRequest
     */
    public function applyUpdateRequest(UpdateRequest $updateRequest): UpdateRequest
    {
        $data = $updateRequest->data;

        $this->update([
            'slug' => Arr::get($data, 'slug', $this->slug),
            'name' => Arr::get($data, 'name', $this->name),
            'description' => sanitize_markdown(
                Arr::get($data, 'description', $this->description)
            ),
            'url' => Arr::get($data, 'url', $this->url),
            'email' => Arr::get($data, 'email', $this->email),
            'phone' => Arr::get($data, 'phone', $this->phone),
            'address_line_1' => Arr::get($data, 'address_line_1', $this->address_line_1),
            'address_line_2' => Arr::get($data, 'address_line_2', $this->address_line_2),
            'address_line_3' => Arr::get($data, 'address_line_3', $this->address_line_3),
            'city' => Arr::get($data, 'city', $this->city),
            'county' => Arr::get($data, 'county', $this->county),
            'postcode' => Arr::get($data, 'postcode', $this->postcode),
            'country' => Arr::get($data, 'country', $this->country),
            'is_hidden' => Arr::get($data, 'is_hidden', $this->is_hidden),
            'logo_file_id' => Arr::get($data, 'logo_file_id', $this->logo_file_id),
        ]);

        return $updateRequest;
    }

    /**
     * @return \App\Models\Organisation
     */
    public function touchServices(): Organisation
    {
        $this->services()->get()->each->save();

        return $this;
    }

    /**
     * @return bool
     */
    public function hasLogo(): bool
    {
        return $this->logo_file_id !== null;
    }

    /**
     * @param int|null $maxDimension
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException|\InvalidArgumentException
     * @return \App\Models\File|\Illuminate\Http\Response|\Illuminate\Contracts\Support\Responsable
     */
    public static function placeholderLogo(int $maxDimension = null)
    {
        if ($maxDimension !== null) {
            return File::resizedPlaceholder($maxDimension, File::META_PLACEHOLDER_FOR_ORGANISATION);
        }

        return response()->make(
            Storage::disk('local')->get('/placeholders/organisation.png'),
            Response::HTTP_OK,
            ['Content-Type' => File::MIME_TYPE_PNG]
        );
    }
}
