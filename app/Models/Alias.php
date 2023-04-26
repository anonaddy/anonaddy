<?php

namespace App\Models;

use App\Traits\HasEncryptedAttributes;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Alias extends Model
{
    use SoftDeletes;
    use HasUuid;
    use HasEncryptedAttributes;
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $encrypted = [
        'description',
    ];

    protected $fillable = [
        'id',
        'user_id',
        'active',
        'description',
        'email',
        'local_part',
        'extension',
        'domain',
        'aliasable_id',
        'aliasable_type',
        'emails_forwarded',
        'emails_blocked',
        'emails_replied',
        'emails_sent',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'aliasable_id' => 'string',
        'aliasable_type' => 'string',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        // Deactivate the alias when it is deleted
        Alias::deleting(function ($alias) {
            if ($alias->active) {
                $alias->deactivate();
            }
        });

        // Activate the alias when it is restored
        Alias::restoring(function ($alias) {
            $alias->activate();
        });
    }

    protected function localPart(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower($value),
        );
    }

    protected function domain(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower($value),
        );
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower($value),
        );
    }

    /**
     * Get the user for the email alias.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the owning aliasable model.
     */
    public function aliasable()
    {
        return $this->morphTo();
    }

    /**
     * Get the recipients for the email alias.
     */
    public function recipients()
    {
        return $this->belongsToMany(Recipient::class, 'alias_recipients')->withPivot('id')->using(AliasRecipient::class);
    }

    /**
     * Get all of the aliases' failed deliveries.
     */
    public function failedDeliveries()
    {
        return $this->hasMany(FailedDelivery::class);
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
        $verifiedRecipients = $this
            ->verifiedRecipients()
            ->get();

        if ($verifiedRecipients->count() === 0) {
            // If the alias is for a custom domain or username that has a default recipient set.
            if ($this->aliasable_id) {
                if (isset($this->aliasable->defaultRecipient)) {
                    return $this->aliasable->defaultRecipient();
                }
            }

            return $this->user->hasVerifiedDefaultRecipient() ? $this->user->defaultRecipient() : collect();
        }

        return $verifiedRecipients;
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

    public function isUuid()
    {
        return $this->id === $this->local_part;
    }

    public function hasSharedDomain()
    {
        return in_array($this->domain, config('anonaddy.all_domains'));
    }

    public function isCustomDomain()
    {
        return $this->aliasable_type === 'App\Models\Domain';
    }

    public function parentDomain()
    {
        return collect(config('anonaddy.all_domains'))
            ->filter(function ($name) {
                return Str::endsWith($this->domain, $name);
            })
            ->first();
    }
}
