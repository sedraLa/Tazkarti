<?php

namespace App\Services\Sms;

use App\Models\Booking;

interface SmsProviderInterface
{
    public function sendPurchaseConfirmation(Booking $booking, string $message, ?string $idempotencyKey = null): bool;
}
