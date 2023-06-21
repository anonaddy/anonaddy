<?php

namespace App\Rules;

use App\Models\Recipient;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RegisterUniqueRecipient implements ValidationRule
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
        $items = Recipient::whereNotNull('email_verified_at')
            ->select('email')
            ->get()
            ->filter(function ($recipient) use ($value) {
                if (($recipient->email) == strtolower($value)) {
                    return $recipient;
                }
            });

        if (count($items) !== 0) {
            $fail('A user with that email already exists.');
        }
    }
}
