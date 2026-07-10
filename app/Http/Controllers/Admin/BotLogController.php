<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotMessageLog;
use Inertia\Inertia;
use Inertia\Response;

class BotLogController extends Controller
{
    public function index(): Response
    {
        $logs = BotMessageLog::orderByDesc('id')->paginate(40);

        return Inertia::render('Admin/BotLogs/Index', [
            'logs' => $logs->through(fn (BotMessageLog $log) => [
                'id' => $log->id,
                'chat_id' => $log->chat_id,
                'telegram_user_id' => $log->telegram_user_id,
                'message_text' => $log->message_text,
                'handler' => $log->handler,
                'status' => $log->status,
                'error_message' => $log->error_message,
                'created_at' => $log->created_at->toDateTimeString(),
            ]),
        ]);
    }
}
