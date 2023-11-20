<?php

use Illuminate\Support\Carbon;

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
