<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRuleRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:50',
            ],
            'conditions' => [
                'required',
                'array',
                'max:5',
            ],
            'conditions.*.type' => [
                'required',
                Rule::in([
                    'subject',
                    'sender',
                    'alias',
                ]),
            ],
            'conditions.*.match' => [
                'sometimes',
                'required',
                Rule::in([
                    'is exactly',
                    'is not',
                    'contains',
                    'does not contain',
                    'starts with',
                    'does not start with',
                    'ends with',
                    'does not end with',
                ]),
            ],
            'conditions.*.values' => [
                'required',
                'array',
                'min:1',
                'max:10',
            ],
            'conditions.*.values.*' => [
                'distinct',
            ],
            'actions' => [
                'required',
                'array',
                'max:5',
            ],
            'actions.*.type' => [
                'required',
                Rule::in([
                    'subject',
                    'displayFrom',
                    'encryption',
                    'banner',
                    'block',
                    'webhook',
                ]),
            ],
            'actions.*.value' => [
                'required',
                'max:50',
            ],
            'operator' => [
                'required',
                'in:AND,OR',
            ],
            'forwards' => 'boolean',
            'replies' => 'boolean',
            'sends' => 'boolean',
        ];
    }
}
