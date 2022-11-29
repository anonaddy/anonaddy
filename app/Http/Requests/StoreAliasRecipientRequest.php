<?php

namespace App\Http\Requests;

use App\Rules\VerifiedRecipientId;
use Illuminate\Foundation\Http\FormRequest;

class StoreAliasRecipientRequest extends FormRequest
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
            'recipient_ids' => [
                'array',
                'max:10',
                new VerifiedRecipientId(),
            ],
        ];
    }
}
