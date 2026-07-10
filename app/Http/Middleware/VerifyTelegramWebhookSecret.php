<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramWebhookSecret
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.telegram.webhook_secret');

        if ($expected) {
            $provided = $request->header('X-Telegram-Bot-Api-Secret-Token', '');

            if (! hash_equals($expected, $provided)) {
                abort(403, 'Invalid webhook secret token.');
            }
        }

        return $next($request);
    }
}
