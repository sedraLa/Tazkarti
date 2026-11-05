<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Enums\RefundStatus;
use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Event;
use App\Models\NotificationDelivery;
use App\Models\Refund;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('password123');

        // 1. Standard bookings for Alexandria Tech Summit
        $techEvent = Event::where('title', 'Alexandria Tech Summit 2026')->first();
        $regularTechTicket = $techEvent->ticketTypes()->where('category', TicketCategory::REGULAR)->first();
        $vipTechTicket = $techEvent->ticketTypes()->where('category', TicketCategory::VIP)->first();

        $customer1 = User::where('email', 'customer1@tazkarti.com')->first();
        $customer2 = User::where('email', 'customer2@tazkarti.com')->first();
        $customer3 = User::where('email', 'customer3@tazkarti.com')->first();
        $customer4 = User::where('email', 'customer4@tazkarti.com')->first();
        $customer5 = User::where('email', 'customer5@tazkarti.com')->first();

        // Customer 1: Paid booking (2 Regular tickets)
        $booking1 = Booking::create([
            'user_id' => $customer1->id,
            'event_id' => $techEvent->id,
            'status' => BookingStatus::PAID,
            'expires_at' => null,
            'total_amount' => 100.00,
            'currency' => 'USD',
            'idempotency_key' => 'idemp_' . Str::random(16),
        ]);

        $item1 = BookingItem::create([
            'booking_id' => $booking1->id,
            'ticket_type_id' => $regularTechTicket->id,
            'quantity' => 2,
            'unit_price' => 50.00,
        ]);

        for ($t = 1; $t <= 2; $t++) {
            Ticket::create([
                'booking_item_id' => $item1->id,
                'ticket_code' => 'TCK-TECH-REG-' . Str::upper(Str::random(8)),
                'status' => TicketStatus::VALID,
                'generated_at' => $now,
            ]);
        }

        NotificationDelivery::create([
            'booking_id' => $booking1->id,
            'channel' => NotificationChannel::EMAIL,
            'notification_type' => 'booking_confirmation',
            'recipient' => $customer1->email,
            'status' => NotificationStatus::SENT,
            'attempts' => 1,
            'sent_at' => $now,
        ]);

        NotificationDelivery::create([
            'booking_id' => $booking1->id,
            'channel' => NotificationChannel::SMS,
            'notification_type' => 'booking_confirmation',
            'recipient' => $customer1->phone,
            'status' => NotificationStatus::SENT,
            'attempts' => 1,
            'sent_at' => $now,
        ]);

        // Customer 2: Active Held booking (1 Regular ticket reserved)
        $booking2 = Booking::create([
            'user_id' => $customer2->id,
            'event_id' => $techEvent->id,
            'status' => BookingStatus::HELD,
            'expires_at' => $now->copy()->addMinutes(15),
            'total_amount' => 50.00,
            'currency' => 'USD',
            'idempotency_key' => 'idemp_' . Str::random(16),
        ]);

        BookingItem::create([
            'booking_id' => $booking2->id,
            'ticket_type_id' => $regularTechTicket->id,
            'quantity' => 1,
            'unit_price' => 50.00,
        ]);

        // Customer 3: Expired booking (was holding 1 VIP ticket, now released)
        $booking3 = Booking::create([
            'user_id' => $customer3->id,
            'event_id' => $techEvent->id,
            'status' => BookingStatus::EXPIRED,
            'expires_at' => $now->copy()->subMinutes(20),
            'total_amount' => 150.00,
            'currency' => 'USD',
            'idempotency_key' => 'idemp_' . Str::random(16),
        ]);

        BookingItem::create([
            'booking_id' => $booking3->id,
            'ticket_type_id' => $vipTechTicket->id,
            'quantity' => 1,
            'unit_price' => 150.00,
        ]);

        // Customer 4: Paid booking (1 VIP ticket)
        $booking4 = Booking::create([
            'user_id' => $customer4->id,
            'event_id' => $techEvent->id,
            'status' => BookingStatus::PAID,
            'expires_at' => null,
            'total_amount' => 150.00,
            'currency' => 'USD',
            'idempotency_key' => 'idemp_' . Str::random(16),
        ]);

        $item4 = BookingItem::create([
            'booking_id' => $booking4->id,
            'ticket_type_id' => $vipTechTicket->id,
            'quantity' => 1,
            'unit_price' => 150.00,
        ]);

        Ticket::create([
            'booking_item_id' => $item4->id,
            'ticket_code' => 'TCK-TECH-VIP-' . Str::upper(Str::random(8)),
            'status' => TicketStatus::VALID,
            'generated_at' => $now,
        ]);

        NotificationDelivery::create([
            'booking_id' => $booking4->id,
            'channel' => NotificationChannel::EMAIL,
            'notification_type' => 'booking_confirmation',
            'recipient' => $customer4->email,
            'status' => NotificationStatus::SENT,
            'attempts' => 1,
            'sent_at' => $now,
        ]);

        // 2. Cancelled event with Refund: Pyramids Ancient Echoes Fest
        $cancelledEvent = Event::where('title', 'Pyramids Ancient Echoes Fest')->first();
        $cancelledTicketType = $cancelledEvent->ticketTypes()->where('category', TicketCategory::REGULAR)->first();

        $refundedBooking = Booking::create([
            'user_id' => $customer5->id,
            'event_id' => $cancelledEvent->id,
            'status' => BookingStatus::REFUNDED,
            'expires_at' => null,
            'total_amount' => 120.00,
            'currency' => 'USD',
            'idempotency_key' => 'idemp_' . Str::random(16),
        ]);

        $refundedItem = BookingItem::create([
            'booking_id' => $refundedBooking->id,
            'ticket_type_id' => $cancelledTicketType->id,
            'quantity' => 2,
            'unit_price' => 60.00,
        ]);

        for ($t = 1; $t <= 2; $t++) {
            Ticket::create([
                'booking_item_id' => $refundedItem->id,
                'ticket_code' => 'TCK-PYR-' . Str::upper(Str::random(8)),
                'status' => TicketStatus::CANCELLED,
                'generated_at' => $now->copy()->subDays(12),
            ]);
        }

        Refund::create([
            'booking_id' => $refundedBooking->id,
            'amount' => 120.00,
            'status' => RefundStatus::COMPLETED,
            'reason' => 'Event cancelled by organizer due to severe weather.',
            'refunded_at' => $now->copy()->subDays(9),
        ]);

        NotificationDelivery::create([
            'booking_id' => $refundedBooking->id,
            'channel' => NotificationChannel::EMAIL,
            'notification_type' => 'refund_notice',
            'recipient' => $customer5->email,
            'status' => NotificationStatus::SENT,
            'attempts' => 1,
            'sent_at' => $now->copy()->subDays(9),
        ]);

        // 3. Mega Event: Cairo Mega Festival 2026 (Thousands of ticket holders)
        $megaEvent = Event::where('title', 'Cairo Mega Festival 2026')->first();
        $megaGeneralTicket = $megaEvent->ticketTypes()->where('category', TicketCategory::REGULAR)->first();
        $megaVipTicket = $megaEvent->ticketTypes()->where('category', TicketCategory::VIP)->first();

        $totalGeneralHolders = 2500;
        $totalVipHolders = 50;
        $totalHolders = $totalGeneralHolders + $totalVipHolders;

        // Step 3a: Bulk insert customer users
        $userRows = [];
        for ($i = 1; $i <= $totalHolders; $i++) {
            $userRows[] = [
                'name' => "Festival Attendee {$i}",
                'email' => "attendee{$i}@cairomega.test",
                'password' => $password,
                'role' => UserRole::CUSTOMER->value,
                'phone' => sprintf('+201500%06d', $i),
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($userRows, 500) as $chunk) {
            User::insert($chunk);
        }

        $megaUsers = User::where('email', 'like', '%@cairomega.test')->orderBy('id')->get();

        // Step 3b: Bulk insert bookings
        $bookingRows = [];
        foreach ($megaUsers as $idx => $user) {
            $isVip = $idx >= $totalGeneralHolders;
            $price = $isVip ? 160.00 : 45.00;

            $bookingRows[] = [
                'user_id' => $user->id,
                'event_id' => $megaEvent->id,
                'status' => BookingStatus::PAID->value,
                'expires_at' => null,
                'total_amount' => $price,
                'currency' => 'USD',
                'idempotency_key' => "idemp_mega_{$user->id}",
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($bookingRows, 500) as $chunk) {
            Booking::insert($chunk);
        }

        $megaBookings = Booking::where('event_id', $megaEvent->id)->orderBy('id')->get();

        // Step 3c: Bulk insert booking items
        $itemRows = [];
        foreach ($megaBookings as $idx => $booking) {
            $isVip = $idx >= $totalGeneralHolders;
            $ticketTypeId = $isVip ? $megaVipTicket->id : $megaGeneralTicket->id;
            $unitPrice = $isVip ? 160.00 : 45.00;

            $itemRows[] = [
                'booking_id' => $booking->id,
                'ticket_type_id' => $ticketTypeId,
                'quantity' => 1,
                'unit_price' => $unitPrice,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($itemRows, 500) as $chunk) {
            BookingItem::insert($chunk);
        }

        $megaItems = BookingItem::whereIn('booking_id', $megaBookings->pluck('id'))->orderBy('id')->get();

        // Step 3d: Bulk insert tickets and notification deliveries
        $ticketRows = [];
        $notifRows = [];

        foreach ($megaItems as $idx => $item) {
            $ticketNumber = $idx + 1;
            $user = $megaUsers[$idx];

            $ticketRows[] = [
                'booking_item_id' => $item->id,
                'ticket_code' => sprintf('TCK-MEGA-%06d', $ticketNumber),
                'status' => TicketStatus::VALID->value,
                'generated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $notifRows[] = [
                'booking_id' => $item->booking_id,
                'channel' => NotificationChannel::EMAIL->value,
                'notification_type' => 'booking_confirmation',
                'recipient' => $user->email,
                'status' => NotificationStatus::SENT->value,
                'attempts' => 1,
                'sent_at' => $now,
                'last_error' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($ticketRows, 500) as $chunk) {
            Ticket::insert($chunk);
        }

        foreach (array_chunk($notifRows, 500) as $chunk) {
            NotificationDelivery::insert($chunk);
        }
    }
}
