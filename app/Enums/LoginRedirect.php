<?php

namespace App\Enums;

enum LoginRedirect: int
{
    case DEFAULT = 0;
    case ALIASES = 1;
    case RECIPIENTS = 2;
    case USERNAMES = 3;
    case DOMAINS = 4;
}
