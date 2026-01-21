<?php

namespace App\Models;

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
            return getVerificationRecords()->isNotEmpty();
        } catch (Exception $e) {
            Log::info('DNS Get TXT Error:', ['domain' => $this->domain, 'user' => $this->user?->username, 'error' => $e->getMessage()]);

            return false;
        }
    }

    private function getVerificationValue()
    {
        return 'aa-verify='.sha1(config('anonaddy.secret').user()->id.user()->domains->count()
    }

    private function getVerificationRecords()
    {
        $value = $this->getVerificationValue()  // no need to recompute this value multiple times
        return collect(dns_get_record($this->domain.'.', DNS_TXT))
                ->filter(function ($r) {
                    return trim($r['txt']) === $value);
                });
    }

    private function getMxValue()
    {
        return config('anonaddy.hostname');
    }

    private function getMxRecord()
    {
        return collect(dns_get_record($this->domain.'.', DNS_MX))
                ->sortBy('pri')
                ->first();
    }

    private function getSpfExample()
    {
        return 'v=spf1 include:spf.'.config('anonaddy.domain').' mx -all';
    }

    private function getSpfRegex()
    {
        return "/^(v=spf1).*(include:spf\.".config('anonaddy.domain').'|mx).*(-|~)all$/'
    }

    private function getSpfRecords()
    {
        $spfRegex = $this->getSpfRegex();  // no need to recompute this value multiple times
        return collect(dns_get_record($this->domain.'.', DNS_TXT))
                ->filter(function ($r) {
                    return preg_match(
                        $spfRegex,
                        $r['txt'],
                    );
                });
    }

    private function getDmarcExample()
    {
        return 'v=DMARC1; p=quarantine; adkim=s'
    }

    private function getDmarcRegex()
    {
        return '/^(v=DMARC1).*(p=quarantine|reject).*/'
    }

    function getDmarcRecords()
    {
        return collect(dns_get_record('_dmarc.'.$this->domain.'.', DNS_TXT))
                ->filter(function ($r) {
                    return preg_match(
                        $this->getDmarcRegex(),
                        $r['txt'],
                    );
                })
    }

    /**
     * Returns the subdomain part of a host name.
     *
     * @param string $host Fully‑qualified domain name (e.g. "api.blog.example.co.uk")
     * @return string Subdomain string, or "@" if the host is the apex domain.
     */
    public static function getSubdomain(string $host): string
    {
        // Normalise: lower‑case and trim trailing dot
        $host = strtolower(rtrim($host, '.'));

        // Split into labels
        $labels = explode('.', $host);

        // A list of public‑suffix rules (simplified).  In production you’d use
        // the Mozilla Public Suffix List (PSL) via a library such as
        // jeremeamia/php-domain-parser.
        $publicSuffixes = [
            'com', 'org', 'net', 'edu', 'gov',
            'co.uk', 'gov.uk', 'ac.uk',
            'co.jp', 'ne.jp', 'or.jp',
            // add more as needed
        ];

        // Determine the effective top‑level domain (eTLD)
        $eTld = '';
        for ($i = 1; $i <= count($labels); $i++) {
            $candidate = implode('.', array_slice($labels, -$i));
            if (in_array($candidate, $publicSuffixes, true)) {
                $eTld = $candidate;
            }
        }

        // If we didn't match a known suffix, fall back to the last label
        if ($eTld === '') {
            $eTld = $labels[count($labels) - 1];
        }

        // Remove the eTLD and the immediate label before it (the registered domain)
        $registeredDomainIdx = count($labels) - count(explode('.', $eTld)) - 1;
        $subdomainParts = array_slice($labels, 0, $registeredDomainIdx + 1);

        // No subdomain → return "@"
        return $subdomainParts ? implode('.', $subdomainParts) : '@';
    }

    /**
     Format:
        ```ts
        type RequiredRecordsResponse = {
            records: RequiredRecord[];
            all_dns_records: DnsRecord[] | string; // string in case of error retrieving DNS records
        }

        type DnsRecord = DnsRecordTxt | DnsRecordMx | DnsRecordCname;
        type DnsRecordBase = {
            host: string;
            class: string;
            ttl: number;
        };
        type DnsRecordTxt = DnsRecordBase & {
            type: 'TXT';
            txt: string;
        };
        type DnsRecordMx = DnsRecordBase & {
            type: 'MX';
            target: string;
            pri: number;
        };
        type DnsRecordCname = DnsRecordBase & {
            type: 'CNAME';
            target: string;
        };

        type RequiredRecord = VerificationRecord | MailServerRecord | SpfRecord;
        type VerificationRecord = {
            label: 'verification';
            type: 'TXT';
            expected: string; // expected verification value
            got: DnsRecordTxt[] | string;  // string in case of error retrieving DNS records
            check: boolean | null; // whether the expected record was found
            help: undefined; // no help text for mail server record
        };
        type MailServerRecord = {
            label: 'mail server';
            type: 'MX';
            expected: string; // expected mail server value
            got: DnsRecordMx | string;  // string in case of error retrieving DNS records
            check: boolean | null; // whether the expected record was found
            help: undefined; // no help text for mail server record
        };
        type SpfRecord = {
            label: 'SPF';
            type: 'TXT';
            expected: string; // expected SPF value
            got: DnsRecordTxt[] | string;  // string in case of error retrieving DNS records
            check: boolean | null; // whether the expected record was found
            help: string; // help text for SPF record format
        };
        type DmarcRecord = {
            label: 'sender verification ';
            type: 'TXT';
            key: string; // DMARC record key
            expected: string; // expected DMARC value
            got: DnsRecordTxt[] | string;  // string in case of error retrieving DNS records
            check: boolean | null; // whether the expected record was found
            help: string; // help text for DMARC record format
        };
        ```
     */
    public function requiredRecords()
    {
        $all_dns_records = null;
        try {
            $all_dns_records = dns_get_record($this->domain.'.', DNS_ALL);
        } catch (Exception $e) {
            $all_dns_records = 'Error retrieving DNS records: '.$e->getMessage();
        }

        try {
            $v = $this->getVerificationRecords()
            $verification = $v->asArray();
            $hasVerification = $v->isNotEmpty();
        } catch (Exception $e) {
            $verification = 'Error retrieving verification records: '.$e->getMessage();
            $hasVerification = false;
        }

        $mxValue = $this->getMxValue()
        try {
            $mx = $this->getMxRecord()
            $hasMX = isset($mx['target']) && $mx['target'] === ;
        } catch (Exception $e) {
            $mx = 'Error retrieving MX records: '.$e->getMessage();
            $hasMX = null;
        }

        $spfValue = $this->getSpfExample()
        try {
            $spf = $this->getSpfRecords()
            $hasSpf = $spf->isNotEmpty();
        } catch (Exception $e) {
            $spf = 'Error retrieving SPF records: '.$e->getMessage();
            $hasSpf = null;
        }

        $dmarcValue = $this->getDmarcExample()
        try {
            $dmarc = $this->getDmarcRecords()
            $hasDmarc = $dmarc->isNotEmpty();
        } catch (Exception $e) {
            $dmarc = 'Error retrieving DMARC records: '.$e->getMessage();
            $hasSpf = null;
        }

        $host = getSubdomain()

        // Return the records and whether the verification record was found
        return [
            'records' => [
                [
                    'label' => 'verification (needed only once)',
                    'type' => 'TXT',
                    'host' => $host,
                    'expected' => $this->getVerificationValue(),
                    'got' => $verification,
                    'check' => $hasVerification,
                    'help' => 'This value is needed only once to proof that you have authority over that domain before adding it to your account.'
                ],
                [
                    'label' => 'mail server (addy)',
                    'type' => 'MX',
                    'host' => $host,
                    'expected' => $mxValue,
                    'got' => $mx,
                    'check' => $hasMX,
                ],
                [
                    'label' => 'sender host verification (SPF)',
                    'type' => 'TXT',
                    'host' => $host,
                    'expected' => $spfValue,
                    'got' => $spf,
                    'check' => $hasSpf,
                    'help' => 'Given is a possible example, the SPF record should comply to the following regex: '.$this->getSpfRegex(),
                ],
                [
                    'label' => 'failed verification policy (DMARC)',
                    'type' => 'TXT',
                    'host' => $host === '@' ? '_dmarc' : '_dmarc.'.$host,
                    'expected' => $dmarcValue,
                    'got' => $dmarc,
                    'check' => $hasDmarc,
                    'help' => 'The DMARC record should comply to the following regex: '.$this->getDmarcRegex(),
                ],

            ],
            'all_dns_records' => $all_dns_records,
        ];
    }}

    /**
     * Checks if the domain has the correct MX records.
     */
    public function checkMxRecords()
    {
        if (App::environment('testing')) {
            return true;
        }

        try {
            $mx = this->getMxRecord()
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

        if ($mx['target'] !== $this->getMxValue()) {
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
        if (App::environment('testing')) {
            return response()->json([
                'success' => true,
                'message' => 'Records verified for sending.',
                'data' => new DomainResource($this->fresh()),
            ]);
        }

        try {
            $spf = $this->getSpfRecords()->isNotEmpty();
        } catch (Exception $e) {
            Log::info('DNS Get SPF Error:', ['domain' => $this->domain, 'user' => $this->user?->username, 'error' => $e->getMessage()]);

            $spf = null;
        }

        if (! $spf) {
            return response()->json([
                'success' => false,
                'message' => 'SPF record not found. This could be due to DNS caching, please try again later.',
                'data' => new DomainResource($this->fresh()),
            ]);
        }

        try {
            $dmarc = collect(dns_get_record('_dmarc.'.$this->domain.'.', DNS_TXT))
                ->contains(function ($r) {
                    return preg_match('/^(v=DMARC1).*(p=quarantine|reject).*/', $r['txt']);
                });
        } catch (Exception $e) {
            Log::info('DNS Get DMARC Error:', ['domain' => $this->domain, 'user' => $this->user?->username, 'error' => $e->getMessage()]);

            $dmarc = null;
        }

        if (! $dmarc) {
            return response()->json([
                'success' => false,
                'message' => 'DMARC record not found. This could be due to DNS caching, please try again later.',
                'data' => new DomainResource($this->fresh()),
            ]);
        }

        try {
            $dkim = collect(dns_get_record(config('anonaddy.dkim_selector').'._domainkey.'.$this->domain.'.', DNS_CNAME))
                ->contains(function ($r) {
                    return $r['target'] === config('anonaddy.dkim_selector').'._domainkey.'.config('anonaddy.domain');
                });
        } catch (Exception $e) {
            Log::info('DNS Get DKIM Error:', ['domain' => $this->domain, 'user' => $this->user?->username, 'error' => $e->getMessage()]);

            $dkim = null;
        }

        if (! $dkim) {
            return response()->json([
                'success' => false,
                'message' => 'CNAME '.config('anonaddy.dkim_selector').'._domainkey record not found. This could be due to DNS caching, please try again later.',
                'data' => new DomainResource($this->fresh()),
            ]);
        }

        $this->markDomainAsVerifiedForSending();

        return response()->json([
            'success' => true,
            'message' => 'Records successfully verified.',
            'data' => new DomainResource($this->fresh()),
        ]);
    }
}
