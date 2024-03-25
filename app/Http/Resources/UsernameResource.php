<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UsernameResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'username' => $this->username,
            'description' => $this->description,
            'from_name' => $this->from_name,
            'aliases' => [],
            'aliases_count' => $this->whenCounted('aliases_count'),
            'default_recipient' => new RecipientResource($this->whenLoaded('defaultRecipient')),
            'active' => $this->active,
            'catch_all' => $this->catch_all,
            'can_login' => $this->can_login,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
