<?php

namespace App\Listeners;

use App\Events\PurchaseConfirmed;
use App\Jobs\SendPurchaseConfirmationEmailJob;

class SendPurchaseConfirmationEmailListener
{
    public function handle(PurchaseConfirmed $event): void
    {
        SendPurchaseConfirmationEmailJob::dispatch($event->bookingId);
    }
}
