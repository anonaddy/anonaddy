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
            'aliases' => AliasResource::collection($this->whenLoaded('aliases')),
            'default_recipient' => new RecipientResource($this->whenLoaded('defaultRecipient')),
            'active' => $this->active,
            'catch_all' => $this->catch_all,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
