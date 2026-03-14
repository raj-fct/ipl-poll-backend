<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingWebController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'bonus_coins' => 'required|integer|min:0',
            'min_bid'     => 'required|integer|min:1',
            'max_bid'     => 'required|integer|min:1',
            'season'      => 'required|string|max:50',
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Settings updated.');
    }
}
