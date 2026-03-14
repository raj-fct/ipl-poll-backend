<?php

use Illuminate\Support\Facades\Schedule;

// Verify match results every 30 minutes during IPL hours (2 PM - 12 AM IST)
Schedule::command('ipl:verify-results')
    ->everyThirtyMinutes()
    ->between('14:00', '23:59')
    ->appendOutputTo(storage_path('logs/verify-results.log'));

// Also run once early morning to catch any overnight results
Schedule::command('ipl:verify-results')
    ->dailyAt('08:00')
    ->appendOutputTo(storage_path('logs/verify-results.log'));

// Fetch/sync match schedule once daily at 6 AM
Schedule::command('ipl:fetch-matches')
    ->dailyAt('06:00')
    ->appendOutputTo(storage_path('logs/fetch-matches.log'));
