<?php

namespace App\Enums;

enum DnsLookupStatus
{
    case Found;
    case Missing;
    case Failed;
}
