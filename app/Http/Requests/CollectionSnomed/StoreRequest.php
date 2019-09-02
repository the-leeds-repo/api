<?php

namespace App\Http\Requests\CollectionSnomed;

use App\Models\Collection;
use App\Models\Taxonomy;
use App\Rules\RootTaxonomyIs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->user()->isSuperAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'code' => [
                'required',
                'string',
                'min:1',
                'max:255',
                Rule::unique('collections', 'name')
                    ->where('type', Collection::TYPE_SNOMED),
            ],
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'order' => [
                'required',
                'integer',
                'min:1',
                'max:' . (Collection::snomed()->count() + 1),
            ],
            'category_taxonomies' => ['present', 'array'],
            'category_taxonomies.*' => [
                'string',
                'exists:taxonomies,id',
                new RootTaxonomyIs(Taxonomy::NAME_CATEGORY),
            ],
        ];
    }
}
