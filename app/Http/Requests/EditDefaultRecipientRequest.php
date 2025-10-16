<?php

namespace App\Http\Requests;

use App\Rules\NotLocalRecipient;
use App\Rules\RegisterUniqueRecipient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

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
                'bail',
                'required',
                'string',
                'ascii',
                App::environment(['local', 'testing']) ? 'email:rfc' : 'email:rfc,dns',
                'max:254',
                new RegisterUniqueRecipient,
                new NotLocalRecipient,
                'not_in:'.$this->user()->email,
            ],
            'current' => 'required|string|current_password',
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
