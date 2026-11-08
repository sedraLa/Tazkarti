<?php

namespace App\Jobs;

use App\Services\BookingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExpireBookingReservation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 5;

    public function __construct(public int $bookingId)
    {
        $this->onQueue('reservations');
    }

    public function handle(BookingService $bookingService): void
    {
        $bookingService->expireReservation($this->bookingId);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error("Failed to expire booking reservation [ID: {$this->bookingId}] after {$this->tries} attempts.", [
            'booking_id' => $this->bookingId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
