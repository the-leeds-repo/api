<?php

namespace App\Http\Requests\Location;

use App\Models\File;
use App\Rules\FileIsMimeType;
use App\Rules\FileIsPendingAssignment;
use App\Rules\Postcode;
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
        if ($this->user('api')->isServiceAdmin()) {
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
            'address_line_1' => ['required', 'string', 'min:1', 'max:255'],
            'address_line_2' => ['present', 'nullable', 'string', 'min:1', 'max:255'],
            'address_line_3' => ['present', 'nullable', 'string', 'min:1', 'max:255'],
            'city' => ['required', 'string', 'min:1', 'max:255'],
            'county' => ['required', 'string', 'min:1', 'max:255'],
            'postcode' => ['required', 'string', 'min:1', 'max:255', new Postcode()],
            'country' => ['required', 'string', 'min:1', 'max:255'],
            'accessibility_info' => ['present', 'nullable', 'string', 'min:1', 'max:10000'],
            'has_wheelchair_access' => ['required', 'boolean'],
            'has_induction_loop' => ['required', 'boolean'],
            'image_file_id' => [
                'nullable',
                'exists:files,id',
                new FileIsMimeType(File::MIME_TYPE_PNG),
                new FileIsPendingAssignment(),
            ],
        ];
    }
}
