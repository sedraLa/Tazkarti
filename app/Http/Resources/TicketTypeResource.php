<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'name' => $this->name,
            'category' => $this->category?->value ?? $this->category,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'quantity' => (int) $this->quantity,
            'reserved_quantity' => (int) $this->reserved_quantity,
            'sold_quantity' => (int) $this->sold_quantity,
            'available_quantity' => (int) $this->available_quantity,
            'max_per_order' => (int) $this->max_per_order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
