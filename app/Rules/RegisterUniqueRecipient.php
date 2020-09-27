<?php

namespace App\Rules;

use App\Models\Recipient;
use Illuminate\Contracts\Validation\Rule;

class RegisterUniqueRecipient implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $items = Recipient::whereNotNull('email_verified_at')
            ->get()
            ->filter(function ($recipient) use ($value) {
                if (($recipient->email) == strtolower($value)) {
                    return $recipient;
                }
            });

        return count($items) === 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'A user with that email already exists.';
    }
}
