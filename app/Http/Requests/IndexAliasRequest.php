<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                'array',
            ],
            'page.number' => [
                'nullable',
                'integer',
            ],
            'page.size' => [
                'nullable',
                'integer',
                'max:100',
                'min:1',
            ],
            'filter' => [
                'nullable',
                'array',
            ],
            'filter.search' => [
                'nullable',
                'string',
                'max:50',
                'min:3',
            ],
            'filter.deleted' => [
                'nullable',
                'in:with,without,only',
                'string',
            ],
            'filter.active' => [
                'nullable',
                'in:true,false',
                'string',
            ],
            'sort' => [
                'nullable',
                'max:50',
                'min:3',
                Rule::in([
                    'local_part',
                    'domain',
                    'email',
                    'emails_forwarded',
                    'emails_blocked',
                    'emails_replied',
                    'emails_sent',
                    'last_forwarded',
                    'last_blocked',
                    'last_replied',
                    'last_sent',
                    'last_used',
                    'active',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    '-local_part',
                    '-domain',
                    '-email',
                    '-emails_forwarded',
                    '-emails_blocked',
                    '-emails_replied',
                    '-emails_sent',
                    '-last_forwarded',
                    '-last_blocked',
                    '-last_replied',
                    '-last_sent',
                    '-last_used',
                    '-active',
                    '-created_at',
                    '-updated_at',
                    '-deleted_at',
                ]),
            ],
            'recipient' => [
                'nullable',
                'uuid',
            ],
            'domain' => [
                'nullable',
                'uuid',
            ],
            'username' => [
                'nullable',
                'uuid',
            ],
        ];
    }
}
