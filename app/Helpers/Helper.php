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
