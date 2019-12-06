<?php

namespace App;

use App\Http\Resources\DomainResource;
use App\Traits\HasEncryptedAttributes;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    use HasUuid, HasEncryptedAttributes;

    public $incrementing = false;

    protected $keyType = 'string';

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
        'active' => 'boolean',
        'default_recipient_id' => 'string',
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
        return $this->morphMany(Alias::class, 'aliasable');
    }

    /**
     * Get the domains's default recipient.
     */
    public function defaultRecipient()
    {
        return $this->hasOne(Recipient::class, 'id', 'default_recipient_id');
    }

    /**
     * Set the domains's default recipient.
     */
    public function setDefaultRecipientAttribute($recipient)
    {
        $this->attributes['default_recipient_id'] = $recipient->id;
        $this->setRelation('defaultRecipient', $recipient);
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
        $records = collect(dns_get_record($this->domain . '.', DNS_MX));

        $lowestPriority = $records->groupBy('pri')->sortKeys()->first();

        if ($lowestPriority->count() !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Please make sure you do not have any other MX records with the same priority.'
            ]);
        }

        // Check the target for the lowest priority record is correct.
        if ($lowestPriority->first()['target'] === 'mail.anonaddy.me') {
            $this->markDomainAsVerified();
            return response()->json([
                'success' => true,
                'message' => 'MX Record successfully verified.',
                'data' => new DomainResource($this->fresh())
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Record not found. This could be due to DNS caching, please try again later.'
        ]);
    }
}
