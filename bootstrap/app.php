<?php

use App\Models\Setting;
use App\Support\Seo;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'telegram.webhook' => \App\Http\Middleware\VerifyTelegramWebhookSecret::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, \Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            if (app()->hasDebugModeEnabled() || $request->routeIs('admin.*') || ! in_array($status, [403, 404, 419, 500, 503], true)) {
                return $response;
            }

            // A request that never matched a route (bad URL, old bookmark) never
            // ran the 'web' middleware group, so there's no session to pull
            // auth/flash from here — share only the session-free branding props
            // that the Error page's layout actually needs.
            Inertia::share([
                'clubName' => config('velotor.brand.name'),
                'brand' => config('velotor.brand'),
                'telegramInviteUrl' => fn () => Setting::get('telegram_invite_url') ?: config('velotor.brand.telegram_url'),
                'seo' => Seo::make(),
            ]);

            return Inertia::render('Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
