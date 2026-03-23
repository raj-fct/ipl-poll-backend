<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Super admin account
        User::firstOrCreate(
            ['mobile' => '9999999999'],
            [
                'name'                 => 'Admin',
                'password'             => Hash::make('Admin@123'),
                'must_change_password' => false,
                'coin_balance'         => 0,
                'is_admin'             => true,
                'is_active'            => true,
            ]
        );

        // Default app settings
        $settings = [
            ['key' => 'bonus_coins', 'value' => '10000',    'description' => 'Coins awarded to each new user on creation'],
            ['key' => 'min_bid',     'value' => '10',        'description' => 'Minimum coins a user can bid on a match'],
            ['key' => 'max_bid',     'value' => '5000',      'description' => 'Maximum coins a user can bid on a match'],
            ['key' => 'max_bid_percent', 'value' => '50',   'description' => 'Max percentage of balance a user can bid (50 or 100)'],
            ['key' => 'season',      'value' => 'IPL 2026',  'description' => 'Current IPL season label'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }

        $this->command->info('✅ Admin user created  →  Mobile: 9999999999  |  Password: Admin@123');
        $this->command->info('✅ Default settings seeded.');
    }
}
