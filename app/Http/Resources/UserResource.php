<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'from_name' => $this->from_name,
            'email_subject' => $this->email_subject,
            'banner_location' => $this->banner_location,
            'bandwidth' => $this->bandwidth,
            'default_recipient_id' => $this->default_recipient_id,
            'default_alias_domain' => $this->default_alias_domain,
            'default_alias_format' => $this->default_alias_format,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
