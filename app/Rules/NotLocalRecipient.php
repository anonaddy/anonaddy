<?php

namespace App\Rules;

use App\Models\Domain;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class NotLocalRecipient implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $emailDomain = strtolower(Str::afterLast($value, '@'));

        // Make sure the recipient domain is not added as a verified custom domain
        if (Domain::whereNotNull('domain_verified_at')->pluck('domain')->contains($emailDomain)) {
            return false;
        }

        $count = collect(config('anonaddy.all_domains'))
            ->filter(function ($domain) use ($emailDomain) {
                return $domain === $emailDomain || Str::endsWith($emailDomain, '.'.$domain);
            })
            ->count();

        return $count === 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The recipient cannot use a local domain or be an alias.';
    }
}
