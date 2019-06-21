<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class UniqueUserRecipient implements Rule
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
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $userRecipients = $this->user
            ->recipients()
            ->get()
            ->map(function ($recipient) {
                return $recipient->email;
            })
            ->toArray();

        return !in_array($value, $userRecipients);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'A recipient with that email already exists.';
    }
}
