<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidRuleId implements Rule
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
        $validRuleIds = $this->user
            ->rules()
            ->pluck('id')
            ->toArray();

        foreach ($ids as $id) {
            if (!in_array($id, $validRuleIds)) {
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
        return 'Invalid Rule ID.';
    }
}
