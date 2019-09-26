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

    protected $keyType = 'string';

    protected $encrypted = [
        'description'
    ];

    protected $fillable = [
        'id',
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
    public function customDomain()
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }

    /**
     * Get the recipients for the email alias.
     */
    public function recipients()
    {
        return $this->belongsToMany(Recipient::class, 'alias_recipients')->withPivot('id')->using(AliasRecipient::class);
    }

    /**
     * Get all of the verified recipients for the email alias.
     */
    public function verifiedRecipients()
    {
        return $this->recipients()->whereNotNull('email_verified_at');
    }

    /**
     * Get the verified recipients for the email alias or the default recipient if none are set.
     */
    public function verifiedRecipientsOrDefault()
    {
        if ($this->verifiedRecipients()->count() === 0) {
            // If the alias is for a custom domain that has a default recipient set.
            if (isset($this->customDomain->defaultRecipient)) {
                return $this->customDomain->defaultRecipient();
            }

            return $this->user->defaultRecipient();
        }

        return $this
                ->verifiedRecipients()
                ->get();
    }

    /**
     * Deactivate the alias.
     */
    public function deactivate()
    {
        $this->update(['active' => false]);
    }

    /**
     * Activate the alias.
     */
    public function activate()
    {
        $this->update(['active' => true]);
    }
}
