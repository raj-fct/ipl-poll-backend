<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IplMatch;
use App\Models\MatchNotification;
use App\Models\ScheduledNotification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationWebController extends Controller
{
    public function index()
    {
        // Sent notifications (match auto + custom)
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
                        'scheduled' => $pollCloseTime->copy()->subMinutes(30),
                        'sent'      => in_array('30min_before', $sent),
                    ],
                ];

                return $match;
            });

        // Custom scheduled notifications
        $scheduledNotifications = ScheduledNotification::with(['match', 'creator'])
            ->orderByDesc('scheduled_at')
            ->paginate(20, ['*'], 'scheduled_page');

        return view('admin.notifications.index', compact(
            'sentNotifications', 'upcomingMatches', 'scheduledNotifications'
        ));
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
            'title'        => 'required|string|max:200',
            'body'         => 'required|string|max:500',
            'match_id'     => 'nullable|exists:matches,id',
            'send_type'    => 'required|in:now,scheduled',
            'scheduled_at' => 'required_if:send_type,scheduled|nullable|date|after:now',
        ]);

        $extraData = [];
        if (! empty($data['match_id'])) {
            $extraData = [
                'type'     => 'custom_notification',
                'match_id' => (string) $data['match_id'],
                'route'    => '/match/' . $data['match_id'],
            ];
        }

        // Schedule for later
        if ($data['send_type'] === 'scheduled') {
            ScheduledNotification::create([
                'title'        => $data['title'],
                'body'         => $data['body'],
                'match_id'     => $data['match_id'] ?? null,
                'scheduled_at' => Carbon::parse($data['scheduled_at']),
                'created_by'   => auth()->id(),
            ]);

            $time = Carbon::parse($data['scheduled_at'])->format('d M Y, g:i A');
            return redirect()->route('admin.notifications.index')
                ->with('success', "Notification scheduled for {$time}.");
        }

        // Send immediately
        Log::info('[Notification] Admin sending immediate notification', [
            'admin_id' => auth()->id(),
            'title'    => $data['title'],
            'body'     => $data['body'],
            'match_id' => $data['match_id'] ?? null,
            'data'     => $extraData,
        ]);

        $notificationService = app(NotificationService::class);
        $result = $notificationService->sendToAll($data['title'], $data['body'], $extraData);

        Log::info('[Notification] Admin immediate notification result', [
            'success' => $result['success'],
            'failure' => $result['failure'],
        ]);

        return redirect()->route('admin.notifications.index')
            ->with('success', "Notification sent! Success: {$result['success']}, Failed: {$result['failure']}");
    }

    public function cancel(ScheduledNotification $notification)
    {
        if ($notification->status !== 'pending') {
            return back()->with('error', 'Only pending notifications can be cancelled.');
        }

        $notification->delete();

        return back()->with('success', 'Scheduled notification cancelled.');
    }
}
