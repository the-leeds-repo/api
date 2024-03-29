<?php

namespace App\Http\Requests\Referral;

use App\Models\Referral;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->user('api')->isServiceWorker($this->referral->service)) {
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
            'status' => [
                'required',
                Rule::in([
                    Referral::STATUS_NEW,
                    Referral::STATUS_IN_PROGRESS,
                    Referral::STATUS_COMPLETED,
                    Referral::STATUS_INCOMPLETED,
                ]),
            ],
            'comments' => [
                Rule::requiredIf(function () {
                    return $this->status === $this->referral->status;
                }),
                'present',
                'nullable',
                'string',
                'min:1',
                'max:255',
            ],
        ];
    }
}
