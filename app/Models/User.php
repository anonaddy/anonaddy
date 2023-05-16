<?php

namespace App\Models;

use App\Notifications\CustomVerifyEmail;
use App\Traits\HasEncryptedAttributes;
use App\Traits\HasUuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;
    use HasUuid;
    use HasEncryptedAttributes;
    use HasApiTokens;
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'from_name',
        'email_subject',
        'banner_location',
        'catch_all',
        'bandwidth',
        'default_alias_domain',
        'default_alias_format',
        'use_reply_to',
        'store_failed_deliveries',
        'default_username_id',
        'default_recipient_id',
        'password',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_backup_code',
    ];

    protected $encrypted = [
        'from_name',
        'email_subject',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_backup_code',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'string',
        'default_username_id' => 'string',
        'default_recipient_id' => 'string',
        'catch_all' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'use_reply_to' => 'boolean',
        'store_failed_deliveries' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the user's default email.
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->defaultRecipient->email,
        );
    }

    /**
     * Get the user's default email verified_at.
     */
    protected function emailVerifiedAt(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->defaultRecipient->email_verified_at,
        );
    }

    /**
     * Set the user's default email verified_at.
     */
    public function setEmailVerifiedAtAttribute($value)
    {
        $this->defaultRecipient->update(['email_verified_at' => $value]);
    }

    /**
     * Get the user's default username.
     */
    protected function username(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->defaultUsername->username,
        );
    }

    /**
     * Set the user's default username.
     */
    public function setDefaultUsernameAttribute($username)
    {
        $this->attributes['default_username_id'] = $username->id;
        $this->setRelation('defaultUsername', $username);
    }

    /**
     * Set the user's default email.
     */
    public function setDefaultRecipientAttribute($recipient)
    {
        $this->attributes['default_recipient_id'] = $recipient->id;
        $this->setRelation('defaultRecipient', $recipient);
    }

    /**
     * Get the user's bandwidth in MB.
     */
    protected function bandwidthMb(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->bandwidth / 1024 / 1024, 2),
        );
    }

    /**
     * Get the user's default username.
     */
    public function defaultUsername()
    {
        return $this->hasOne(Username::class, 'id', 'default_username_id');
    }

    /**
     * Get the user's default recipient.
     */
    public function defaultRecipient()
    {
        return $this->hasOne(Recipient::class, 'id', 'default_recipient_id');
    }

    /**
     * Get all of the user's email aliases.
     */
    public function aliases()
    {
        return $this->hasMany(Alias::class);
    }

    /**
     * Get all of the user's recipients.
     */
    public function recipients()
    {
        return $this->hasMany(Recipient::class);
    }

    /**
     * Get all of the user's custom domains.
     */
    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * Get all of the user's rules.
     */
    public function rules()
    {
        return $this->hasMany(Rule::class);
    }

    /**
     * Get all of the user's failed deliveries.
     */
    public function failedDeliveries()
    {
        return $this->hasMany(FailedDelivery::class);
    }

    /**
     * Get all of the user's active rules.
     */
    public function activeRules()
    {
        return $this->rules()->where('active', true);
    }

    /**
     * Get all of the user's active rules in the correct order.
     */
    public function activeRulesOrdered()
    {
        return $this->rules()->where('active', true)->orderBy('order');
    }

    /**
     * Get all of the user's active rules in the correct order that should be run of forwards.
     */
    public function activeRulesForForwardsOrdered()
    {
        return $this->rules()->where('active', true)->where('forwards', true)->orderBy('order');
    }

    /**
     * Get all of the user's active rules in the correct order that should be run of forwards.
     */
    public function activeRulesForRepliesOrdered()
    {
        return $this->rules()->where('active', true)->where('replies', true)->orderBy('order');
    }

    /**
     * Get all of the user's active rules in the correct order that should be run of forwards.
     */
    public function activeRulesForSendsOrdered()
    {
        return $this->rules()->where('active', true)->where('sends', true)->orderBy('order');
    }

    /**
     * Get all of the user's usernames.
     */
    public function Usernames()
    {
        return $this->hasMany(Username::class);
    }

    /**
     * Get all of the user's webauthn keys.
     */
    public function webauthnKeys()
    {
        return $this->hasMany(WebauthnKey::class);
    }

    /**
     * Get all of the user's verified recipients.
     */
    public function verifiedRecipients()
    {
        return $this->recipients()->whereNotNull('email_verified_at');
    }

    /**
     * Get all of the user's verified domains.
     */
    public function verifiedDomains()
    {
        return $this->domains()->whereNotNull('domain_verified_at');
    }

    /**
     * Get all of the alias recipient pivot rows for the user.
     */
    public function aliasRecipients()
    {
        return $this->hasManyThrough(AliasRecipient::class, Alias::class);
    }

    /**
     * Get all of the user's aliases using a shared domain.
     */
    public function sharedDomainAliases()
    {
        return $this->aliases()->whereIn('domain', config('anonaddy.all_domains'));
    }

    /**
     * Get all of the user's active aliases using a shared domain.
     */
    public function activeSharedDomainAliases()
    {
        return $this->sharedDomainAliases()->where('active', true);
    }

    /**
     * Get all of the user's aliases that are using the default recipient
     */
    public function aliasesUsingDefault()
    {
        return $this->aliases()->whereDoesntHave('recipients')->where(function (Builder $q) {
            return $q->whereDoesntHaveMorph(
                'aliasable',
                ['App\Models\Domain', 'App\Models\Username'],
                function (Builder $query) {
                    $query->whereNotNull('default_recipient_id');
                }
            )->orWhereNull('aliasable_id');
        });
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

    public function hasVerifiedDefaultRecipient()
    {
        return ! is_null($this->defaultRecipient->email_verified_at);
    }

    public function totalEmailsForwarded()
    {
        return $this->aliases()->withTrashed()->sum('emails_forwarded');
    }

    public function totalEmailsBlocked()
    {
        return $this->aliases()->withTrashed()->sum('emails_blocked');
    }

    public function totalEmailsReplied()
    {
        return $this->aliases()->withTrashed()->sum('emails_replied');
    }

    public function totalEmailsSent()
    {
        return $this->aliases()->withTrashed()->sum('emails_sent');
    }

    public function getBandwidthLimit()
    {
        return config('anonaddy.bandwidth_limit');
    }

    public function getBandwidthLimitMb()
    {
        return round($this->getBandwidthLimit() / 1024 / 1024, 2);
    }

    public function nearBandwidthLimit()
    {
        return ($this->bandwidth / $this->getBandwidthLimit()) > 0.9;
    }

    public function hasReachedBandwidthLimit()
    {
        return $this->bandwidth >= $this->getBandwidthLimit();
    }

    public function hasExceededNewAliasLimit()
    {
        if (App::environment('testing')) {
            return false;
        }

        return \Illuminate\Support\Facades\Redis::throttle("user:{$this->id}:limit:new-alias")
            ->allow(config('anonaddy.new_alias_hourly_limit'))
            ->every(3600)
            ->then(
                function () {
                    return false;
                },
                function () {
                    return true;
                }
            );
    }

    public function hasReachedUsernameLimit()
    {
        return $this->username_count >= config('anonaddy.additional_username_limit');
    }

    public function isVerifiedRecipient($email)
    {
        return $this
            ->verifiedRecipients()
            ->select(['id', 'user_id', 'email', 'email_verified_at'])
            ->get()
            ->map(function ($recipient) use ($email) {
                if (Str::contains($email, '+')) {
                    return strtolower($recipient->email);
                }

                $withoutExtension = preg_replace('/\+[\s\S]+(?=@)/', '', $recipient->email);

                return strtolower($withoutExtension);
            })
            ->contains(strtolower($email));
    }

    public function getVerifiedRecipientByEmail($email)
    {
        return $this
            ->verifiedRecipients()
            ->select(['id', 'user_id', 'email', 'can_reply_send', 'email_verified_at'])
            ->get()
            ->first(function ($recipient) use ($email) {
                if (Str::contains($email, '+')) {
                    $recipientEmail = strtolower($recipient->email);
                } else {
                    $recipientEmail = strtolower(preg_replace('/\+[\s\S]+(?=@)/', '', $recipient->email));
                }

                // Allow either pm.me or protonmail.com domains
                if (in_array(Str::afterLast($email, '@'), ['pm.me', 'protonmail.com'])) {
                    $localPart = Str::beforeLast($email, '@');

                    return in_array($recipientEmail, [strtolower($localPart.'@pm.me'), strtolower($localPart.'@protonmail.com')]);
                }

                return $recipientEmail === strtolower($email);
            });
    }

    public function deleteKeyFromKeyring($fingerprint): void
    {
        $gnupg = new \gnupg();

        $recipientsUsingFingerprint = $this
            ->recipients()
            ->get()
            ->where('fingerprint', $fingerprint);

        // Check that the user has a verified recipient matching the key's email and if any other recipients are using that key.
        if (isset($key[0])) {
            collect($key[0]['uids'])
                ->filter(function ($uid) {
                    return ! $uid['invalid'];
                })
                ->pluck('email')
                ->each(function ($email) use ($gnupg, $fingerprint, $recipientsUsingFingerprint) {
                    if ($this->isVerifiedRecipient($email) && $recipientsUsingFingerprint->count() === 1) {
                        $gnupg->deletekey($fingerprint);

                        $recipientsUsingFingerprint->first()->update([
                            'should_encrypt' => false,
                            'fingerprint' => null,
                        ]);
                    }
                });
        }
    }

    public function generateRandomWordLocalPart()
    {
        return collect(config('anonaddy.wordlist'))
            ->random(2)
            ->implode('.').mt_rand(0, 999);
    }

    public function generateRandomCharacterLocalPart(int $length): string
    {
        $alphabet = '0123456789abcdefghijklmnopqrstuvwxyz';

        $str = '';

        for ($i = 0; $i < $length; $i++) {
            $index = random_int(0, 35);
            $str .= $alphabet[$index];
        }

        return $str;
    }

    public function domainOptions()
    {
        $customDomains = $this->verifiedDomains()->pluck('domain')->toArray();

        $allDomains = config('anonaddy.all_domains')[0] ? config('anonaddy.all_domains') : [config('anonaddy.domain')];

        return $this->usernames()
            ->pluck('username')
            ->flatMap(function ($username) use ($allDomains) {
                return collect($allDomains)->map(function ($domain) use ($username) {
                    return $username.'.'.$domain;
                });
            })
            ->concat($customDomains)
            ->concat($allDomains)
            ->reverse()
            ->values();
    }
}
