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
            ->selectRaw('ifnull(count(id),0) as total')
            ->selectRaw('ifnull(sum(active=1),0) as active')
            ->selectRaw('ifnull(sum(CASE WHEN active=0 AND deleted_at IS NULL THEN 1 END),0) as inactive')
            ->selectRaw('ifnull(sum(CASE WHEN deleted_at IS NOT NULL THEN 1 END),0) as deleted')
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
            'active_rule_count' => $this->activeRules()->count(),
            'total_emails_forwarded' => (int) $totals->forwarded,
            'total_emails_blocked' => (int) $totals->blocked,
            'total_emails_replied' => (int) $totals->replied,
            'total_emails_sent' => (int) $totals->sent,
            'total_aliases' => (int) $totals->total,
            'total_active_aliases' => (int) $totals->active,
            'total_inactive_aliases' => (int) $totals->inactive,
            'total_deleted_aliases' => (int) $totals->deleted,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
