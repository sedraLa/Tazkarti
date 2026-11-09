<?php

namespace App\Policies;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Event $event): bool
    {
        if ($event->status === EventStatus::PUBLISHED) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return $user->organizer !== null
            && $user->organizer->id === $event->organizer_id;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::ADMIN
            || ($user->role === UserRole::ORGANIZER && $user->organizer !== null);
    }

    public function update(User $user, Event $event): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return $user->organizer !== null
            && $user->organizer->id === $event->organizer_id;
    }

    public function delete(User $user, Event $event): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return $user->organizer !== null
            && $user->organizer->id === $event->organizer_id;
    }

    public function markAsFeatured(User $user, Event $event): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function cancel(User $user, Event $event): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return $user->role === UserRole::ORGANIZER
            && $user->organizer !== null
            && $user->organizer->id === $event->organizer_id;
    }

    public function sendAnnouncement(User $user, Event $event): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return $user->role === UserRole::ORGANIZER
            && $user->organizer !== null
            && $user->organizer->id === $event->organizer_id;
    }
}
