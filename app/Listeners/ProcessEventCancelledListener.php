<?php

namespace App\Listeners;

use App\Events\EventCancelled;
use App\Jobs\CancelEventBookingsJob;

class ProcessEventCancelledListener
{
    public function handle(EventCancelled $event): void
    {
        CancelEventBookingsJob::dispatch($event->eventId);
    }
}
