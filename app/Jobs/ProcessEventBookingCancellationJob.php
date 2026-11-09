<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessEventBookingCancellationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public int $bookingId)
    {
        $this->onQueue('bulk');
    }

    public function handle(BookingService $bookingService): void
    {
        $booking = Booking::with(['user', 'event'])->find($this->bookingId);

        if (! $booking) {
            return;
        }

        if (! in_array($booking->status, [BookingStatus::HELD, BookingStatus::PAID])) {
            return;
        }

        try {
            $bookingService->cancelBooking(
                user: $booking->user,
                booking: $booking,
                reason: 'Event cancelled by organizer'
            );
        } catch (Throwable $e) {
            Log::error("Failed to cancel booking [ID: {$this->bookingId}] for cancelled event [ID: {$booking->event_id}]: " . $e->getMessage());
            throw $e;
        }
    }
}
