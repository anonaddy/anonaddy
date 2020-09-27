<?php

namespace App\Rules;

use App\Models\Recipient;
use Illuminate\Contracts\Validation\Rule;

class UniqueRecipient implements Rule
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
        $items = Recipient::where('user_id', $this->user->id)
            ->orWhere('email_verified_at', '!=', null)
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
        return 'A recipient with that email already exists.';
    }
}
