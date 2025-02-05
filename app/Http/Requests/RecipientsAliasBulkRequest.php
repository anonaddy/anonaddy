<?php

namespace App\Http\Requests;

use App\Rules\VerifiedRecipientId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class RecipientsAliasBulkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'ids' => Arr::whereNotNull($this->ids ?? []),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => 'required|array|max:25|min:1',
            'ids.*' => 'required|uuid|distinct',
            'recipient_ids' => [
                'array',
                'max:10',
                new VerifiedRecipientId,
            ],
            'recipient_ids.*' => 'required|uuid|distinct',
        ];
    }
}
