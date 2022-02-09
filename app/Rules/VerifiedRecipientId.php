<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class VerifiedRecipientId implements Rule
{
    protected $user;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->user = user();
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
        $verifiedRecipientIds = $this->user
            ->verifiedRecipients()
            ->select('id')
            ->get()
            ->map(function ($recipient) {
                return $recipient->id;
            })
            ->toArray();

        if (! is_array($ids)) {
            return false;
        }

        foreach ($ids as $id) {
            if (!in_array($id, $verifiedRecipientIds)) {
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
