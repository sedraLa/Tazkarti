<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $bookings = $request->user()
            ->bookings()
            ->with(['event.venue', 'bookingItems.ticketType', 'tickets'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return BookingResource::collection($bookings);
    }

    public function store(StoreBookingRequest $request, BookingService $bookingService): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        $booking = $bookingService->createReservation(
            $request->user(),
            (int) $request->input('event_id'),
            $request->input('items'),
            $idempotencyKey
        );

        return (new BookingResource($booking))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Booking $booking): BookingResource
    {
        $this->authorize('view', $booking);

        $booking->load(['event.venue', 'bookingItems.ticketType', 'tickets']);

        return new BookingResource($booking);
    }

    public function confirm(Request $request, Booking $booking, BookingService $bookingService): BookingResource
    {
        $this->authorize('confirm', $booking);

        $confirmedBooking = $bookingService->confirmBooking($request->user(), $booking);

        return new BookingResource($confirmedBooking);
    }
}
