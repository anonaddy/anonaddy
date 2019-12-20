<?php

namespace App\Rules;

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
        $emailDomain = Str::afterLast($value, '@');

        $count = collect(config('anonaddy.all_domains'))
            ->filter(function ($domain) use ($emailDomain) {
                return Str::endsWith(strtolower($emailDomain), $domain);
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
        return 'The recipient cannot be a local one or alias.';
    }
}
