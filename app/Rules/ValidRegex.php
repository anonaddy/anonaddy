<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidRegex implements ValidationRule
{
    /**
     * Delimiters tried when wrapping a user-supplied pattern for preg_*.
     *
     * @var array<int, string>
     */
    protected static array $delimiters = ['/', '#', '~', '%', '+', '!', '@', ';', '`'];

    /**
     * Wrap a user-supplied pattern with a delimiter that does not appear in the pattern.
     */
    public static function wrap(string $pattern): ?string
    {
        foreach (self::$delimiters as $delimiter) {
            if (! str_contains($pattern, $delimiter)) {
                return $delimiter.$pattern.$delimiter;
            }
        }

        return null;
    }

    /**
     * Match $subject against a user-supplied pattern (without delimiters).
     */
    public static function matches(string $pattern, string $subject): bool
    {
        $delimited = self::wrap($pattern);

        if ($delimited === null) {
            return false;
        }

        return @preg_match($delimited, $subject) === 1;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail("{$attribute} is an invalid regular expression.");

            return;
        }

        $delimited = self::wrap($value);

        // First check PHP regex validity
        if ($delimited === null || @preg_match($delimited, 'test') === false || preg_last_error() !== PREG_NO_ERROR) {
            $fail("{$attribute} is an invalid regular expression.");

            return;
        }

        // Then check MySQL regex validity, sqlite doesn't support REGEX
        if (! App::environment('testing')) {
            try {
                DB::select('SELECT ? REGEXP ?', ['test', $value]);
            } catch (\Exception $e) {
                $fail("{$attribute} is an invalid regular expression.");
            }
        }
    }
}
