<?php

namespace App\Models;

use App\Traits\HasUuid;
use LaravelWebauthn\Models\Casts\Base64;
use LaravelWebauthn\Models\Casts\TrustPath;
use LaravelWebauthn\Models\Casts\Uuid;
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

    protected $visible = [
        'id',
        'name',
        'enabled',
        'type',
        'transports',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'counter' => 'integer',
        'transports' => 'array',
        'credentialId' => Base64::class,
        'credentialPublicKey' => Base64::class,
        'aaguid' => Uuid::class,
        'trustPath' => TrustPath::class,
        'enabled' => 'boolean',
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
