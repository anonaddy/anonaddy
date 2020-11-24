<?php

namespace App\Models;

use App\Traits\HasUuid;
use LaravelWebauthn\Models\WebauthnKey as ModelsWebauthnKey;

class WebauthnKey extends ModelsWebauthnKey
{
    use HasUuid;

    public $incrementing = false;

    protected $keyType = 'string';
}
