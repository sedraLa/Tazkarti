<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'event_id' => $this->event_id,
            'status' => $this->status?->value ?? $this->status,
            'expires_at' => $this->expires_at?->toISOString(),
            'total_amount' => (float) $this->total_amount,
            'currency' => $this->currency,
            'idempotency_key' => $this->idempotency_key,
            'event' => new EventResource($this->whenLoaded('event')),
            'items' => BookingItemResource::collection($this->whenLoaded('bookingItems')),
            'tickets' => TicketResource::collection($this->whenLoaded('tickets')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
