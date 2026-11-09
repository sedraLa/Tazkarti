<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CancelEventBookingsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $eventId)
    {
        $this->onQueue('bulk');
    }

    public function handle(): void
    {
        Booking::where('event_id', $this->eventId)
            ->whereIn('status', [BookingStatus::HELD, BookingStatus::PAID])
            ->chunkById(100, function ($bookings) {
                foreach ($bookings as $booking) {
                    ProcessEventBookingCancellationJob::dispatch($booking->id);
                }
            });
    }
}
