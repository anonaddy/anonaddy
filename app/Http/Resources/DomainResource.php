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
            'aliases' => $this->aliases,
            'active' => $this->active,
            'domain_verified_at' => $this->domain_verified_at ? $this->domain_verified_at->toDateTimeString() : null,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
