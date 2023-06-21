<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecipientKeyRequest extends FormRequest
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
            'key_data' => [
                'string',
                'regex:/-----BEGIN PGP PUBLIC KEY BLOCK-----([A-Za-z0-9+=\/\n]+)-----END PGP PUBLIC KEY BLOCK-----/i',
            ],
        ];
    }
}
