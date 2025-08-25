<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class ValidRegex implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (@preg_match("/{$value}/", 'test') === false || preg_last_error() !== PREG_NO_ERROR) {
            $fail("{$attribute} is an invalid regular expression.");
        }

        if (! App::environment('testing')) {
            try {
                DB::select('SELECT ? REGEXP ?', ['test', $value]);
            } catch (\Exception $e) {
                $fail("{$attribute} is an invalid regular expression.");
            }
        }
    }
}
