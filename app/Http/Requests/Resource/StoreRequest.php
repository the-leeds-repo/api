<?php

namespace App\Http\Requests\Resource;

use App\Models\Resource;
use App\Rules\IsOrganisationAdmin;
use App\Rules\MarkdownMaxLength;
use App\Rules\MarkdownMinLength;
use App\Rules\Slug;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->isOrganisationAdmin();
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
                'required',
                'exists:organisations,id',
                new IsOrganisationAdmin($this->user()),
            ],
            'slug' => [
                'required',
                'string',
                'min:1',
                'max:255',
                'unique:' . table(Resource::class) . ',slug',
                new Slug(),
            ],
            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
            ],
            'description' => [
                'required',
                'string',
                new MarkdownMinLength(1),
                new MarkdownMaxLength(1600),
            ],
            'url' => ['required', 'url', 'max:500'],
            'license' => ['present', 'nullable', 'string', 'max:255'],
            'author' => ['present', 'nullable', 'string', 'max:255'],
            'category_taxonomies' => ['present', 'array'],
            'category_taxonomies.*' => ['exists:taxonomies,id'],
            'published_at' => ['present', 'nullable', 'string', 'date'],
            'last_modified_at' => ['present', 'nullable', 'string', 'date'],
        ];
    }
}
