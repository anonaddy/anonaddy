<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutboundMessage extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'alias_id',
        'recipient_id',
        'email_type',
        'bounced',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'alias_id' => 'string',
        'recipient_id' => 'string',
        'bounced' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user for the outbound message.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the recipient for the outbound message.
     */
    public function recipient()
    {
        return $this->belongsTo(Recipient::class);
    }

    /**
     * Get the alias for the outbound message.
     */
    public function alias()
    {
        return $this->belongsTo(Alias::class)->withTrashed();
    }

    public function markAsBounced()
    {
        $this->update(['bounced' => true]);
    }
}
