<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $events = Event::published()
            ->city($request->query('city'))
            ->eventType($request->query('event_type'))
            ->date($request->query('date'), $request->query('to_date'))
            ->with(['venue', 'organizer', 'images'])
            ->latest('start_at')
            ->paginate($request->integer('per_page', 15));

        return EventResource::collection($events);
    }

    public function upcoming(Request $request): AnonymousResourceCollection
    {
        $events = Event::published()
            ->upcoming()
            ->city($request->query('city'))
            ->eventType($request->query('event_type'))
            ->with(['venue', 'organizer', 'images'])
            ->orderBy('start_at')
            ->paginate($request->integer('per_page', 15));

        return EventResource::collection($events);
    }

    public function featured(Request $request): AnonymousResourceCollection
    {
        $events = Event::published()
            ->featured()
            ->with(['venue', 'organizer', 'images'])
            ->orderBy('start_at')
            ->paginate($request->integer('per_page', 10));

        return EventResource::collection($events);
    }

    public function show(Event $event): EventResource
    {
        if ($event->status !== EventStatus::PUBLISHED) {
            abort(404, 'الفعالية غير موجودة أو غير منشورة.');
        }

        $event->load(['venue', 'organizer', 'images', 'ticketTypes']);

        return new EventResource($event);
    }
}
