<?php

namespace App\Jobs;

use App\Services\BookingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireBookingReservation implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $bookingId)
    {
    }

    public function handle(BookingService $bookingService): void
    {
        $bookingService->expireReservation($this->bookingId);
    }
}
