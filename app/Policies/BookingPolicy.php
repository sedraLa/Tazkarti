<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id || $user->role === UserRole::ADMIN;
    }

    public function confirm(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        if ($user->id === $booking->user_id) {
            return true;
        }

        if ($user->role === UserRole::ORGANIZER && $user->organizer !== null) {
            $organizerId = $booking->event?->organizer_id
                ?? Event::where('id', $booking->event_id)->value('organizer_id');

            return $organizerId !== null && $organizerId === $user->organizer->id;
        }

        return false;
    }
}
