<?php

namespace App\Services\Email;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class LogEmailProvider implements EmailProviderInterface
{
    public function sendPurchaseConfirmation(Booking $booking, ?string $idempotencyKey = null): bool
    {
        if (config('services.email.simulate_failure', false)) {
            throw new RuntimeException('Simulated email provider failure for testing.');
        }

        Log::info("Purchase confirmation email sent to {$booking->user->email} for Booking #{$booking->id}.", [
            'booking_id' => $booking->id,
            'recipient' => $booking->user->email,
            'event' => $booking->event?->title,
            'tickets_count' => $booking->tickets->count(),
            'total_amount' => $booking->total_amount,
            'idempotency_key' => $idempotencyKey,
        ]);

        return true;
    }

    public function sendBookingCancellation(Booking $booking, ?string $idempotencyKey = null): bool
    {
        if (config('services.email.simulate_failure', false)) {
            throw new RuntimeException('Simulated email provider failure for testing.');
        }

        Log::info("Booking cancellation email sent to {$booking->user->email} for Booking #{$booking->id}.", [
            'booking_id' => $booking->id,
            'recipient' => $booking->user->email,
            'event' => $booking->event?->title,
            'total_amount' => $booking->total_amount,
            'status' => $booking->status?->value ?? $booking->status,
            'idempotency_key' => $idempotencyKey,
        ]);

        return true;
    }
}
