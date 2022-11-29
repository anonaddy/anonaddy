<?php

namespace App\Http\Requests;

use App\Rules\NotLocalRecipient;
use App\Rules\UniqueRecipient;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecipientRequest extends FormRequest
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
                'string',
                'max:254',
                'email:rfc',
                new UniqueRecipient(),
                new NotLocalRecipient(),
            ],
        ];
    }
}
