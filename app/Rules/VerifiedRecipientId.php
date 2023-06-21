<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class VerifiedRecipientId implements ValidationRule
{
    protected $verifiedRecipientIds;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(array $verifiedRecipientIds = null)
    {
        if (! is_null($verifiedRecipientIds)) {
            $this->verifiedRecipientIds = $verifiedRecipientIds;
        } else {
            $this->verifiedRecipientIds = user()
                ->verifiedRecipients()
                ->pluck('id')
                ->toArray();
        }
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $ids, Closure $fail): void
    {
        // Multiple calls to $fail simply add more validation errors, they don't stop processing.
        if (! is_array($ids)) {
            $fail('Invalid Recipient');
        } else {
            foreach ($ids as $id) {
                if (! in_array($id, $this->verifiedRecipientIds)) {
                    $fail('Invalid Recipient');
                }
            }
        }
    }
}
