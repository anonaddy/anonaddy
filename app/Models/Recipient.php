<?php

namespace App\Models;

use App\Notifications\CustomVerifyEmail;
use App\Notifications\UsernameReminder;
use App\Traits\HasEncryptedAttributes;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Recipient extends Model
{
    use Notifiable;
    use HasUuid;
    use HasEncryptedAttributes;
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $encrypted = [
        'email',
        'fingerprint',
    ];

    protected $fillable = [
        'email',
        'user_id',
        'can_reply_send',
        'should_encrypt',
        'inline_encryption',
        'protected_headers',
        'fingerprint',
        'email_verified_at',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'can_reply_send' => 'boolean',
        'should_encrypt' => 'boolean',
        'inline_encryption' => 'boolean',
        'protected_headers' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        Recipient::deleting(function ($recipient) {
            if ($recipient->fingerprint) {
                $recipient->user->deleteKeyFromKeyring($recipient->fingerprint);
            }

            $recipient->aliases()->detach();
        });
    }

    /**
     * Query scope to return verified or unverified recipients.
     */
    public function scopeVerified($query, $condition = null)
    {
        if ($condition === 'false') {
            return $query->whereNull('email_verified_at');
        }

        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Get the user the recipient belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the aliases that have this recipient attached.
     */
    public function aliases()
    {
        return $this->belongsToMany(Alias::class, 'alias_recipients')->using(AliasRecipient::class);
    }

    /**
     * Get all of the recipient's failed deliveries.
     */
    public function failedDeliveries()
    {
        return $this->hasMany(FailedDelivery::class);
    }

    /**
     * Get all of the user's custom domains.
     */
    public function domainsUsingAsDefault()
    {
        return $this->hasMany(Domain::class, 'default_recipient_id', 'id');
    }

    /**
     * Get all of the user's usernames using this recipient as their default.
     */
    public function usernamesUsingAsDefault()
    {
        return $this->hasMany(Username::class, 'default_recipient_id', 'id');
    }

    /**
     * Determine if the recipient has a verified email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return ! is_null($this->email_verified_at);
    }

    /**
     * Mark this recipient's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmail());
    }

    /**
     * Send the username reminder notification.
     *
     * @return void
     */
    public function sendUsernameReminderNotification()
    {
        $this->notify(new UsernameReminder());
    }

    /**
     * Get the email address that should be used for verification.
     *
     * @return string
     */
    public function getEmailForVerification()
    {
        return $this->email;
    }
}
