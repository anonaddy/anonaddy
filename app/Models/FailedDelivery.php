<?php

namespace App\Models;

use App\Traits\HasEncryptedAttributes;
use App\Traits\HasUuid;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailedDelivery extends Model
{
    use HasUuid;
    use HasEncryptedAttributes;
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $encrypted = [
        'sender',
    ];

    protected $fillable = [
        'user_id',
        'recipient_id',
        'alias_id',
        'bounce_type',
        'remote_mta',
        'sender',
        'email_type',
        'status',
        'code',
        'attempted_at',
    ];

    protected $dates = [
        'attempted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'recipient_id' => 'string',
        'alias_id' => 'string',
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

    /**
     * Get the user for the failed delivery.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the recipient for the failed delivery.
     */
    public function recipient()
    {
        return $this->belongsTo(Recipient::class);
    }

    /**
     * Get the alias for the failed delivery.
     */
    public function alias()
    {
        return $this->belongsTo(Alias::class);
    }
}
