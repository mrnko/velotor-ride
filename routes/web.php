<?php

use App\Http\Controllers\Admin\BotLogController;
use App\Http\Controllers\AnnounceUpdateController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ParticipantController as AdminParticipantController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\RideResultController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\WeeklyPeriodController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Site\AllTimeController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\LandingController;
use App\Http\Controllers\Site\ParticipantController;
use App\Http\Controllers\Site\PrivacyController;
use App\Http\Controllers\Site\RulesController;
use App\Http\Controllers\Site\SitemapController;
use App\Http\Controllers\Site\WeekController;
use App\Http\Controllers\Site\YearController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

/*
| Public informational homepage lives at the domain root; all statistics and
| stats functionality live under the /stat/* prefix.
*/
Route::get('/', LandingController::class)->name('home');
Route::get('/privacy-policy', PrivacyController::class)->name('privacy');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Legacy URL from the old site — old Telegram messages and bookmarks still link here.
Route::redirect('/statistics', '/stat', 301);

Route::prefix('stat')->name('stat.')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('/weeks/{year?}', [WeekController::class, 'archive'])->whereNumber('year')->name('week.archive');
    Route::get('/weeks/{year}/{weekNumber}', [HomeController::class, 'show'])->whereNumber(['year', 'weekNumber'])->name('week.show');

    Route::get('/year/{year?}', [YearController::class, 'show'])->whereNumber('year')->name('year.show');

    Route::get('/all-time', AllTimeController::class)->name('all-time');

    Route::get('/user/{participant:slug}', [ParticipantController::class, 'show'])->name('participants.show');

    Route::get('/rules', RulesController::class)->name('rules');
});

Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->middleware('telegram.webhook')
    ->name('telegram.webhook');

Route::get('/telegram/announce', AnnounceUpdateController::class)->name('telegram.announce');

Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('logout');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/recalculate', [DashboardController::class, 'recalculate'])->name('recalculate');

    Route::get('/participants', [AdminParticipantController::class, 'index'])->name('participants.index');
    Route::post('/participants/{participant}/merge', [AdminParticipantController::class, 'merge'])->name('participants.merge');

    Route::get('/ride-results', [RideResultController::class, 'index'])->name('ride-results.index');
    Route::get('/ride-results/{rideResult}/edit', [RideResultController::class, 'edit'])->name('ride-results.edit');
    Route::put('/ride-results/{rideResult}', [RideResultController::class, 'update'])->name('ride-results.update');
    Route::delete('/ride-results/{rideResult}', [RideResultController::class, 'destroy'])->name('ride-results.destroy');

    Route::get('/weekly-periods', [WeeklyPeriodController::class, 'index'])->name('weekly-periods.index');
    Route::post('/weekly-periods/close', [WeeklyPeriodController::class, 'close'])->name('weekly-periods.close');
    Route::post('/weekly-periods/remind', [WeeklyPeriodController::class, 'remind'])->name('weekly-periods.remind');

    Route::get('/bot-logs', [BotLogController::class, 'index'])->name('bot-logs.index');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');
});
