<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RecipientResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'email' => $this->email,
            'can_reply_send' => $this->can_reply_send,
            'should_encrypt' => $this->should_encrypt,
            'inline_encryption' => $this->inline_encryption,
            'protected_headers' => $this->protected_headers,
            'fingerprint' => $this->fingerprint,
            'email_verified_at' => $this->email_verified_at?->toDateTimeString(),
            'aliases' => AliasResource::collection($this->whenLoaded('aliases')),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
