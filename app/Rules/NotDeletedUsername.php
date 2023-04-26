<?php

namespace App\Rules;

use App\Models\DeletedUsername;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotDeletedUsername implements ValidationRule
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
        $deletedUsernames = DeletedUsername::select('username')
            ->get()
            ->map(function ($item) {
                return $item->username;
            })
            ->toArray();

        if (in_array(strtolower($value), $deletedUsernames)) {
            $fail('The :attribute has already been taken.');
        }
    }
}
