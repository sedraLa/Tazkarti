<?php

namespace App\Jobs;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Booking;
use App\Models\NotificationDelivery;
use App\Services\Email\EmailProviderInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendBookingCancelledEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 5;

    public function __construct(public int $bookingId)
    {
        $this->onQueue('notifications');
    }

    public function handle(EmailProviderInterface $emailProvider): void
    {
        $booking = Booking::with(['user', 'event.venue', 'tickets', 'bookingItems.ticketType', 'refunds'])->find($this->bookingId);

        if (! $booking) {
            Log::warning("Booking cancellation email skipped: Booking [ID: {$this->bookingId}] not found.");
            return;
        }

        try {
            $delivery = NotificationDelivery::firstOrCreate(
                [
                    'booking_id' => $booking->id,
                    'channel' => NotificationChannel::EMAIL,
                    'notification_type' => 'booking_cancellation',
                ],
                [
                    'recipient' => $booking->user->email,
                    'status' => NotificationStatus::PENDING,
                    'attempts' => 0,
                ]
            );
        } catch (QueryException $e) {
            $delivery = NotificationDelivery::where('booking_id', $booking->id)
                ->where('channel', NotificationChannel::EMAIL)
                ->where('notification_type', 'booking_cancellation')
                ->first();
        }

        if (! $delivery) {
            Log::error("Could not obtain notification delivery record for booking [ID: {$this->bookingId}].");
            return;
        }

        if ($delivery->status === NotificationStatus::SENT) {
            return;
        }

        // Atomic claim: only claim if status is pending, failed, or abandoned processing (lease expired)
        // Lease timeout is 60s, aligned between job timeout (30s) and Redis retry_after (90s)
        $leaseThreshold = now()->subSeconds(60);

        $claimed = NotificationDelivery::where('id', $delivery->id)
            ->where(function ($query) use ($leaseThreshold) {
                $query->whereIn('status', [NotificationStatus::PENDING, NotificationStatus::FAILED])
                    ->orWhere(function ($q) use ($leaseThreshold) {
                        $q->where('status', NotificationStatus::PROCESSING)
                          ->where('updated_at', '<=', $leaseThreshold);
                    });
            })
            ->update([
                'status' => NotificationStatus::PROCESSING,
                'attempts' => DB::raw('attempts + 1'),
                'updated_at' => now(),
            ]);

        if ($claimed === 0) {
            $delivery->refresh();

            if ($delivery->status === NotificationStatus::SENT) {
                return;
            }

            $elapsedSeconds = $delivery->updated_at ? (int) $delivery->updated_at->diffInSeconds(now()) : 0;
            $remainingDelay = max(10, (60 - $elapsedSeconds) + 5);

            $this->release($remainingDelay);

            return;
        }

        $delivery->refresh();

        $idempotencyKey = "booking-{$booking->id}-email-booking_cancellation";

        try {
            $emailProvider->sendBookingCancellation($booking, $idempotencyKey);

            $delivery->update([
                'status' => NotificationStatus::SENT,
                'sent_at' => now(),
                'last_error' => null,
            ]);
        } catch (Throwable $e) {
            $delivery->update([
                'status' => NotificationStatus::FAILED,
                'last_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $delivery = NotificationDelivery::where('booking_id', $this->bookingId)
            ->where('channel', NotificationChannel::EMAIL)
            ->where('notification_type', 'booking_cancellation')
            ->first();

        if ($delivery && $delivery->status !== NotificationStatus::SENT) {
            $delivery->update([
                'status' => NotificationStatus::FAILED,
                'last_error' => $exception?->getMessage(),
            ]);
        }

        Log::error("Booking cancellation email delivery permanently failed for booking ID: {$this->bookingId}", [
            'booking_id' => $this->bookingId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
