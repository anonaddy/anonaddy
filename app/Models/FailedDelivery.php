<?php

namespace App\Models;

use App\Mail\ForwardEmail;
use App\Traits\HasEncryptedAttributes;
use App\Traits\HasUuid;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpMimeMailParser\Parser;

class FailedDelivery extends Model
{
    use HasEncryptedAttributes;
    use HasFactory;
    use HasUuid;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $encrypted = [
        'sender',
        'destination',
    ];

    protected $fillable = [
        'id',
        'user_id',
        'recipient_id',
        'alias_id',
        'is_stored',
        'resent',
        'bounce_type',
        'remote_mta',
        'sender',
        'destination',
        'email_type',
        'status',
        'code',
        'attempted_at',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'recipient_id' => 'string',
        'alias_id' => 'string',
        'is_stored' => 'boolean',
        'resent' => 'boolean',
        'attempted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        FailedDelivery::deleting(function ($failedDelivery) {
            if ($failedDelivery->is_stored) {
                Storage::disk('local')->delete($failedDelivery->id.'.eml');
            }
        });
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Get the human readable email type.
     *
     * @param  string  $value
     * @return string
     */
    protected function emailType(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => match ($value) {
                'F' => 'Forward',
                'R' => 'Reply',
                'S' => 'Send',
                'RP' => 'Reset Password',
                'FDN' => 'Failed Delivery',
                'DMI' => 'Domain MX Invalid',
                'DRU' => 'Default Recipient Updated',
                'NRV' => 'New Recipient Verified',
                'FLA' => 'Failed Login Attempt',
                'TES' => 'Token Expiring Soon',
                'UR' => 'Username Reminder',
                'VR' => 'Verify Recipient',
                'VU' => 'Verify User',
                'DRSA' => 'Disallowed Reply/Send Attempt',
                'DUS' => 'Domain Unverified For Sending',
                'GKE' => 'PGP Key Expired',
                'NBL' => 'Near Bandwidth Limit',
                'RSL' => 'Reached Reply/Send Limit',
                'SRSA' => 'Spam Reply/Send Attempt',
                'AIF' => 'Aliases Import Finished',
                default => 'Forward',
            },
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => Str::ascii($value),
        );
    }

    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => Str::ascii($value),
        );
    }

    /**
     * Get the user for the failed delivery.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the recipient for the failed delivery.
     */
    public function recipient()
    {
        return $this->belongsTo(Recipient::class);
    }

    /**
     * Get the alias for the failed delivery.
     */
    public function alias()
    {
        return $this->belongsTo(Alias::class)->withTrashed();
    }

    public function resend($verifiedRecipientIds = null) // validate max 10.
    {
        // Validate max 10 recipient IDs
        if ($verifiedRecipientIds && is_array($verifiedRecipientIds) && count($verifiedRecipientIds) > 10) {
            abort(422, 'Maximum of 10 recipient IDs allowed');
        }

        if (! $this->is_stored || ! Storage::disk('local')->exists($this->id.'.eml')) {
            abort(404);
        }

        if ($this->resent) {
            abort(422, 'This failed delivery has already been resent');
        }

        if ($this->getRawOriginal('email_type') !== 'F' || ! $this->alias) {
            abort(422, 'Only messages with an email type of "Forward" can currently be resent');
        }

        $email = Storage::disk('local')->get($this->id.'.eml');

        if (! $email) {
            abort(404);
        }

        $parser = new Parser;

        $parser->setText($email);

        $emailData = new EmailData($parser, $this->sender, strlen($email), 'F', true);

        $isSpam = $parser->getHeader('X-AnonAddy-Spam') === 'Yes';

        if ($verifiedRecipientIds) {
            $recipients = $this->user->verifiedRecipients()->find($verifiedRecipientIds);
        } else {
            $recipients = $this->alias->verifiedRecipientsOrDefault();
        }

        $recipients->each(function ($aliasRecipient) use ($emailData, $isSpam) {
            $message = new ForwardEmail($this->alias, $emailData, $aliasRecipient, $isSpam, true);

            Mail::to($aliasRecipient->email)->queue($message);
        });

        $this->resent();

        return true;
    }

    public function resent()
    {
        $this->update(['resent' => true]);
    }
}
