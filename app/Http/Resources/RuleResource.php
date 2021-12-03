<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RuleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'order' => $this->order,
            'conditions' => $this->conditions,
            'actions' => $this->actions,
            'operator' => $this->operator,
            'forwards' => $this->forwards,
            'replies' => $this->replies,
            'sends' => $this->sends,
            'active' => $this->active,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
