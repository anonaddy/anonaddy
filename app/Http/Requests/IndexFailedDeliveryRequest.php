<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexFailedDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
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
            'filter.email_type' => [
                'nullable',
                'string',
                'in:inbound,outbound,inbound_quarantined',
            ],
        ];
    }
}
