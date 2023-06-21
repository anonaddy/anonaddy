<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRuleId implements ValidationRule
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
    public function validate(string $attribute, mixed $ids, Closure $fail): void
    {
        $validRuleIds = user()
            ->rules()
            ->pluck('id')
            ->toArray();

        foreach ($ids as $id) {
            if (! in_array($id, $validRuleIds)) {
                $fail('Invalid Rule ID.');
            }
        }
    }
}
