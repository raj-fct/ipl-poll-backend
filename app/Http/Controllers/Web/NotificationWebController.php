<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IplMatch;
use App\Models\MatchNotification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationWebController extends Controller
{
    public function index()
    {
        // Scheduled notifications (sent)
        $sentNotifications = MatchNotification::with('match')
            ->orderByDesc('sent_at')
            ->paginate(20, ['*'], 'sent_page');

        // Upcoming matches with notification schedule
        $upcomingMatches = IplMatch::where('status', 'upcoming')
            ->where('match_date', '>=', now())
            ->orderBy('match_date')
            ->get()
            ->map(function ($match) {
                $pollCloseTime = $match->match_date->copy()->subMinutes(30);
                $sent = MatchNotification::where('match_id', $match->id)->pluck('type')->toArray();

                $match->notification_schedule = [
                    [
                        'type'      => 'match_day',
                        'label'     => 'Match Day (9 AM)',
                        'scheduled' => $match->match_date->copy()->startOfDay()->addHours(9),
                        'sent'      => in_array('match_day', $sent),
                    ],
                    [
                        'type'      => '2hr_before',
                        'label'     => '2hr Before Poll Close',
                        'scheduled' => $pollCloseTime->copy()->subHours(2),
                        'sent'      => in_array('2hr_before', $sent),
                    ],
                    [
                        'type'      => '1hr_before',
                        'label'     => '1hr Before Poll Close',
                        'scheduled' => $pollCloseTime->copy()->subHour(),
                        'sent'      => in_array('1hr_before', $sent),
                    ],
                    [
                        'type'      => '30min_before',
                        'label'     => '30min Before Poll Close',
                        'scheduled' => $pollCloseTime,
                        'sent'      => in_array('30min_before', $sent),
                    ],
                ];

                return $match;
            });

        return view('admin.notifications.index', compact('sentNotifications', 'upcomingMatches'));
    }

    public function create()
    {
        $matches = IplMatch::where('status', 'upcoming')
            ->where('match_date', '>=', now())
            ->orderBy('match_date')
            ->get();

        return view('admin.notifications.create', compact('matches'));
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'title'    => 'required|string|max:200',
            'body'     => 'required|string|max:500',
            'match_id' => 'nullable|exists:matches,id',
        ]);

        $notificationService = app(NotificationService::class);

        $extraData = [];
        if (! empty($data['match_id'])) {
            $extraData = [
                'type'     => 'custom_notification',
                'match_id' => (string) $data['match_id'],
                'route'    => '/match/' . $data['match_id'],
            ];
        }

        $result = $notificationService->sendToAll($data['title'], $data['body'], $extraData);

        return redirect()->route('admin.notifications.index')
            ->with('success', "Notification sent! Success: {$result['success']}, Failed: {$result['failure']}");
    }
}