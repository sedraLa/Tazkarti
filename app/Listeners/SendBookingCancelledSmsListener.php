<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Jobs\SendBookingCancelledSmsJob;

class SendBookingCancelledSmsListener
{
    public function handle(BookingCancelled $event): void
    {
        SendBookingCancelledSmsJob::dispatch($event->bookingId);
    }
}
