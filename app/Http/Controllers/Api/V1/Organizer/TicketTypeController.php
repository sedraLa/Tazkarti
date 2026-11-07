<?php

namespace App\Http\Controllers\Api\V1\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketTypeRequest;
use App\Http\Requests\UpdateTicketTypeRequest;
use App\Http\Resources\TicketTypeResource;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketTypeController extends Controller
{
    public function index(Request $request, Event $event): AnonymousResourceCollection
    {
        $this->authorize('update', $event);

        $ticketTypes = $event->ticketTypes()->paginate($request->integer('per_page', 15));

        return TicketTypeResource::collection($ticketTypes);
    }

    public function store(StoreTicketTypeRequest $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $ticketType = $event->ticketTypes()->create($request->validated());

        return (new TicketTypeResource($ticketType))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateTicketTypeRequest $request, ?Event $event = null, ?TicketType $ticketType = null): TicketTypeResource
    {
        // Handle both nested and shallow route parameters
        $ticket = $ticketType ?? $this->resolveTicketTypeFromRoute();

        if ($event && $ticket->event_id !== $event->id) {
            abort(404, 'نوع التذكرة غير موجود في الفعالية المحددة.');
        }

        $this->authorize('update', $ticket);

        $ticket->update($request->validated());

        return new TicketTypeResource($ticket);
    }

    public function destroy(Request $request, ?Event $event = null, ?TicketType $ticketType = null): JsonResponse
    {
        $ticket = $ticketType ?? $this->resolveTicketTypeFromRoute();

        if ($event && $ticket->event_id !== $event->id) {
            abort(404, 'نوع التذكرة غير موجود في الفعالية المحددة.');
        }

        $this->authorize('update', $ticket);

        if ($ticket->bookingItems()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف نوع التذكرة لوجود حجوزات أو تذاكر مسجلة عليه.',
            ], 422);
        }

        $ticket->delete();

        return response()->json([
            'message' => 'تم حذف نوع التذكرة بنجاح.',
        ], 200);
    }

    protected function resolveTicketTypeFromRoute(): TicketType
    {
        $param = request()->route('ticketType') ?? request()->route('ticket_type');
        if ($param instanceof TicketType) {
            return $param;
        }

        return TicketType::findOrFail($param);
    }
}
