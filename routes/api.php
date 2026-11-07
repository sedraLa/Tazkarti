<?php

use App\Http\Controllers\Api\V1\Admin\EventController as AdminEventController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\Organizer\EventController as OrganizerEventController;
use App\Http\Controllers\Api\V1\Organizer\TicketTypeController as OrganizerTicketTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public Event Endpoints
    Route::get('events', [EventController::class, 'index']);
    Route::get('events/upcoming', [EventController::class, 'upcoming']);
    Route::get('events/featured', [EventController::class, 'featured']);
    Route::get('events/{event}', [EventController::class, 'show']);

    // Customer Booking Endpoints (Protected by Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('bookings', [BookingController::class, 'index']);
        Route::post('bookings', [BookingController::class, 'store']);
        Route::get('bookings/{booking}', [BookingController::class, 'show']);
        Route::post('bookings/{booking}/confirm', [BookingController::class, 'confirm']);
    });

    // Admin Endpoints (Protected by Sanctum)
    Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
        Route::patch('events/{event}/feature', [AdminEventController::class, 'toggleFeatured']);
    });

    // Organizer Endpoints (Protected by Sanctum)
    Route::middleware('auth:sanctum')->prefix('organizer')->group(function () {
        Route::get('events', [OrganizerEventController::class, 'index']);
        Route::post('events', [OrganizerEventController::class, 'store']);
        Route::get('events/{event}', [OrganizerEventController::class, 'show']);
        Route::put('events/{event}', [OrganizerEventController::class, 'update']);
        Route::delete('events/{event}', [OrganizerEventController::class, 'destroy']);

        // Ticket Types (Nested under Event)
        Route::get('events/{event}/ticket-types', [OrganizerTicketTypeController::class, 'index']);
        Route::post('events/{event}/ticket-types', [OrganizerTicketTypeController::class, 'store']);
        Route::put('events/{event}/ticket-types/{ticketType}', [OrganizerTicketTypeController::class, 'update']);
        Route::delete('events/{event}/ticket-types/{ticketType}', [OrganizerTicketTypeController::class, 'destroy']);

        // Ticket Types (Shallow)
        Route::put('ticket-types/{ticketType}', [OrganizerTicketTypeController::class, 'update']);
        Route::delete('ticket-types/{ticketType}', [OrganizerTicketTypeController::class, 'destroy']);
    });
});
