<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsAdminController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['settings' => Setting::all()]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bonus_coins' => 'nullable|integer|min:0',
            'min_bid'     => 'nullable|integer|min:1',
            'max_bid'     => 'nullable|integer|min:1',
            'max_bid_percent' => 'nullable|integer|in:50,100',
            'season'      => 'nullable|string|max:50',
        ]);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                Setting::set($key, $value);
            }
        }

        return response()->json([
            'message'  => 'Settings updated.',
            'settings' => Setting::all(),
        ]);
    }
}
