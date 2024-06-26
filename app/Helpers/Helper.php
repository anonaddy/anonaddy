<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

function user()
{
    return auth()->user();
}

function carbon(...$args)
{
    return new Carbon(...$args);
}

function randomString(int $length): string
{
    $alphabet = '0123456789abcdefghijklmnopqrstuvwxyz';

    $str = '';

    for ($i = 0; $i < $length; $i++) {
        $index = random_int(0, 35);
        $str .= $alphabet[$index];
    }

    return $str;
}

function stripEmailExtension(string $email): string
{
    if (! Str::contains($email, '@')) {
        return $email;
    }

    // Strip the email of extensions
    [$localPart, $domain] = explode('@', strtolower($email));
    // Remove plus extension from local part if present
    $localPart = Str::contains($localPart, '+') ? Str::before($localPart, '+') : $localPart;

    return $localPart.'@'.$domain;
}
