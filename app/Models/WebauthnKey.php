<?php

namespace App\Models;

use App\Traits\HasUuid;
use LaravelWebauthn\Models\WebauthnKey as ModelsWebauthnKey;

class WebauthnKey extends ModelsWebauthnKey
{
    use HasUuid;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'name',
        'enabled',
        'credentialId',
        'type',
        'transports',
        'attestationType',
        'trustPath',
        'aaguid',
        'credentialPublicKey',
        'counter',
        'timestamp',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'counter' => 'integer',
        'transports' => 'array',
    ];

    /**
     * Enabled the key for use.
     */
    public function enable()
    {
        $this->update(['enabled' => true]);
    }

    /**
     * Disable the key for use.
     */
    public function disable()
    {
        $this->update(['enabled' => false]);
    }
}
