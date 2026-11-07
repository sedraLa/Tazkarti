<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\TicketCategory;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $organizer1 = Organizer::first();
        $organizer2 = Organizer::skip(1)->first();

        $cairoVenue = Venue::where('city', 'Cairo')->first();
        $alexVenue = Venue::where('city', 'Alexandria')->first();
        $gizaVenue = Venue::where('city', 'Giza')->first();
        $hurghadaVenue = Venue::where('city', 'Hurghada')->first();

        // 1. Published Event: Alexandria Tech Summit
        $techEvent = Event::create([
            'organizer_id' => $organizer1->id,
            'venue_id' => $alexVenue->id,
            'title' => 'Alexandria Tech Summit 2026',
            'description' => 'Annual premier tech summit bringing together software engineers, founders, and investors.',
            'event_type' => 'Technology',
            'start_at' => now()->addDays(20),
            'end_at' => now()->addDays(22),
            'status' => EventStatus::PUBLISHED,
            'is_featured' => true,
        ]);

        $techEvent->images()->create([
            'path' => 'events/alex_tech_poster.jpg',
        ]);

        $techEvent->ticketTypes()->createMany([
            [
                'name' => 'Regular Pass',
                'category' => TicketCategory::REGULAR,
                'price' => 50.00,
                'currency' => 'USD',
                'quantity' => 200,
                'reserved_quantity' => 1,
                'sold_quantity' => 2,
                'max_per_order' => 5,
            ],
            [
                'name' => 'VIP Access',
                'category' => TicketCategory::VIP,
                'price' => 150.00,
                'currency' => 'USD',
                'quantity' => 50,
                'reserved_quantity' => 0,
                'sold_quantity' => 1,
                'max_per_order' => 2,
            ],
        ]);

        // 2. Draft Event: Nile Heritage Symphony
        $draftEvent = Event::create([
            'organizer_id' => $organizer2->id,
            'venue_id' => $alexVenue->id,
            'title' => 'Nile Heritage Symphony Night',
            'description' => 'Private rehearsal and preview concert of the Nile classical symphony orchestra.',
            'event_type' => 'Concert',
            'start_at' => now()->addDays(40),
            'end_at' => now()->addDays(40)->addHours(4),
            'status' => EventStatus::DRAFT,
        ]);

        $draftEvent->images()->create([
            'path' => 'events/nile_symphony_poster.jpg',
        ]);

        $draftEvent->ticketTypes()->createMany([
            [
                'name' => 'Regular Seating',
                'category' => TicketCategory::REGULAR,
                'price' => 30.00,
                'currency' => 'USD',
                'quantity' => 150,
                'reserved_quantity' => 0,
                'sold_quantity' => 0,
                'max_per_order' => 4,
            ],
            [
                'name' => 'VIP Balcony',
                'category' => TicketCategory::VIP,
                'price' => 80.00,
                'currency' => 'USD',
                'quantity' => 40,
                'reserved_quantity' => 0,
                'sold_quantity' => 0,
                'max_per_order' => 2,
            ],
        ]);

        // 3. Cancelled Event: Pyramids Ancient Echoes Fest
        $cancelledEvent = Event::create([
            'organizer_id' => $organizer1->id,
            'venue_id' => $gizaVenue->id,
            'title' => 'Pyramids Ancient Echoes Fest',
            'description' => 'Outdoor music experience at the Giza Plateau, cancelled due to adverse weather forecast.',
            'event_type' => 'Festival',
            'start_at' => now()->subDays(10),
            'end_at' => now()->subDays(9),
            'status' => EventStatus::CANCELLED,
        ]);

        $cancelledEvent->images()->create([
            'path' => 'events/pyramids_poster.jpg',
        ]);

        $cancelledEvent->ticketTypes()->createMany([
            [
                'name' => 'Standard Entry',
                'category' => TicketCategory::REGULAR,
                'price' => 60.00,
                'currency' => 'USD',
                'quantity' => 500,
                'reserved_quantity' => 0,
                'sold_quantity' => 2,
                'max_per_order' => 4,
            ],
            [
                'name' => 'VIP Circle',
                'category' => TicketCategory::VIP,
                'price' => 120.00,
                'currency' => 'USD',
                'quantity' => 50,
                'reserved_quantity' => 0,
                'sold_quantity' => 0,
                'max_per_order' => 2,
            ],
        ]);

        // 4. Postponed Event: El Gouna Film Nights
        $postponedEvent = Event::create([
            'organizer_id' => $organizer2->id,
            'venue_id' => $hurghadaVenue->id,
            'title' => 'El Gouna Film Nights',
            'description' => 'Showcase of contemporary Mediterranean films, postponed to late season.',
            'event_type' => 'Film',
            'start_at' => now()->addDays(80),
            'end_at' => now()->addDays(82),
            'status' => EventStatus::POSTPONED,
        ]);

        $postponedEvent->images()->create([
            'path' => 'events/elgouna_film_poster.jpg',
        ]);

        $postponedEvent->ticketTypes()->createMany([
            [
                'name' => 'Film Pass',
                'category' => TicketCategory::REGULAR,
                'price' => 25.00,
                'currency' => 'USD',
                'quantity' => 100,
                'reserved_quantity' => 0,
                'sold_quantity' => 0,
                'max_per_order' => 4,
            ],
        ]);

        // 5. Mega Event: Cairo Mega Festival 2026 (Thousands of ticket holders)
        $megaEvent = Event::create([
            'organizer_id' => $organizer1->id,
            'venue_id' => $cairoVenue->id,
            'title' => 'Cairo Mega Festival 2026',
            'description' => 'The largest international live performance and music festival in North Africa.',
            'event_type' => 'Music',
            'start_at' => now()->addDays(60),
            'end_at' => now()->addDays(61),
            'status' => EventStatus::PUBLISHED,
            'is_featured' => true,
        ]);

        $megaEvent->images()->create([
            'path' => 'events/cairo_mega_poster.jpg',
        ]);

        $megaEvent->ticketTypes()->createMany([
            [
                'name' => 'General Admission',
                'category' => TicketCategory::REGULAR,
                'price' => 45.00,
                'currency' => 'USD',
                'quantity' => 5000,
                'reserved_quantity' => 0,
                'sold_quantity' => 2500,
                'max_per_order' => 6,
            ],
            [
                'name' => 'VIP Golden Circle',
                'category' => TicketCategory::VIP,
                'price' => 160.00,
                'currency' => 'USD',
                'quantity' => 500,
                'reserved_quantity' => 0,
                'sold_quantity' => 50,
                'max_per_order' => 4,
            ],
        ]);
    }
}
