<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FailedDeliveryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'recipient_id' => $this->recipient_id,
            'recipient_email' => $this->recipient_id ? $this->recipient->email : null,
            'alias_id' => $this->alias_id,
            'alias_email' => $this->alias_id ? $this->alias->email : null,
            'bounce_type' => $this->bounce_type,
            'remote_mta' => $this->remote_mta,
            'sender' => $this->sender,
            'email_type' => $this->email_type,
            'status' => $this->status,
            'code' => $this->code,
            'attempted_at' => $this->attempted_at?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
