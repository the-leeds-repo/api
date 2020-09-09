<?php

namespace App\Http\Requests\Organisation;

use App\Models\File;
use App\Models\Organisation;
use App\Rules\FileIsMimeType;
use App\Rules\FileIsPendingAssignment;
use App\Rules\Postcode;
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
        if ($this->user('api')->isGlobalAdmin()) {
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
            'slug' => ['required', 'string', 'min:1', 'max:255', 'unique:' . table(Organisation::class) . ',slug', new Slug()],
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'description' => ['required', 'string', 'min:1', 'max:10000'],
            'url' => ['required', 'url', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:1', 'max:255'],
            'address_line_1' => ['present', 'nullable', 'string', 'min:1', 'max:255'],
            'address_line_2' => ['present', 'nullable', 'string', 'min:1', 'max:255'],
            'address_line_3' => ['present', 'nullable', 'string', 'min:1', 'max:255'],
            'city' => ['present', 'nullable', 'string', 'min:1', 'max:255'],
            'county' => ['present', 'nullable', 'string', 'min:1', 'max:255'],
            'postcode' => ['present', 'nullable', 'string', 'min:1', 'max:255', new Postcode()],
            'country' => ['present', 'nullable', 'string', 'min:1', 'max:255'],
            'is_hidden' => ['required', 'boolean'],
            'logo_file_id' => [
                'nullable',
                'exists:files,id',
                new FileIsMimeType(File::MIME_TYPE_PNG),
                new FileIsPendingAssignment(),
            ],
        ];
    }
}
