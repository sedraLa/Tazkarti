<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;

class TicketTypePolicy
{
    public function view(User $user, TicketType $ticketType): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return $user->organizer !== null
            && $user->organizer->id === $ticketType->event->organizer_id;
    }

    public function create(User $user, Event $event): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return $user->organizer !== null
            && $user->organizer->id === $event->organizer_id;
    }

    public function update(User $user, TicketType $ticketType): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return $user->organizer !== null
            && $user->organizer->id === $ticketType->event->organizer_id;
    }

    public function delete(User $user, TicketType $ticketType): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return $user->organizer !== null
            && $user->organizer->id === $ticketType->event->organizer_id;
    }
}
