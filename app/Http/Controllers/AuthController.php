<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Login: mobile + password.
     * Returns must_change_password so Flutter can redirect on first login.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile'    => 'required|string',
            'password'  => 'required|string',
            'fcm_token' => 'nullable|string',
        ]);

        $user = User::where('mobile', $data['mobile'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'mobile' => ['Invalid mobile number or password.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Your account has been disabled. Please contact the administrator.',
            ], 403);
        }

        if (! empty($data['fcm_token'])) {
            $user->update(['fcm_token' => $data['fcm_token']]);
        }

        // Single active session per user
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'                 => $this->userResource($user),
            'token'                => $token,
            'must_change_password' => $user->must_change_password,
        ]);
    }

    /**
     * Change password.
     * Clears must_change_password and issues a new token.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'old_password' => 'required|string',
            'new_password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $user = $request->user();

        if (! Hash::check($request->old_password, $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['Current password is incorrect.'],
            ]);
        }

        if ($request->old_password === $request->new_password) {
            throw ValidationException::withMessages([
                'new_password' => ['New password must be different from the current password.'],
            ]);
        }

        $user->update([
            'password'             => Hash::make($request->new_password),
            'must_change_password' => false,
        ]);

        // Revoke all tokens and issue fresh one
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'              => 'Password changed successfully.',
            'token'                => $token,
            'must_change_password' => false,
        ]);
    }

    /**
     * Get authenticated user's profile and poll stats.
     */
    public function profile(Request $request): JsonResponse
    {
        $user  = $request->user();
        $polls = $user->polls();

        $won     = (clone $polls)->where('status', 'won')->count();
        $total   = $polls->count();

        return response()->json([
            'user'  => $this->userResource($user),
            'stats' => [
                'total_polls'  => $total,
                'won'          => $won,
                'lost'         => (clone $polls)->where('status', 'lost')->count(),
                'pending'      => (clone $polls)->where('status', 'pending')->count(),
                'win_rate'     => $total ? round($won / $total * 100, 1) : 0,
                'total_earned' => (clone $polls)->where('status', 'won')->sum('coins_earned'),
            ],
        ]);
    }

    /**
     * Store or update FCM token for the authenticated user.
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['message' => 'FCM token updated successfully.']);
    }

    /**
     * Logout: revoke current token only.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    private function userResource(User $user): array
    {
        return [
            'id'                   => $user->id,
            'name'                 => $user->name,
            'mobile'               => $user->mobile,
            'coin_balance'         => $user->coin_balance,
            'is_admin'             => $user->is_admin,
            'must_change_password' => $user->must_change_password,
        ];
    }
}
