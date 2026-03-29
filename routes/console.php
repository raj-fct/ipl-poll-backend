<?php

use Illuminate\Support\Facades\Schedule;

// Verify match results every 30 minutes during IPL hours (2 PM - 12 AM IST)
Schedule::command('ipl:verify-results')
    ->everyFiveMinutes()
    ->appendOutputTo(storage_path('logs/verify-results.log'));

// Fetch/sync match schedule once daily at 6 AM
Schedule::command('ipl:fetch-matches')
    ->dailyAt('06:00')
    ->appendOutputTo(storage_path('logs/fetch-matches.log'));

// Send match notifications (match day, 2hr, 1hr, 30min before poll close)
Schedule::command('ipl:send-match-notifications')
    ->everyMinute()
    ->between('08:30', '23:59')
    ->appendOutputTo(storage_path('logs/match-notifications.log'));

// Send custom scheduled notifications
Schedule::command('ipl:send-scheduled-notifications')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/scheduled-notifications.log'));
