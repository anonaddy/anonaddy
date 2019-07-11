<?php

namespace App;

use App\Traits\HasEncryptedAttributes;
use App\Traits\HasUuid;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Notifications\Notifiable;

class Recipient extends Model
{
    use Notifiable, HasUuid, HasEncryptedAttributes;

    public $incrementing = false;

    protected $encrypted = [
        'email',
        'fingerprint'
    ];

    protected $fillable = [
        'email',
        'user_id',
        'should_encrypt',
        'fingerprint',
        'email_verified_at'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'email_verified_at'
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'should_encrypt' => 'boolean'
    ];

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
        $this->notify(new VerifyEmail);
    }
}
