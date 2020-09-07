<?php

namespace App\Http\Requests\Organisation;

use Illuminate\Foundation\Http\FormRequest;

class ShowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->route('organisation')->is_hidden === false) {
            return true;
        }

        if ($this->user() === null) {
            return false;
        }

        if ($this->user()->isGlobalAdmin()) {
            return true;
        }

        if (in_array($this->route('organisation')->id, $this->user()->organisationIds())) {
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
            //
        ];
    }
}
