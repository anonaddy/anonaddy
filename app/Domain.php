<?php

namespace App;

use App\Traits\HasEncryptedAttributes;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Dns\Dns;

class Domain extends Model
{
    use HasUuid, HasEncryptedAttributes;

    public $incrementing = false;

    protected $encrypted = [
        'description'
    ];

    protected $fillable = [
        'domain',
        'description',
        'active'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'domain_verified_at'
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'active' => 'boolean'
    ];

    /**
     * Set the domain's name.
     */
    public function setDomainAttribute($value)
    {
        $this->attributes['domain'] = strtolower($value);
    }

    /**
     * Get the user for the custom domain.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all of the domains's aliases.
     */
    public function aliases()
    {
        return $this->hasMany(Alias::class);
    }

    /**
     * Deactivate the domain.
     */
    public function deactivate()
    {
        $this->update(['active' => false]);
    }

    /**
     * Activate the domain.
     */
    public function activate()
    {
        $this->update(['active' => true]);
    }

    /**
     * Determine if the domain is verified.
     *
     * @return bool
     */
    public function isVerified()
    {
        return ! is_null($this->domain_verified_at);
    }

    /**
     * Mark this domain as verified.
     *
     * @return bool
     */
    public function markDomainAsVerified()
    {
        return $this->forceFill([
            'domain_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Checks if the domain has the correct records.
     *
     * @return void
     */
    public function checkVerification()
    {
        $dns = new Dns($this->domain, '1.1.1.1');

        if (Str::contains($dns->getRecords('MX'), 'MX 10 mail.anonaddy.me.')) {
            $this->markDomainAsVerified();
        }
    }
}
