<?php

namespace App\Http\Controllers\Api\V1\Organizer;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendEventAnnouncementRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventAnnouncementResource;
use App\Http\Resources\EventResource;
use App\Jobs\ProcessEventAnnouncementJob;
use App\Models\Event;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        if ($user->role === UserRole::CUSTOMER || (! $user->organizer && $user->role !== UserRole::ADMIN)) {
            abort(403, 'غير مصرح لك: حسابك ليس حساب منظم فعاليات.');
        }

        $query = $user->role === UserRole::ADMIN
            ? Event::query()
            : $user->organizer->events();

        $events = $query
            ->with(['organizer', 'venue', 'images', 'ticketTypes'])
            ->latest('start_at')
            ->paginate($request->integer('per_page', 15));

        return EventResource::collection($events);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $organizer = $request->user()->organizer;

        $event = $organizer->events()->create($request->validated());
        $event->load(['organizer', 'venue', 'images', 'ticketTypes']);

        return (new EventResource($event))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Event $event): EventResource
    {
        $this->authorize('view', $event);

        $event->load(['organizer', 'venue', 'images', 'ticketTypes']);

        return new EventResource($event);
    }

    public function update(UpdateEventRequest $request, Event $event): EventResource
    {
        $this->authorize('update', $event);

        $event->update($request->validated());
        $event->load(['organizer', 'venue', 'images', 'ticketTypes']);

        return new EventResource($event);
    }

    public function destroy(Request $request, Event $event): JsonResponse
    {
        $this->authorize('delete', $event);

        // If the event has bookings, tickets, or refund records, preserve history and mark as cancelled
        if ($event->bookings()->exists()) {
            $event->update(['status' => EventStatus::CANCELLED]);

            return response()->json([
                'message' => 'تم إلغاء الفعالية والحفاظ على سجل الحجوزات والتذاكر.',
                'data' => new EventResource($event->load(['organizer', 'venue', 'images', 'ticketTypes'])),
            ], 200);
        }

        // Only events with no bookings can be permanently deleted
        $event->ticketTypes()->delete();
        $event->delete();

        return response()->json([
            'message' => 'تم حذف الفعالية بنجاح.',
        ], 200);
    }

    public function cancel(Request $request, Event $event, BookingService $bookingService): JsonResponse
    {
        $this->authorize('cancel', $event);

        $cancelledEvent = $bookingService->cancelEvent($event, $request->input('reason'));

        return response()->json([
            'message' => 'تم إلغاء الفعالية وبدء معالجة استرجاع الحجوزات.',
            'data' => new EventResource($cancelledEvent->load(['organizer', 'venue', 'images', 'ticketTypes'])),
        ], 200);
    }

    public function sendAnnouncement(SendEventAnnouncementRequest $request, Event $event): JsonResponse
    {
        if ($event->status !== EventStatus::PUBLISHED) {
            abort(422, 'لا يمكن إرسال إعلان لفعالية غير منشورة.');
        }

        $announcement = DB::transaction(function () use ($request, $event) {
            $announcement = $event->announcements()->create($request->validated());

            DB::afterCommit(function () use ($announcement) {
                ProcessEventAnnouncementJob::dispatch($announcement->id);
            });

            return $announcement;
        });

        return (new EventAnnouncementResource($announcement))
            ->response()
            ->setStatusCode(201);
    }
}
