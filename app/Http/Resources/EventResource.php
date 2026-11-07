<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'event_type' => $this->event_type,
            'start_at' => $this->start_at?->toISOString(),
            'end_at' => $this->end_at?->toISOString(),
            'status' => $this->status?->value ?? $this->status,
            'is_featured' => (bool) $this->is_featured,
            'venue' => new VenueResource($this->whenLoaded('venue')),
            'organizer' => new OrganizerResource($this->whenLoaded('organizer')),
            'ticket_types' => TicketTypeResource::collection($this->whenLoaded('ticketTypes')),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
