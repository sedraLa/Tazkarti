<?php

namespace App\Jobs;

use App\Models\AnnouncementDelivery;
use App\Models\EventAnnouncement;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendAnnouncementNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public int $announcementId, public int $userId)
    {
        $this->onQueue('bulk');
    }

    public function handle(): void
    {
        $announcement = EventAnnouncement::with('event')->find($this->announcementId);
        $user = User::find($this->userId);

        if (! $announcement || ! $user || empty($user->email)) {
            return;
        }

        try {
            $delivery = AnnouncementDelivery::firstOrCreate(
                [
                    'event_announcement_id' => $announcement->id,
                    'recipient' => $user->email,
                    'channel' => 'email',
                ],
                [
                    'user_id' => $user->id,
                    'status' => 'pending',
                ]
            );
        } catch (QueryException $e) {
            $delivery = AnnouncementDelivery::where('event_announcement_id', $announcement->id)
                ->where('recipient', $user->email)
                ->where('channel', 'email')
                ->first();
        }

        if (! $delivery) {
            return;
        }

        if ($delivery->status === 'sent') {
            return;
        }

        $claimed = AnnouncementDelivery::where('id', $delivery->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'processing',
                'updated_at' => now(),
            ]);

        if ($claimed === 0) {
            $delivery->refresh();
            if ($delivery->status === 'sent') {
                return;
            }

            $this->release(10);
            return;
        }

        Log::info("Event announcement sent to {$user->email} for Event #{$announcement->event_id}.", [
            'announcement_id' => $announcement->id,
            'event_id' => $announcement->event_id,
            'recipient' => $user->email,
            'title' => $announcement->title,
            'message' => $announcement->message,
        ]);

        $delivery->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
