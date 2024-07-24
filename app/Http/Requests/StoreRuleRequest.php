<?php

namespace App\Http\Requests;

use App\Rules\ValidRegex;
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
                    'alias_description',
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
            'conditions.*.values' => Rule::forEach(function ($value, $attribute, $data, $condition) {
                if (in_array($condition['match'], ['matches regex', 'does not match regex'])) {
                    return [
                        'required',
                        'array',
                        'min:1',
                        'max:1',
                    ];
                }

                return [
                    'required',
                    'array',
                    'min:1',
                    'max:10',
                ];
            }),
            'conditions.*.values.*' => Rule::forEach(function ($value, $attribute, $data) {
                if (in_array(array_values($data)[1], ['matches regex', 'does not match regex'])) {
                    return [new ValidRegex];
                }

                return ['distinct'];
            }),
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
                    'removeAttachments',
                    'forwardTo',
                    //'webhook',
                ]),
            ],
            'actions.*.value' => Rule::forEach(function ($value, $attribute, $data, $action) {
                if ($action['type'] === 'forwardTo') {
                    return [Rule::in(user()->verifiedRecipients()->pluck('id')->toArray())]; // Must be a valid verified recipient
                }

                return [
                    'required',
                    'max:50',
                ];
            }),
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
