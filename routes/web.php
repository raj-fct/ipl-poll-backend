<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\DashboardWebController;
use App\Http\Controllers\Web\UserWebController;
use App\Http\Controllers\Web\MatchWebController;
use App\Http\Controllers\Web\PollWebController;
use App\Http\Controllers\Web\SettingWebController;
use App\Http\Controllers\Web\TeamWebController;
use App\Http\Controllers\Web\TransactionWebController;

// Admin Login
Route::get('/admin/login', [AuthWebController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthWebController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AuthWebController::class, 'logout'])->name('admin.logout');

// Admin Panel (protected)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardWebController::class, 'index'])->name('dashboard');

    // Users
    Route::get('/users', [UserWebController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserWebController::class, 'create'])->name('users.create');
    Route::post('/users', [UserWebController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserWebController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserWebController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserWebController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/toggle-active', [UserWebController::class, 'toggleActive'])->name('users.toggle-active');
    Route::post('/users/{user}/reset-password', [UserWebController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/{user}/adjust-coins', [UserWebController::class, 'adjustCoins'])->name('users.adjust-coins');
    Route::post('/users/award-bonus', [UserWebController::class, 'awardBonusToAll'])->name('users.award-bonus');

    // Teams
    Route::get('/teams', [TeamWebController::class, 'index'])->name('teams.index');
    Route::get('/teams/create', [TeamWebController::class, 'create'])->name('teams.create');
    Route::post('/teams', [TeamWebController::class, 'store'])->name('teams.store');
    Route::get('/teams/{team}/edit', [TeamWebController::class, 'edit'])->name('teams.edit');
    Route::put('/teams/{team}', [TeamWebController::class, 'update'])->name('teams.update');

    // Matches
    Route::get('/matches', [MatchWebController::class, 'index'])->name('matches.index');
    Route::get('/matches/create', [MatchWebController::class, 'create'])->name('matches.create');
    Route::post('/matches', [MatchWebController::class, 'store'])->name('matches.store');
    Route::get('/matches/{match}', [MatchWebController::class, 'show'])->name('matches.show');
    Route::get('/matches/{match}/edit', [MatchWebController::class, 'edit'])->name('matches.edit');
    Route::put('/matches/{match}', [MatchWebController::class, 'update'])->name('matches.update');
    Route::post('/matches/{match}/status', [MatchWebController::class, 'updateStatus'])->name('matches.update-status');
    Route::post('/matches/{match}/set-result', [MatchWebController::class, 'setResult'])->name('matches.set-result');
    Route::post('/matches/{match}/cancel', [MatchWebController::class, 'cancel'])->name('matches.cancel');

    // Polls
    Route::get('/polls', [PollWebController::class, 'index'])->name('polls.index');

    // Transactions
    Route::get('/transactions', [TransactionWebController::class, 'index'])->name('transactions.index');

    // Settings
    Route::get('/settings', [SettingWebController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingWebController::class, 'update'])->name('settings.update');
});
