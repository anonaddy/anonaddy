<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeRegex implements ValidationRule
{
    /**
     * Substrings that commonly cause catastrophic backtracking (ReDoS)
     * when combined with a long input string.
     *
     * @var array<int, string>
     */
    protected static array $dangerousSubstrings = [
        '(.*)*',
        '(.*)+',
        '(.+)*',
        '(.+)+',
        '(\w+)+',
        '(\w*)*',
        '(\w+)*',
        '(\w*)+',
        '(\s+)+',
        '(\s*)*',
        '(\s+)*',
        '(\s*)+',
        '(\d+)+',
        '(\d*)*',
        '(\d+)*',
        '(\d*)+',
    ];

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        foreach (self::$dangerousSubstrings as $dangerous) {
            if (str_contains($value, $dangerous)) {
                $fail('The :attribute contains a pattern that can cause excessive CPU use. Please use a simpler expression.');

                return;
            }
        }

        if ($this->hasNestedQuantifiers($value)) {
            $fail('The :attribute contains nested quantifiers that can cause excessive CPU use. Please use a simpler expression.');
        }
    }

    /**
     * Detect nested quantifiers: a group that contains + or * and is itself quantified, e.g. (a+)+ or (.*)*
     */
    protected function hasNestedQuantifiers(string $pattern): bool
    {
        $len = strlen($pattern);
        $i = 0;

        while ($i < $len) {
            if ($pattern[$i] === '\\') {
                $i += 2;

                continue;
            }
            if ($pattern[$i] === ')' && $i + 1 < $len) {
                $next = $i + 1;
                while ($next < $len && $pattern[$next] === ' ') {
                    $next++;
                }
                if ($next < $len && ($pattern[$next] === '*' || $pattern[$next] === '+')) {
                    $depth = 1;
                    $open = $i - 1;
                    while ($open >= 0 && $depth > 0) {
                        if ($pattern[$open] === ')') {
                            if ($open === 0 || $pattern[$open - 1] !== '\\') {
                                $depth++;
                            }
                            $open--;

                            continue;
                        }
                        if ($pattern[$open] === '(') {
                            if ($open === 0 || $pattern[$open - 1] !== '\\') {
                                $depth--;
                                if ($depth === 0) {
                                    $group = substr($pattern, $open + 1, $i - $open - 1);
                                    if ($this->groupContainsQuantifier($group)) {
                                        return true;
                                    }
                                    break;
                                }
                            }
                            $open--;

                            continue;
                        }
                        $open--;
                    }
                }
            }
            $i++;
        }

        return false;
    }

    /**
     * Check if the group body contains an unescaped + or * (quantifier).
     */
    protected function groupContainsQuantifier(string $group): bool
    {
        $len = strlen($group);
        for ($i = 0; $i < $len; $i++) {
            if ($group[$i] === '\\') {
                $i++;

                continue;
            }
            if (($group[$i] === '+' || $group[$i] === '*') && ($i === 0 || $group[$i - 1] !== '\\')) {
                return true;
            }
        }

        return false;
    }
}
