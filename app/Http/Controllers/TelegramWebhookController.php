<?php

namespace App\Http\Controllers;

use App\Services\Telegram\UpdateHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, UpdateHandler $handler): Response
    {
        // UpdateHandler never throws — any malformed/unexpected payload is
        // logged internally. We always answer 200 so Telegram doesn't retry.
        $handler->handle($request->all());

        return response(['ok' => true]);
    }
}
