<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Settings/Edit', [
            'settings' => [
                'telegram_chat_id' => Setting::get('telegram_chat_id', ''),
                'telegram_invite_url' => Setting::get('telegram_invite_url', ''),
                'timezone' => Setting::get('timezone', config('velotor.timezone')),
                'max_distance_km' => (int) Setting::get('max_distance_km', config('velotor.max_distance_km')),
                'duplicate_window_minutes' => (int) Setting::get('duplicate_window_minutes', config('velotor.duplicate_window_minutes')),
                'duplicate_distance_delta_km' => (float) Setting::get('duplicate_distance_delta_km', config('velotor.duplicate_distance_delta_km')),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'telegram_chat_id' => ['nullable', 'string', 'max:64'],
            'telegram_invite_url' => ['nullable', 'url', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'max_distance_km' => ['required', 'integer', 'min:1', 'max:10000'],
            'duplicate_window_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'duplicate_distance_delta_km' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        Setting::set('telegram_chat_id', $validated['telegram_chat_id'] ?? '');
        Setting::set('telegram_invite_url', $validated['telegram_invite_url'] ?? '');
        Setting::set('timezone', $validated['timezone']);
        Setting::set('max_distance_km', $validated['max_distance_km'], 'integer');
        Setting::set('duplicate_window_minutes', $validated['duplicate_window_minutes'], 'integer');
        Setting::set('duplicate_distance_delta_km', $validated['duplicate_distance_delta_km'], 'float');

        return back()->with('success', 'Налаштування збережено.');
    }
}
