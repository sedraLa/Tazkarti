<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\RefundStatus;
use App\Enums\TicketStatus;
use App\Events\BookingCancelled;
use App\Events\EventCancelled;
use App\Events\PurchaseConfirmed;
use App\Jobs\ExpireBookingReservation;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Refund;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function createReservation(User $user, int $eventId, array $items, ?string $idempotencyKey = null): Booking
    {
        // Aggregate items by ticket_type_id to prevent bypassing limits
        $aggregatedItems = collect($items)
            ->groupBy('ticket_type_id')
            ->map(function ($group) {
                return [
                    'ticket_type_id' => (int) $group->first()['ticket_type_id'],
                    'quantity' => (int) $group->sum('quantity'),
                ];
            })
            ->values()
            ->all();

        // Check idempotency key before transaction to avoid duplicate work
        if ($idempotencyKey) {
            $existing = Booking::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $this->validateAndReturnExistingBooking($existing, $user, $eventId, $aggregatedItems);
            }
        }

        try {
            return DB::transaction(function () use ($user, $eventId, $aggregatedItems, $idempotencyKey) {
                // Lock and verify event
                $event = Event::where('id', $eventId)->lockForUpdate()->first();
                if (! $event || $event->status !== EventStatus::PUBLISHED) {
                    abort(422, 'لا يمكن حجز تذاكر لفعالية غير منشورة.');
                }

                if ($event->start_at <= now()) {
                    abort(422, 'لا يمكن حجز تذاكر لفعالية بدأت بالفعل.');
                }

                // Lock ticket types in consistent sorted order to avoid deadlocks
                $ticketTypeIds = collect($aggregatedItems)->pluck('ticket_type_id')->sort()->values()->all();

                $ticketTypes = TicketType::whereIn('id', $ticketTypeIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($ticketTypes->count() !== count(array_unique($ticketTypeIds))) {
                    abort(422, 'نوع التذكرة المحدد غير موجود.');
                }

                foreach ($ticketTypes as $ticketType) {
                    if ($ticketType->event_id !== $event->id) {
                        abort(422, 'جميع أنواع التذاكر المختارة يجب أن تنتمي لنفس الفعالية.');
                    }
                }

                $totalAmount = 0;
                $currency = null;
                $bookingItemsData = [];

                foreach ($aggregatedItems as $item) {
                    $tt = $ticketTypes->get($item['ticket_type_id']);
                    $quantity = (int) $item['quantity'];

                    if ($quantity <= 0) {
                        abort(422, 'كمية التذاكر يجب أن تكون أكبر من الصفر.');
                    }

                    if ($quantity > $tt->max_per_order) {
                        abort(422, "الكمية المطلوبة لتذكرة '{$tt->name}' تتجاوز الحد الأقصى المسموح به لكل طلب ({$tt->max_per_order}).");
                    }

                    $available = $tt->quantity - $tt->reserved_quantity - $tt->sold_quantity;
                    if ($quantity > $available) {
                        abort(422, "الكمية المتاحة لتذكرة '{$tt->name}' غير كافية لإتمام الحجز.");
                    }

                    // Hold inventory
                    $tt->increment('reserved_quantity', $quantity);

                    $unitPrice = $tt->price;
                    $totalAmount += ($unitPrice * $quantity);
                    $currency = $currency ?? $tt->currency;

                    $bookingItemsData[] = [
                        'ticket_type_id' => $tt->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                    ];
                }

                $booking = Booking::create([
                    'user_id' => $user->id,
                    'event_id' => $event->id,
                    'status' => BookingStatus::HELD,
                    'expires_at' => now()->addMinutes(10),
                    'total_amount' => $totalAmount,
                    'currency' => $currency ?? 'USD',
                    'idempotency_key' => $idempotencyKey,
                ]);

                foreach ($bookingItemsData as $itemData) {
                    $booking->bookingItems()->create($itemData);
                }

                // Dispatch delayed expiration job and invalidate cache after commit
                DB::afterCommit(function () use ($booking, $ticketTypeIds) {
                    foreach ($ticketTypeIds as $id) {
                        TicketType::invalidateAvailableQuantityCache($id);
                    }
                    ExpireBookingReservation::dispatch($booking->id)->delay(now()->addMinutes(10));
                });

                return $booking->load(['bookingItems.ticketType', 'event.venue']);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle race condition on unique idempotency_key
            if ($idempotencyKey && ($e->getCode() === '23000' || ($e->errorInfo[1] ?? 0) === 1062)) {
                $existing = Booking::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $this->validateAndReturnExistingBooking($existing, $user, $eventId, $aggregatedItems);
                }
            }

            throw $e;
        }
    }

    public function confirmBooking(User $user, Booking $booking): Booking
    {
        $shouldExpire = false;

        $confirmedBooking = DB::transaction(function () use ($user, $booking, &$shouldExpire) {
            $lockedBooking = Booking::where('id', $booking->id)->lockForUpdate()->first();
            if (! $lockedBooking) {
                abort(404, 'الحجز غير موجود.');
            }

            if ($lockedBooking->user_id !== $user->id) {
                abort(403, 'غير مصرح لك بتأكيد هذا الحجز.');
            }

            if ($lockedBooking->status === BookingStatus::PAID) {
                abort(422, 'الحجز مؤكد ومدفوع بالفعل.');
            }

            if ($lockedBooking->status !== BookingStatus::HELD) {
                abort(422, 'لا يمكن تأكيد الحجز لأن حالته ليست معلقة.');
            }

            if ($lockedBooking->expires_at && $lockedBooking->expires_at <= now()) {
                $shouldExpire = true;
                return null;
            }

            // Lock all ticket types in ascending ID order to prevent deadlocks
            $ticketTypeIds = $lockedBooking->bookingItems->pluck('ticket_type_id')->unique()->sort()->values()->all();
            $ticketTypes = TicketType::whereIn('id', $ticketTypeIds)->lockForUpdate()->get()->keyBy('id');

            // Move inventory from reserved to sold and generate tickets
            foreach ($lockedBooking->bookingItems as $item) {
                $tt = $ticketTypes->get($item->ticket_type_id);
                if ($tt) {
                    $decrementAmount = min($item->quantity, $tt->reserved_quantity);
                    $tt->decrement('reserved_quantity', $decrementAmount);
                    $tt->increment('sold_quantity', $item->quantity);
                    $tt->refresh();
                }

                for ($i = 0; $i < $item->quantity; $i++) {
                    Ticket::create([
                        'booking_item_id' => $item->id,
                        'ticket_code' => $this->generateUniqueTicketCode(),
                        'status' => TicketStatus::VALID,
                        'generated_at' => now(),
                    ]);
                }
            }

            $lockedBooking->update([
                'status' => BookingStatus::PAID,
                'expires_at' => null,
            ]);

            DB::afterCommit(function () use ($lockedBooking, $ticketTypeIds) {
                foreach ($ticketTypeIds as $id) {
                    TicketType::invalidateAvailableQuantityCache($id);
                }
                PurchaseConfirmed::dispatch($lockedBooking->id);
            });

            return $lockedBooking;
        });

        if ($shouldExpire) {
            $this->expireReservation($booking->id);
            abort(422, 'انتهت صلاحية هذا الحجز المؤقت ولم يعد قابلاً للتأكيد.');
        }

        return $confirmedBooking->load(['bookingItems.ticketType', 'bookingItems.tickets', 'tickets', 'event.venue']);
    }

    public function expireReservation(int $bookingId): ?Booking
    {
        return DB::transaction(function () use ($bookingId) {
            $booking = Booking::where('id', $bookingId)->lockForUpdate()->first();
            if (! $booking) {
                return null;
            }

            if ($booking->status !== BookingStatus::HELD) {
                return $booking;
            }

            if ($booking->expires_at && $booking->expires_at > now()) {
                return $booking;
            }

            // Lock all ticket types in ascending ID order to prevent deadlocks
            $ticketTypeIds = $booking->bookingItems->pluck('ticket_type_id')->unique()->sort()->values()->all();
            $ticketTypes = TicketType::whereIn('id', $ticketTypeIds)->lockForUpdate()->get()->keyBy('id');

            foreach ($booking->bookingItems as $item) {
                $tt = $ticketTypes->get($item->ticket_type_id);
                if ($tt) {
                    $decrementAmount = min($item->quantity, $tt->reserved_quantity);
                    if ($decrementAmount > 0) {
                        $tt->decrement('reserved_quantity', $decrementAmount);
                        $tt->refresh();
                    }
                }
            }

            $booking->update([
                'status' => BookingStatus::EXPIRED,
            ]);

            DB::afterCommit(function () use ($ticketTypeIds) {
                foreach ($ticketTypeIds as $id) {
                    TicketType::invalidateAvailableQuantityCache($id);
                }
            });

            return $booking;
        });
    }

    public function cancelBooking(?User $user, Booking $booking, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($user, $booking, $reason) {
            $lockedBooking = Booking::where('id', $booking->id)->lockForUpdate()->first();
            if (! $lockedBooking) {
                abort(404, 'الحجز غير موجود.');
            }

            if (in_array($lockedBooking->status, [BookingStatus::CANCELLED, BookingStatus::REFUNDED, BookingStatus::EXPIRED])) {
                abort(422, 'لا يمكن إلغاء هذا الحجز نظراً لحالته الحالية.');
            }

            if ($lockedBooking->status === BookingStatus::HELD && $lockedBooking->expires_at && $lockedBooking->expires_at <= now()) {
                $this->expireReservation($lockedBooking->id);
                abort(422, 'انتهت صلاحية هذا الحجز المؤقت ولا يمكن إلغاؤه.');
            }

            if (! in_array($lockedBooking->status, [BookingStatus::HELD, BookingStatus::PAID])) {
                abort(422, 'حالة الحجز غير صالحة للإلغاء.');
            }

            // Lock event and check start time
            $event = Event::where('id', $lockedBooking->event_id)->lockForUpdate()->first();
            if (! $event || ($event->status !== EventStatus::CANCELLED && $event->start_at <= now())) {
                abort(422, 'لا يمكن إلغاء الحجز بعد بدء الفعالية.');
            }

            // Check if any ticket is USED (unless whole event was cancelled)
            $bookingItemIds = $lockedBooking->bookingItems()->pluck('id');
            $hasUsedTickets = Ticket::whereIn('booking_item_id', $bookingItemIds)
                ->where('status', TicketStatus::USED)
                ->exists();

            if ($event->status !== EventStatus::CANCELLED && $hasUsedTickets) {
                abort(422, 'لا يمكن إلغاء أو استرجاع حجز يحتوي على تذاكر مستخدمة.');
            }

            // Lock ticket types in ascending ID order to prevent deadlocks
            $ticketTypeIds = $lockedBooking->bookingItems->pluck('ticket_type_id')->unique()->sort()->values()->all();
            $ticketTypes = TicketType::whereIn('id', $ticketTypeIds)->lockForUpdate()->get()->keyBy('id');

            if ($lockedBooking->status === BookingStatus::HELD) {
                foreach ($lockedBooking->bookingItems as $item) {
                    $tt = $ticketTypes->get($item->ticket_type_id);
                    if ($tt) {
                        $decrementAmount = min($item->quantity, $tt->reserved_quantity);
                        if ($decrementAmount > 0) {
                            $tt->decrement('reserved_quantity', $decrementAmount);
                            $tt->refresh();
                        }
                    }
                }

                $lockedBooking->update([
                    'status' => BookingStatus::CANCELLED,
                    'expires_at' => null,
                ]);
            } elseif ($lockedBooking->status === BookingStatus::PAID) {
                foreach ($lockedBooking->bookingItems as $item) {
                    $tt = $ticketTypes->get($item->ticket_type_id);
                    if ($tt) {
                        $decrementAmount = min($item->quantity, $tt->sold_quantity);
                        if ($decrementAmount > 0) {
                            $tt->decrement('sold_quantity', $decrementAmount);
                            $tt->refresh();
                        }
                    }
                }

                Ticket::whereIn('booking_item_id', $bookingItemIds)
                    ->where('status', TicketStatus::VALID)
                    ->update([
                        'status' => TicketStatus::CANCELLED,
                    ]);

                $existingRefund = Refund::where('booking_id', $lockedBooking->id)->first();
                if (! $existingRefund) {
                    Refund::create([
                        'booking_id' => $lockedBooking->id,
                        'amount' => $lockedBooking->total_amount,
                        'status' => RefundStatus::COMPLETED,
                        'reason' => $reason ?? 'Booking cancelled',
                        'refunded_at' => now(),
                    ]);
                }

                $lockedBooking->update([
                    'status' => BookingStatus::REFUNDED,
                ]);
            }

            DB::afterCommit(function () use ($lockedBooking, $ticketTypeIds) {
                foreach ($ticketTypeIds as $id) {
                    TicketType::invalidateAvailableQuantityCache($id);
                }
                BookingCancelled::dispatch($lockedBooking->id);
            });

            return $lockedBooking->load(['bookingItems.ticketType', 'bookingItems.tickets', 'tickets', 'event.venue', 'refunds']);
        });
    }

    public function cancelEvent(Event $event, ?string $reason = null): Event
    {
        return DB::transaction(function () use ($event, $reason) {
            $lockedEvent = Event::where('id', $event->id)->lockForUpdate()->first();
            if (! $lockedEvent) {
                abort(404, 'الفعالية غير موجودة.');
            }

            if ($lockedEvent->status === EventStatus::CANCELLED) {
                abort(422, 'الفعالية ملغاة بالفعل.');
            }

            if ($lockedEvent->status !== EventStatus::PUBLISHED) {
                abort(422, 'لا يمكن إلغاء فعالية غير منشورة.');
            }

            $lockedEvent->update([
                'status' => EventStatus::CANCELLED,
            ]);

            DB::afterCommit(function () use ($lockedEvent) {
                Event::invalidateUpcomingCache();
                EventCancelled::dispatch($lockedEvent->id);
            });

            return $lockedEvent;
        });
    }

    protected function validateAndReturnExistingBooking(Booking $existing, User $user, int $eventId, array $aggregatedItems): Booking
    {
        if ($existing->user_id !== $user->id) {
            abort(422, 'مفتاح التحقق من التكرار مستخدم من قبل مستخدم آخر.');
        }

        if ($existing->event_id !== $eventId) {
            abort(422, 'مفتاح التحقق من التكرار مستخدم مسبقاً لفعالية مختلفة.');
        }

        $existingItems = $existing->bookingItems
            ->sortBy('ticket_type_id')
            ->map(fn ($item) => ['ticket_type_id' => $item->ticket_type_id, 'quantity' => (int) $item->quantity])
            ->values()
            ->all();

        $requestedItems = collect($aggregatedItems)
            ->sortBy('ticket_type_id')
            ->map(fn ($item) => ['ticket_type_id' => (int) $item['ticket_type_id'], 'quantity' => (int) $item['quantity']])
            ->values()
            ->all();

        if ($existingItems !== $requestedItems) {
            abort(422, 'مفتاح التحقق من التكرار مستخدم مسبقاً بتفاصيل تذاكر مختلفة.');
        }

        return $existing->load(['bookingItems.ticketType', 'event.venue']);
    }

    protected function generateUniqueTicketCode(): string
    {
        do {
            $code = 'TCK-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        } while (Ticket::where('ticket_code', $code)->exists());

        return $code;
    }
}
