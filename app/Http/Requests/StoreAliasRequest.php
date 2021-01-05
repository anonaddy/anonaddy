<?php

namespace App\Http\Requests;

use App\Rules\ValidAliasLocalPart;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAliasRequest extends FormRequest
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
                Rule::in($this->user()->domainOptions())
            ],
            'description' => 'nullable|max:100',
            'format' => 'nullable|in:random_characters,uuid,random_words,custom'
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes('local_part', [
            'required',
            'max:50',
            Rule::unique('aliases')->where(function ($query) {
                return $query->where('local_part', $this->validationData()['local_part'])
                ->where('domain', $this->validationData()['domain']);
            }),
            new ValidAliasLocalPart
        ], function () {
            $format = $this->validationData()['format'] ?? 'random_characters';
            return $format === 'custom';
        });
    }

    public function messages()
    {
        return [
            'local_part.unique' => 'That alias already exists.',
        ];
    }
}
