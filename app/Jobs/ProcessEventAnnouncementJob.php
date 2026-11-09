<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\EventAnnouncement;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessEventAnnouncementJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $announcementId)
    {
        $this->onQueue('bulk');
    }

    public function handle(): void
    {
        $announcement = EventAnnouncement::find($this->announcementId);

        if (! $announcement) {
            return;
        }

        User::whereHas('bookings', function ($query) use ($announcement) {
            $query->where('event_id', $announcement->event_id)
                ->where('status', BookingStatus::PAID);
        })->chunkById(100, function ($users) use ($announcement) {
            foreach ($users as $user) {
                SendAnnouncementNotificationJob::dispatch($announcement->id, $user->id);
            }
        });
    }
}
