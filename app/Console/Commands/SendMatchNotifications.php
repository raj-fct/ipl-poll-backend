<?php

namespace App\Console\Commands;

use App\Models\IplMatch;
use App\Models\MatchNotification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendMatchNotifications extends Command
{
    protected $signature = 'ipl:send-match-notifications';
    protected $description = 'Send scheduled notifications for upcoming matches';

    /**
     * Notification windows (type => minutes before poll close).
     * Polls close 30 min before match_date, so:
     *   match_day       = sent at 9 AM on match day
     *   2hr_before      = 2h 30m before match (2h before poll close)
     *   1hr_before      = 1h 30m before match (1h before poll close)
     *   30min_before    = 1h before match (30min before poll close)
     */
    private const NOTIFICATIONS = [
        '2hr_before'   => 150, // 2h30m before match = 2h before poll close
        '1hr_before'   => 90,  // 1h30m before match = 1h before poll close
        '30min_before' => 60,  // 1h before match = 30min before poll close
    ];

    public function handle(): void
    {
        $now = Carbon::now();

        // Get upcoming matches within the next 3 hours or today (for match_day)
        $matches = IplMatch::where('status', 'upcoming')
            ->where('match_date', '>=', $now)
            ->where('match_date', '<=', $now->copy()->addHours(24))
            ->get();

        if ($matches->isEmpty()) {
            $this->info('No upcoming matches to notify about.');
            return;
        }

        $notificationService = app(NotificationService::class);

        foreach ($matches as $match) {
            $matchDate = $match->match_date;
            $matchLabel = "{$match->team_a_short} vs {$match->team_b_short}";

            // 1) Match day notification at 9 AM
            $this->sendMatchDayNotification($match, $matchLabel, $matchDate, $now, $notificationService);

            // 2) Time-based notifications before poll close
            foreach (self::NOTIFICATIONS as $type => $minutesBefore) {
                $this->sendTimedNotification($match, $matchLabel, $type, $minutesBefore, $matchDate, $now, $notificationService);
            }
        }
    }

    private function sendMatchDayNotification(
        IplMatch $match,
        string $matchLabel,
        Carbon $matchDate,
        Carbon $now,
        NotificationService $notificationService
    ): void {
        // Only send if it's match day and it's 9 AM or later
        if (! $now->isSameDay($matchDate) || $now->hour < 9) {
            return;
        }

        if ($this->alreadySent($match->id, 'match_day')) {
            return;
        }

        $matchTime = $matchDate->format('g:i A');
        $multiplier = $match->win_multiplier ? "{$match->win_multiplier}x" : '';
        $title = "🏏 Match Day! {$matchLabel}";
        $body = "Today's blockbuster starts at {$matchTime}! Pick your winner, bet your coins and earn {$multiplier} rewards. Don't sit out — champions predict!";

        $this->sendAndLog($match, 'match_day', $title, $body, $notificationService);
    }

    private function sendTimedNotification(
        IplMatch $match,
        string $matchLabel,
        string $type,
        int $minutesBefore,
        Carbon $matchDate,
        Carbon $now,
        NotificationService $notificationService
    ): void {
        $sendAt = $matchDate->copy()->subMinutes($minutesBefore);

        // Send if we're within 5 minutes of the scheduled time
        if ($now->lt($sendAt) || $now->gt($sendAt->copy()->addMinutes(5))) {
            return;
        }

        if ($this->alreadySent($match->id, $type)) {
            return;
        }

        $messages = [
            '2hr_before'   => [
                'title' => "⏰ 2 Hours Left! {$matchLabel}",
                'body'  => "Polls close in 2 hours! Back your favourite team, place your coins and watch them multiply. Predict now!",
            ],
            '1hr_before'   => [
                'title' => "⚡ 1 Hour Left! {$matchLabel}",
                'body'  => "Only 1 hour to go! The odds are set, the stage is ready — are you in? Place your prediction before it's too late!",
            ],
            '30min_before' => [
                'title' => "🚨 Final Call! {$matchLabel}",
                'body'  => "Last 30 minutes! Polls are about to close. This is your LAST chance to predict and win coins. Go go go!",
            ],
        ];

        $msg = $messages[$type];
        $this->sendAndLog($match, $type, $msg['title'], $msg['body'], $notificationService);
    }

    private function sendAndLog(
        IplMatch $match,
        string $type,
        string $title,
        string $body,
        NotificationService $notificationService
    ): void {
        $result = $notificationService->sendToAll($title, $body, [
            'type'     => 'match_reminder',
            'match_id' => (string) $match->id,
            'route'    => '/match/' . $match->id,
        ]);

        MatchNotification::create([
            'match_id'      => $match->id,
            'type'          => $type,
            'sent_at'       => now(),
            'success_count' => $result['success'],
            'failure_count' => $result['failure'],
        ]);

        $this->info("[Match #{$match->match_number}] {$type}: sent {$result['success']} ok, {$result['failure']} failed");
    }

    private function alreadySent(int $matchId, string $type): bool
    {
        return MatchNotification::where('match_id', $matchId)->where('type', $type)->exists();
    }
}