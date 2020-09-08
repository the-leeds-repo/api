<?php

namespace App\Http\Requests\Resource;

use App\Http\Requests\HasMissingValues;
use App\Models\Resource;
use App\Rules\IsOrganisationAdmin;
use App\Rules\MarkdownMaxLength;
use App\Rules\MarkdownMinLength;
use App\Rules\Slug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    use HasMissingValues;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user('api')->isOrganisationAdmin($this->resource->organisation);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'organisation_id' => [
                'exists:organisations,id',
                new IsOrganisationAdmin($this->user('api')),
            ],
            'slug' => [
                'string',
                'min:1',
                'max:255',
                Rule::unique(table(Resource::class), 'slug')
                    ->ignoreModel($this->resource),
                new Slug(),
            ],
            'name' => [
                'string',
                'min:1',
                'max:255',
            ],
            'description' => [
                'string',
                new MarkdownMinLength(1),
                new MarkdownMaxLength(1600),
            ],
            'url' => ['url', 'max:500'],
            'license' => ['nullable', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'category_taxonomies' => ['array'],
            'category_taxonomies.*' => ['exists:taxonomies,id'],
            'published_at' => ['nullable', 'string', 'date'],
            'last_modified_at' => ['nullable', 'string', 'date'],
        ];
    }

    /**
     * Check if the user requested only a preview of the update request.
     *
     * @return bool
     */
    public function isPreview(): bool
    {
        return $this->preview === true;
    }
}
