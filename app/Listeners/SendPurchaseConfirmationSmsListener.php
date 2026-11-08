<?php

namespace App\Listeners;

use App\Events\PurchaseConfirmed;
use App\Jobs\SendPurchaseConfirmationSmsJob;

class SendPurchaseConfirmationSmsListener
{
    public function handle(PurchaseConfirmed $event): void
    {
        SendPurchaseConfirmationSmsJob::dispatch($event->bookingId);
    }
}
