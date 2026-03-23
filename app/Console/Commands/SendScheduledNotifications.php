<?php

namespace App\Console\Commands;

use App\Models\ScheduledNotification;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendScheduledNotifications extends Command
{
    protected $signature = 'ipl:send-scheduled-notifications';
    protected $description = 'Send custom scheduled notifications that are due';

    public function handle(): void
    {
        $due = ScheduledNotification::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($due->isEmpty()) {
            return;
        }

        $notificationService = app(NotificationService::class);

        foreach ($due as $notification) {
            $data = [];
            if ($notification->match_id) {
                $data = [
                    'type'     => 'custom_notification',
                    'match_id' => (string) $notification->match_id,
                    'route'    => '/match/' . $notification->match_id,
                ];
            }

            try {
                $result = $notificationService->sendToAll(
                    $notification->title,
                    $notification->body,
                    $data
                );

                $notification->update([
                    'status'        => 'sent',
                    'success_count' => $result['success'],
                    'failure_count' => $result['failure'],
                    'sent_at'       => now(),
                ]);

                $this->info("[#{$notification->id}] Sent: {$result['success']} ok, {$result['failure']} failed");
            } catch (\Throwable $e) {
                $notification->update(['status' => 'failed']);
                $this->error("[#{$notification->id}] Failed: {$e->getMessage()}");
            }
        }
    }
}
