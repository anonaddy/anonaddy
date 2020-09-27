<?php

namespace App\Models;

use App\Traits\HasEncryptedAttributes;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalUsername extends Model
{
    use HasUuid, HasEncryptedAttributes, HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $encrypted = [
        'description'
    ];

    protected $fillable = [
        'username',
        'description',
        'active'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'active' => 'boolean',
        'default_recipient_id' => 'string'
    ];

    public static function boot()
    {
        parent::boot();

        AdditionalUsername::deleting(function ($username) {
            $username->aliases()->delete();
            DeletedUsername::create(['username' => $username->username]);
        });
    }

    /**
     * Set the username.
     */
    public function setUsernameAttribute($value)
    {
        $this->attributes['username'] = strtolower($value);
    }

    /**
     * Get the user for the additional username.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all of the additional usernames's aliases.
     */
    public function aliases()
    {
        return $this->morphMany(Alias::class, 'aliasable');
    }

    /**
     * Get the additional usernames's default recipient.
     */
    public function defaultRecipient()
    {
        return $this->hasOne(Recipient::class, 'id', 'default_recipient_id');
    }
    /**
     * Set the additional usernames's default recipient.
     */
    public function setDefaultRecipientAttribute($recipient)
    {
        $this->attributes['default_recipient_id'] = $recipient->id;
        $this->setRelation('defaultRecipient', $recipient);
    }

    /**
     * Deactivate the username.
     */
    public function deactivate()
    {
        $this->update(['active' => false]);
    }

    /**
     * Activate the username.
     */
    public function activate()
    {
        $this->update(['active' => true]);
    }
}
