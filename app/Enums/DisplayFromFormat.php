<?php

namespace App\Enums;

enum DisplayFromFormat: int
{
    case DEFAULT = 0;
    case BRACKETS = 1;
    case DOMAIN = 2;
    case NAME = 3;
    case ADDRESS = 4;
    case NONE = 5;
    case DOMAINONLY = 6;
    case LEGACY = 7;
}
