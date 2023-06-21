<?php

namespace App\Rules;

use App\Models\Domain;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class NotLocalRecipient implements ValidationRule
{
    /**
     * Indicates whether the rule should be implicit.
     *
     * @var bool
     */
    public $implicit = true;

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $emailDomain = strtolower(Str::afterLast($value, '@'));

        // Make sure the recipient domain is not added as a verified custom domain
        if (Domain::whereNotNull('domain_verified_at')->pluck('domain')->contains($emailDomain)) {
            $fail('The recipient cannot use a domain that is already used by a custom domain.');
        }

        $count = collect(config('anonaddy.all_domains'))
            ->filter(function ($domain) use ($emailDomain) {
                return $domain === $emailDomain || Str::endsWith($emailDomain, '.'.$domain);
            })
            ->count();

        if ($count !== 0) {
            $fail('The recipient cannot use a local domain or be an alias.');
        }
    }
}
