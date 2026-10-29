<?php

namespace App\Http\Requests;

use App\Enums\CustomAliasRandomCharset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomAliasRandomCharactersRequest extends FormRequest
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
            'enabled' => ['required', 'boolean'],
            'length' => ['required', 'integer', 'between:1,10'],
            'charset' => ['required', 'string', Rule::enum(CustomAliasRandomCharset::class)],
        ];
    }
}
