<?php

use App\Http\Controllers\Admin\BotLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ParticipantController as AdminParticipantController;
use App\Http\Controllers\Admin\RideResultController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\WeeklyPeriodController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Site\AllTimeController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\ParticipantController;
use App\Http\Controllers\Site\RulesController;
use App\Http\Controllers\Site\WeekController;
use App\Http\Controllers\Site\YearController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/week', [WeekController::class, 'current'])->name('week.current');
Route::get('/weeks/{year?}', [WeekController::class, 'archive'])->whereNumber('year')->name('week.archive');
Route::get('/weeks/{year}/{weekNumber}', [WeekController::class, 'show'])->whereNumber(['year', 'weekNumber'])->name('week.show');

Route::get('/year/{year?}', [YearController::class, 'show'])->whereNumber('year')->name('year.show');

Route::get('/all-time', AllTimeController::class)->name('all-time');

Route::get('/participants/{participant}', [ParticipantController::class, 'show'])->name('participants.show');

Route::get('/rules', RulesController::class)->name('rules');

Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->middleware('telegram.webhook')
    ->name('telegram.webhook');

Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('logout');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/recalculate', [DashboardController::class, 'recalculate'])->name('recalculate');

    Route::get('/participants', [AdminParticipantController::class, 'index'])->name('participants.index');

    Route::get('/ride-results', [RideResultController::class, 'index'])->name('ride-results.index');
    Route::get('/ride-results/{rideResult}/edit', [RideResultController::class, 'edit'])->name('ride-results.edit');
    Route::put('/ride-results/{rideResult}', [RideResultController::class, 'update'])->name('ride-results.update');
    Route::delete('/ride-results/{rideResult}', [RideResultController::class, 'destroy'])->name('ride-results.destroy');

    Route::get('/weekly-periods', [WeeklyPeriodController::class, 'index'])->name('weekly-periods.index');
    Route::post('/weekly-periods/close', [WeeklyPeriodController::class, 'close'])->name('weekly-periods.close');

    Route::get('/bot-logs', [BotLogController::class, 'index'])->name('bot-logs.index');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
});
