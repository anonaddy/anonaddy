<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class VerifiedRecipientId implements Rule
{
    protected $user;

    protected $verifiedRecipientIds;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(array $verifiedRecipientIds = null)
    {
        $this->user = user();

        if (! is_null($verifiedRecipientIds)) {
            $this->verifiedRecipientIds = $verifiedRecipientIds;
        } else {
            $this->verifiedRecipientIds = $this->user
            ->verifiedRecipients()
            ->pluck('id')
            ->toArray();
        }
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $ids
     * @return bool
     */
    public function passes($attribute, $ids)
    {
        if (! is_array($ids)) {
            return false;
        }

        foreach ($ids as $id) {
            if (!in_array($id, $this->verifiedRecipientIds)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Invalid Recipient.';
    }
}
