<?php

namespace App\Models;

use App\Dns\DomainDnsLookup;
use App\Enums\DnsLookupStatus;
use App\Http\Resources\DomainResource;
use App\Traits\HasEncryptedAttributes;
use App\Traits\HasUuid;
use Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class Domain extends Model
{
    use HasEncryptedAttributes;
    use HasFactory;
    use HasUuid;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $encrypted = [
        'description',
        'from_name',
    ];

    protected $fillable = [
        'domain',
        'description',
        'from_name',
        'active',
        'catch_all',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'active' => 'boolean',
        'catch_all' => 'boolean',
        'default_recipient_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'domain_verified_at' => 'datetime',
        'domain_mx_validated_at' => 'datetime',
        'domain_sending_verified_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        Domain::deleting(function ($domain) {
            $domain->aliases()->withTrashed()->forceDelete();
        });
    }

    /**
     * Set the domain's name.
     */
    protected function domain(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower($value),
        );
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
     * Disable catch-all for the domain.
     */
    public function disableCatchAll()
    {
        $this->update(['catch_all' => false]);
    }

    /**
     * Enable catch-all for the domain.
     */
    public function enableCatchAll()
    {
        $this->update(['catch_all' => true]);
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
     * Determine if the domain is verified for sending.
     *
     * @return bool
     */
    public function isVerifiedForSending()
    {
        return ! is_null($this->domain_sending_verified_at);
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
     * Mark this domain as verified for sending.
     *
     * @return bool
     */
    public function markDomainAsVerifiedForSending()
    {
        return $this->forceFill([
            'domain_sending_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Mark this domain as having valid MX records.
     *
     * @return bool
     */
    public function markDomainAsValidMx()
    {
        return $this->forceFill([
            'domain_mx_validated_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Checks if the domain has the correct records.
     */
    public function checkVerification()
    {
        if (App::environment('testing')) {
            return true;
        }

        try {
            return collect(dns_get_record($this->domain.'.', DNS_TXT))
                ->contains(function ($r) {
                    return trim($r['txt']) === 'aa-verify='.sha1(config('anonaddy.secret').user()->id.user()->domains->count());
                });
        } catch (Exception $e) {
            Log::info('DNS Get TXT Error:', ['domain' => $this->domain, 'user' => $this->user?->username, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Checks if the domain has the correct MX records.
     */
    public function checkMxRecords()
    {
        if (App::environment('testing')) {
            return true;
        }

        try {
            $mx = collect(dns_get_record($this->domain.'.', DNS_MX))
                ->sortBy('pri')
                ->first();
        } catch (Exception $e) {
            Log::info('DNS Get MX Error:', ['domain' => $this->domain, 'user' => $this->user?->username, 'error' => $e->getMessage()]);

            // If an error occurs then do not unverify
            if (! is_null($this->domain_mx_validated_at)) {
                return true;
            }

            return false;
        }

        if (! isset($mx['target'])) {
            return false;
        }

        if ($mx['target'] !== config('anonaddy.hostname')) {
            return false;
        }

        $this->markDomainAsValidMx();

        return true;
    }

    /**
     * Checks if the domain has the correct records for sending.
     */
    public function checkVerificationForSending()
    {
        if ($this->shouldBypassSendingDnsChecks()) {
            $this->markDomainAsVerifiedForSending();

            return response()->json([
                'success' => true,
                'message' => 'Records verified for sending.',
                'data' => new DomainResource($this->fresh()),
            ]);
        }

        $selector = config('anonaddy.dkim_selector');

        $checks = [
            [
                'label' => 'SPF',
                'name' => $this->domain.'.',
                'type' => DNS_TXT,
                'missing' => 'SPF record not found. This could be due to DNS caching, please try again later.',
                'matcher' => fn (array $record) => preg_match("/^(v=spf1).*(include:spf\.".config('anonaddy.domain').'|mx).*(-|~)all$/', $record['txt'] ?? ''),
            ],
            [
                'label' => 'DMARC',
                'name' => '_dmarc.'.$this->domain.'.',
                'type' => DNS_TXT,
                'missing' => 'DMARC record not found. This could be due to DNS caching, please try again later.',
                'matcher' => fn (array $record) => preg_match('/^(v=DMARC1).*(p=quarantine|reject).*/', $record['txt'] ?? ''),
            ],
            [
                'label' => 'DKIM',
                'name' => $selector.'._domainkey.'.$this->domain.'.',
                'type' => DNS_CNAME,
                'missing' => 'CNAME '.$selector.'._domainkey record not found. This could be due to DNS caching, please try again later.',
                'matcher' => fn (array $record) => ($record['target'] ?? null) === $selector.'._domainkey.'.config('anonaddy.domain'),
            ],
        ];

        foreach ($checks as $check) {
            $status = $this->lookupSendingDnsRecord($check['name'], $check['type'], $check['matcher'], $check['label']);

            if ($status === DnsLookupStatus::Failed) {
                return $this->sendingDnsLookupFailedResponse();
            }

            if ($status === DnsLookupStatus::Missing) {
                return response()->json([
                    'success' => false,
                    'message' => $check['missing'],
                    'data' => new DomainResource($this->fresh()),
                ]);
            }
        }

        $this->markDomainAsVerifiedForSending();

        return response()->json([
            'success' => true,
            'message' => 'Records successfully verified.',
            'data' => new DomainResource($this->fresh()),
        ]);
    }

    /**
     * Skip live DNS lookups in tests unless a test opts in.
     */
    protected function shouldBypassSendingDnsChecks(): bool
    {
        return App::environment('testing') && ! config('anonaddy.run_dns_checks');
    }

    /**
     * @param  callable(array<string, mixed>): bool  $matcher
     */
    protected function lookupSendingDnsRecord(string $name, int $type, callable $matcher, string $logLabel): DnsLookupStatus
    {
        try {
            $records = app(DomainDnsLookup::class)->getRecords($name, $type);
        } catch (Exception $e) {
            Log::info("DNS Get {$logLabel} Error:", [
                'domain' => $this->domain,
                'user' => $this->user?->username,
                'error' => $e->getMessage(),
            ]);

            return DnsLookupStatus::Failed;
        }

        if ($records === false) {
            Log::info("DNS Get {$logLabel} Error:", [
                'domain' => $this->domain,
                'user' => $this->user?->username,
                'error' => 'dns_get_record returned false',
            ]);

            return DnsLookupStatus::Failed;
        }

        if (collect($records)->contains($matcher)) {
            return DnsLookupStatus::Found;
        }

        return DnsLookupStatus::Missing;
    }

    protected function sendingDnsLookupFailedResponse()
    {
        if ($this->isVerifiedForSending()) {
            return response()->json([
                'success' => true,
                'dns_error' => true,
                'message' => 'DNS lookup failed. Domain left verified for sending.',
                'data' => new DomainResource($this->fresh()),
            ]);
        }

        return response()->json([
            'success' => false,
            'dns_error' => true,
            'message' => 'DNS lookup failed. This could be due to DNS caching, please try again later.',
            'data' => new DomainResource($this->fresh()),
        ]);
    }
}
