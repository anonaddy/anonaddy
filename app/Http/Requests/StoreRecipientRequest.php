<?php

namespace App\Http\Requests;

use App\Rules\NotLocalRecipient;
use App\Rules\UniqueRecipient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

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
                'bail',
                'required',
                'string',
                'ascii',
                App::environment(['local', 'testing']) ? 'email:rfc' : 'email:rfc,dns',
                'max:254',
                new UniqueRecipient,
                new NotLocalRecipient,
            ],
        ];
    }
}
