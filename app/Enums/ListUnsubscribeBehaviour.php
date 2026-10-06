<?php

namespace App\Enums;

enum ListUnsubscribeBehaviour: int
{
    case OriginalWithFallback = 0;
    case Deactivate = 1;
    case Delete = 2;
    case BlockEmail = 3;
    case BlockDomain = 4;
    case OriginalWithNoFallback = 5;
}
