<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexAliasRequest extends FormRequest
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
            'page' => [
                'nullable',
                'array'
            ],
            'page.number' => [
                'nullable',
                'integer'
            ],
            'filter' => [
                'nullable',
                'array'
            ],
            'filter.search' => [
                'nullable',
                'string',
                'max:50',
                'min:3'
            ],
            'filter.deleted' => [
                'nullable',
                'in:with,without,only',
                'string',
            ],
        ];
    }
}
