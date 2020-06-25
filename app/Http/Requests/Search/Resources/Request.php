<?php

namespace App\Http\Requests\Search\Resources;

use Illuminate\Foundation\Http\FormRequest;

class Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'query' => ['required_without_all:category,persona,category_taxonomy.id,category_taxonomy.name', 'string', 'min:3', 'max:255'],
            'category' => ['required_without_all:query,persona,category_taxonomy.id,category_taxonomy.name', 'string', 'min:1', 'max:255'],
            'persona' => ['required_without_all:query,category,category_taxonomy.id,category_taxonomy.name', 'string', 'min:1', 'max:255'],
            'category_taxonomy' => ['array'],
            'category_taxonomy.id' => ['required_without_all:query,category,persona,category_taxonomy.name', 'string', 'size:36'],
            'category_taxonomy.name' => ['required_without_all:query,category,persona,category_taxonomy.id', 'string', 'min:1', 'max:255'],
        ];
    }
}
