<?php

namespace App\Http\Requests;

use App\Rules\RegisterUniqueRecipient;
use Illuminate\Foundation\Http\FormRequest;

class EditDefaultRecipientRequest extends FormRequest
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
            'email' => [
                'required',
                'email:rfc,dns',
                'max:254',
                'confirmed',
                new RegisterUniqueRecipient(),
                'not_in:'.$this->user()->email,
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'email.not_in' => 'That email is the same as the current one.',
        ];
    }
}
