<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(config('firebase.credentials'));
        $this->messaging = $factory->createMessaging();
    }

    /**
     * Send notification to a single user.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (empty($user->fcm_token)) {
            return false;
        }

        return $this->sendToToken($user->fcm_token, $title, $body, $data);
    }

    /**
     * Send notification to a single FCM token.
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message);
            return true;
        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            Log::warning('FCM token not found, clearing token', ['token' => $token]);
            User::where('fcm_token', $token)->update(['fcm_token' => null]);
            return false;
        } catch (\Throwable $e) {
            Log::error('FCM send failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send notification to multiple users.
     */
    public function sendToUsers($users, string $title, string $body, array $data = []): array
    {
        $tokens = $users->pluck('fcm_token')->filter()->values()->all();

        if (empty($tokens)) {
            return ['success' => 0, 'failure' => 0];
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send notification to multiple FCM tokens.
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        try {
            $report = $this->messaging->sendMulticast($message, $tokens);

            // Clear invalid tokens
            $invalidTokens = [];
            foreach ($report->invalidTokens() as $token) {
                $invalidTokens[] = $token;
            }
            if (! empty($invalidTokens)) {
                User::whereIn('fcm_token', $invalidTokens)->update(['fcm_token' => null]);
            }

            return [
                'success' => $report->successes()->count(),
                'failure' => $report->failures()->count(),
            ];
        } catch (\Throwable $e) {
            Log::error('FCM multicast failed', ['error' => $e->getMessage()]);
            return ['success' => 0, 'failure' => count($tokens)];
        }
    }

    /**
     * Send notification to all users with FCM tokens.
     */
    public function sendToAll(string $title, string $body, array $data = []): array
    {
        $users = User::whereNotNull('fcm_token')->where('is_active', true)->get();
        return $this->sendToUsers($users, $title, $body, $data);
    }
}