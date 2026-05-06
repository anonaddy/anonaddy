<?php

namespace App\Enums;

enum FailedDeliveryNotificationPreference: int
{
    case All = 0;
    case NormalOnly = 1;
    case QuarantinedOnly = 2;
    case None = 3;
}
