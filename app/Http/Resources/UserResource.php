<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        $totals = $this
            ->aliases()
            ->withTrashed()
            ->toBase()
            ->selectRaw('ifnull(sum(emails_forwarded),0) as forwarded')
            ->selectRaw('ifnull(sum(emails_blocked),0) as blocked')
            ->selectRaw('ifnull(sum(emails_replied),0) as replied')
            ->selectRaw('ifnull(sum(emails_sent),0) as sent')
            ->first();

        return [
            'id' => $this->id,
            'username' => $this->username,
            'from_name' => $this->from_name,
            'email_subject' => $this->email_subject,
            'banner_location' => $this->banner_location,
            'bandwidth' => $this->bandwidth,
            'username_count' => $this->username_count,
            'default_username_id' => $this->default_username_id,
            'default_recipient_id' => $this->default_recipient_id,
            'default_alias_domain' => $this->default_alias_domain,
            'default_alias_format' => $this->default_alias_format,
            'recipient_count' => $this->recipients()->count(),
            'active_domain_count' => $this->domains()->where('active', true)->count(),
            'active_shared_domain_alias_count' => $this->activeSharedDomainAliases()->count(),
            'total_emails_forwarded' => (int) $totals->forwarded,
            'total_emails_blocked' => (int) $totals->blocked,
            'total_emails_replied' => (int) $totals->replied,
            'total_emails_sent' => (int) $totals->sent,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
