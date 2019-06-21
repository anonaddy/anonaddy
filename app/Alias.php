<?php

namespace App;

use App\Traits\HasEncryptedAttributes;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alias extends Model
{
    use SoftDeletes, HasUuid, HasEncryptedAttributes;

    public $incrementing = false;

    protected $encrypted = [
        'description'
    ];

    protected $fillable = [
        'active',
        'description',
        'email',
        'local_part',
        'domain',
        'domain_id'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'domain_id' => 'string',
        'active' => 'boolean'
    ];

    public function setLocalPartAttribute($value)
    {
        $this->attributes['local_part'] = strtolower($value);
    }

    public function setDomainAttribute($value)
    {
        $this->attributes['domain'] = strtolower($value);
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }

    /**
     * Get the user for the email alias.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the custom domain for the email alias.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the recipients for the email alias.
     */
    public function recipients()
    {
        return $this->BelongsToMany(Recipient::class, 'alias_recipients')->withPivot('id')->using(AliasRecipient::class);
    }

    /**
     * Get the verified recipients for the email alias.
     */
    public function verifiedRecipients()
    {
        return $this->recipients()->whereNotNull('email_verified_at');
    }

    /**
     * Get the verified emails of recipients for the email alias.
     */
    public function recipientEmails()
    {
        return $this->verifiedRecipients()->count() ? $this->verifiedRecipients()
        ->get()
        ->map(function ($recipient) {
            return $recipient->email;
        })->toArray() : $this->user->email;
    }

    public function deactivate()
    {
        $this->update(['active' => false]);
    }

    public function activate()
    {
        $this->update(['active' => true]);
    }
}
