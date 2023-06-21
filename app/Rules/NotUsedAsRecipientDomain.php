<?php

namespace App\Rules;

use App\Models\Recipient;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class NotUsedAsRecipientDomain implements ValidationRule
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
        $recipientDomains = Recipient::whereNotNull('email_verified_at')
            ->select('email')
            ->get()
            ->map(function ($recipient) {
                return Str::afterLast($recipient->email, '@');
            })
            ->unique();

        if ($recipientDomains->contains($value)) {
            $fail('The domain must not already be used by a verified recipient.');
        }
    }
}
