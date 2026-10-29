<?php

namespace App\Rules;

use App\Services\WebhookUrlGuard;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class WebhookAction implements ValidationRule
{
    /**
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || array_diff(array_keys($value), ['url', 'include_body', 'include_headers']) !== [] || ! isset($value['url'], $value['include_body'])) {
            $fail('The webhook action must include a url and include_body.');

            return;
        }

        if (! is_string($value['url'])) {
            $fail(WebhookUrlGuard::PUBLIC_HTTPS_MESSAGE);

            return;
        }

        $reason = app(WebhookUrlGuard::class)->failureReason($value['url']);

        if ($reason !== null) {
            $fail($reason);

            return;
        }

        if (! is_bool($value['include_body'])) {
            $fail('The webhook include_body value must be true or false.');

            return;
        }

        if (array_key_exists('include_headers', $value) && ! is_bool($value['include_headers'])) {
            $fail('The webhook include_headers value must be true or false.');
        }
    }
}
