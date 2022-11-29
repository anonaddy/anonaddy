<?php

namespace App\Http\Requests;

use App\Rules\NotLocalDomain;
use App\Rules\NotUsedAsRecipientDomain;
use App\Rules\ValidDomain;
use Illuminate\Foundation\Http\FormRequest;

class StoreDomainRequest extends FormRequest
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
            'domain' => [
                'required',
                'string',
                'max:50',
                'unique:domains',
                new ValidDomain(),
                new NotLocalDomain(),
                new NotUsedAsRecipientDomain(),
            ],
        ];
    }
}
