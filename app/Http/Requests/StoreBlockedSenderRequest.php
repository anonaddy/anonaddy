<?php

namespace App\Http\Requests;

use App\Models\BlockedSender;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlockedSenderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                Rule::in(['email', 'domain']),
            ],
            'value' => [
                'required',
                'string',
                'max:253',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $type = $this->input('type');
                    $normalised = is_string($value) ? strtolower(trim($value)) : $value;
                    if ($type === 'email') {
                        if (! filter_var($normalised, FILTER_VALIDATE_EMAIL)) {
                            $fail('The value must be a valid email address when type is email.');
                        }
                    }
                    if ($type === 'domain') {
                        // Require a proper domain with at least one dot and a TLD (e.g. example.com)
                        if (! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $normalised)) {
                            $fail('The value must be a valid domain (e.g. example.com) when type is domain.');
                        }
                    }
                    if (user()->blockedSenders()->where('type', $type)->where('value', $normalised)->exists()) {
                        $fail(BlockedSender::ALREADY_ON_BLOCKLIST_MESSAGE);
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors (UK English).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Please select whether you are blocking an email or a domain.',
            'type.in' => 'The type must be either email or domain.',
            'value.required' => 'Please enter an email address or domain to block.',
            'value.max' => 'The value may not be longer than 253 characters.',
        ];
    }

    /**
     * Prepare the data for validation (normalise value to lowercase).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('value') && is_string($this->value)) {
            $this->merge(['value' => strtolower(trim($this->value))]);
        }
    }
}
