<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DomainResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'domain' => $this->domain,
            'description' => $this->description,
            'aliases' => AliasResource::collection($this->whenLoaded('aliases')),
            'default_recipient' => new RecipientResource($this->whenLoaded('defaultRecipient')),
            'active' => $this->active,
            'catch_all' => $this->catch_all,
            'domain_verified_at' => $this->domain_verified_at?->toDateTimeString(),
            'domain_mx_validated_at' => $this->domain_mx_validated_at?->toDateTimeString(),
            'domain_sending_verified_at' => $this->domain_sending_verified_at?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
