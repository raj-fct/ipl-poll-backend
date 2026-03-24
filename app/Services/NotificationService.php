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
            Log::info('[FCM] sendToUser skipped — no FCM token', [
                'user_id' => $user->id,
                'title'   => $title,
            ]);
            return false;
        }

        Log::info('[FCM] sendToUser triggered', [
            'user_id' => $user->id,
            'token'   => substr($user->fcm_token, 0, 20) . '...',
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
        ]);

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

            Log::info('[FCM] Sending to token', [
                'token' => substr($token, 0, 20) . '...',
                'title' => $title,
            ]);

            $this->messaging->send($message);

            Log::info('[FCM] Successfully sent to token', [
                'token' => substr($token, 0, 20) . '...',
            ]);

            return true;
        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            Log::warning('[FCM] Token not found, clearing', ['token' => substr($token, 0, 20) . '...']);
            User::where('fcm_token', $token)->update(['fcm_token' => null]);
            return false;
        } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
            Log::error('[FCM] Invalid message', ['token' => substr($token, 0, 20) . '...', 'error' => $e->getMessage()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('[FCM] Send failed', [
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            return false;
        }
    }

    /**
     * Send notification to multiple users.
     */
    public function sendToUsers($users, string $title, string $body, array $data = []): array
    {
        $tokens = $users->pluck('fcm_token')->filter()->values()->all();

        Log::info('[FCM] sendToUsers triggered', [
            'total_users'      => $users->count(),
            'users_with_token' => count($tokens),
            'title'            => $title,
            'body'             => $body,
            'data'             => $data,
        ]);

        if (empty($tokens)) {
            Log::warning('[FCM] sendToUsers — no valid tokens found, skipping');
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
            Log::info('[FCM] Sending multicast', ['token_count' => count($tokens), 'title' => $title]);

            $report = $this->messaging->sendMulticast($message, $tokens);

            // Clear invalid tokens
            $invalidTokens = [];
            foreach ($report->invalidTokens() as $token) {
                $invalidTokens[] = $token;
            }
            if (! empty($invalidTokens)) {
                Log::warning('[FCM] Clearing invalid tokens', ['count' => count($invalidTokens)]);
                User::whereIn('fcm_token', $invalidTokens)->update(['fcm_token' => null]);
            }

            $result = [
                'success' => $report->successes()->count(),
                'failure' => $report->failures()->count(),
            ];

            Log::info('[FCM] Multicast result', $result);

            // Log individual failures for debugging
            foreach ($report->failures()->getItems() as $failure) {
                Log::error('[FCM] Individual failure', [
                    'token' => substr($failure->target()->value(), 0, 20) . '...',
                    'error' => $failure->error() ? $failure->error()->getMessage() : 'unknown',
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('[FCM] Multicast failed', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'token_count' => count($tokens),
            ]);
            return ['success' => 0, 'failure' => count($tokens)];
        }
    }

    /**
     * Send notification to all users with FCM tokens.
     */
    public function sendToAll(string $title, string $body, array $data = []): array
    {
        $users = User::whereNotNull('fcm_token')->where('is_active', true)->get();

        Log::info('[FCM] sendToAll triggered', [
            'active_users_with_token' => $users->count(),
            'title'                   => $title,
        ]);

        return $this->sendToUsers($users, $title, $body, $data);
    }
}