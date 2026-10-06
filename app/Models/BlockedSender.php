<?php

namespace App\Models;

use App\Traits\HasUuid;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedSender extends Model
{
    use HasFactory;
    use HasUuid;

    public const ALREADY_ON_BLOCKLIST_MESSAGE = 'This email or domain is already on your blocklist.';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'type',
        'value',
        'blocked',
        'last_blocked',
        'created_at',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'blocked' => 'integer',
        'last_blocked' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isEmail(): bool
    {
        return $this->type === 'email';
    }

    public function isDomain(): bool
    {
        return $this->type === 'domain';
    }

    /**
     * Shared alias domains that must not be blocked from a banner link.
     */
    public static function isProtectedAliasDomain(string $domain): bool
    {
        $domain = strtolower(trim($domain));

        if ($domain === '') {
            return false;
        }

        foreach (config('anonaddy.all_domains', []) as $aliasDomain) {
            $aliasDomain = strtolower((string) $aliasDomain);

            if ($aliasDomain === '') {
                continue;
            }

            if ($domain === $aliasDomain || str_ends_with($domain, '.'.$aliasDomain)) {
                return true;
            }
        }

        return false;
    }
}
