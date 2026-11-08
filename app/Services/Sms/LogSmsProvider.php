<?php

namespace App\Services\Sms;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class LogSmsProvider implements SmsProviderInterface
{
    public function sendPurchaseConfirmation(Booking $booking, string $message, ?string $idempotencyKey = null): bool
    {
        if (config('services.sms.simulate_failure', false)) {
            throw new RuntimeException('Simulated SMS provider failure for testing.');
        }

        Log::info("Purchase confirmation SMS sent to {$booking->user->phone} for Booking #{$booking->id}.", [
            'booking_id' => $booking->id,
            'recipient' => $booking->user->phone,
            'message' => $message,
            'idempotency_key' => $idempotencyKey,
        ]);

        return true;
    }

    public function sendBookingCancellation(Booking $booking, string $message, ?string $idempotencyKey = null): bool
    {
        if (config('services.sms.simulate_failure', false)) {
            throw new RuntimeException('Simulated SMS provider failure for testing.');
        }

        Log::info("Booking cancellation SMS sent to {$booking->user->phone} for Booking #{$booking->id}.", [
            'booking_id' => $booking->id,
            'recipient' => $booking->user->phone,
            'message' => $message,
            'idempotency_key' => $idempotencyKey,
        ]);

        return true;
    }
}
