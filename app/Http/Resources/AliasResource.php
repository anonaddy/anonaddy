<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AliasResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'aliasable_id' => $this->aliasable_id,
            'aliasable_type' => $this->aliasable_type,
            'local_part' => $this->local_part,
            'extension' => $this->extension,
            'domain' => $this->domain,
            'email' => $this->email,
            'active' => $this->active,
            'description' => $this->description,
            'emails_forwarded' => $this->emails_forwarded,
            'emails_blocked' => $this->emails_blocked,
            'emails_replied' => $this->emails_replied,
            'emails_sent' => $this->emails_sent,
            'recipients' => RecipientResource::collection($this->whenLoaded('recipients')),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }
}
