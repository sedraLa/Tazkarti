<?php

namespace App\Services\Email;

use App\Models\Booking;

interface EmailProviderInterface
{
    public function sendPurchaseConfirmation(Booking $booking, ?string $idempotencyKey = null): bool;

    public function sendBookingCancellation(Booking $booking, ?string $idempotencyKey = null): bool;
}
