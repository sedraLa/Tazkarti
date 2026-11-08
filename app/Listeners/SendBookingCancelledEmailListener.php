<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Jobs\SendBookingCancelledEmailJob;

class SendBookingCancelledEmailListener
{
    public function handle(BookingCancelled $event): void
    {
        SendBookingCancelledEmailJob::dispatch($event->bookingId);
    }
}
