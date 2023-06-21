<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class NotLocalDomain implements ValidationRule
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
        $count = collect(config('anonaddy.all_domains'))
            ->filter(function ($name) use ($value) {
                return Str::endsWith(strtolower($value), $name);
            })
            ->count();

        if ($count !== 0) {
            $fail('The domain cannot be a local one.');
        }
    }
}
