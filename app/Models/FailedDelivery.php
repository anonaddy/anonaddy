<?php

namespace App\Models;

use App\Traits\HasEncryptedAttributes;
use App\Traits\HasUuid;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        'id',
        'user_id',
        'recipient_id',
        'alias_id',
        'is_stored',
        'bounce_type',
        'remote_mta',
        'sender',
        'email_type',
        'status',
        'code',
        'attempted_at',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'recipient_id' => 'string',
        'alias_id' => 'string',
        'is_stored' => 'boolean',
        'attempted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        FailedDelivery::deleting(function ($failedDelivery) {
            if ($failedDelivery->is_stored) {
                Storage::disk('local')->delete($failedDelivery->id.'.eml');
            }
        });
    }

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
