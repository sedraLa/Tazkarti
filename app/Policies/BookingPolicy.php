<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Booking;
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
}
