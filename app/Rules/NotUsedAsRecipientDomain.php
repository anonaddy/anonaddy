<?php

namespace App\Rules;

use App\Models\Recipient;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class NotUsedAsRecipientDomain implements Rule
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
        return ! Recipient::whereNotNull('email_verified_at')
            ->get()
            ->pluck('email')
            ->map(function ($recipientEmail) {
                return Str::afterLast($recipientEmail, '@');
            })
            ->unique()
            ->contains($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The domain must not already be used by a verified recipient.';
    }
}
