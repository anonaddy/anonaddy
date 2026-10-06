<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateHiddenAliasDomainsRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hidden_domains' => ['present', 'array', 'max:500'],
            'hidden_domains.*' => [
                'string',
                'distinct',
                Rule::in($this->user()->domainOptions()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hidden_domains.*.in' => 'Select a domain from your account.',
            'hidden_domains.*.distinct' => 'Select each domain once.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $defaultAliasDomain = $this->user()->default_alias_domain;
                $hiddenDomains = collect($this->input('hidden_domains', []))
                    ->reject(fn (string $domain) => $domain === $defaultAliasDomain);

                $visibleDomains = $this->user()->domainOptions()
                    ->reject(fn (string $domain) => $hiddenDomains->contains($domain));

                if ($visibleDomains->isEmpty()) {
                    $validator->errors()->add(
                        'hidden_domains',
                        'Leave at least one domain visible in the alias picker.'
                    );
                }
            },
        ];
    }
}
