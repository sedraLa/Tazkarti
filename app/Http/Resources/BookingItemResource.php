<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'ticket_type_id' => $this->ticket_type_id,
            'quantity' => (int) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'subtotal' => (float) ($this->quantity * $this->unit_price),
            'ticket_type' => new TicketTypeResource($this->whenLoaded('ticketType')),
            'tickets' => TicketResource::collection($this->whenLoaded('tickets')),
        ];
    }
}
