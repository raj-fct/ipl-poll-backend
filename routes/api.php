<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\Admin\MatchAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsAdminController;

// ── Public ────────────────────────────────────────────────────
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// ── Authenticated ─────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout',          [AuthController::class, 'logout']);
    Route::get('/auth/profile',          [AuthController::class, 'profile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // Matches
    Route::get('/matches',          [MatchController::class, 'index']);
    Route::get('/matches/{match}',  [MatchController::class, 'show']);

    // Polls
    Route::post('/polls',         [PollController::class, 'store']);
    Route::put('/polls/{poll}',   [PollController::class, 'update']);
    Route::get('/polls/my',       [PollController::class, 'myPolls']);

    // Wallet
    Route::get('/wallet/balance',       [WalletController::class, 'balance']);
    Route::get('/wallet/transactions',  [WalletController::class, 'transactions']);

    // Leaderboard
    Route::get('/leaderboard',          [LeaderboardController::class, 'index']);
    Route::get('/leaderboard/my-rank',  [LeaderboardController::class, 'myRank']);

    // ── Admin ─────────────────────────────────────────────────
    Route::middleware('admin')->prefix('admin')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Users
        Route::get('/users',                          [UserAdminController::class, 'index']);
        Route::post('/users',                         [UserAdminController::class, 'store']);
        Route::put('/users/{user}',                   [UserAdminController::class, 'update']);
        Route::post('/users/{user}/reset-password',   [UserAdminController::class, 'resetPassword']);
        Route::patch('/users/{user}/toggle-active',   [UserAdminController::class, 'toggleActive']);
        Route::post('/users/{user}/adjust-coins',     [UserAdminController::class, 'adjustCoins']);
        Route::post('/users/award-bonus-all',         [UserAdminController::class, 'awardBonusToAll']);

        // Matches
        Route::get('/matches',                          [MatchAdminController::class, 'index']);
        Route::post('/matches',                         [MatchAdminController::class, 'store']);
        Route::put('/matches/{match}',                  [MatchAdminController::class, 'update']);
        Route::patch('/matches/{match}/status',         [MatchAdminController::class, 'updateStatus']);
        Route::post('/matches/{match}/set-result',      [MatchAdminController::class, 'setResult']);
        Route::post('/matches/{match}/cancel',          [MatchAdminController::class, 'cancelMatch']);

        // Settings
        Route::get('/settings',  [SettingsAdminController::class, 'index']);
        Route::put('/settings',  [SettingsAdminController::class, 'update']);
    });
});
