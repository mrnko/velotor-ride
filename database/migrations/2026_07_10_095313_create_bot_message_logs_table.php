<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_message_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_update_id')->nullable();
            $table->string('chat_id')->nullable();
            $table->unsignedBigInteger('telegram_user_id')->nullable();
            $table->text('message_text')->nullable();
            $table->enum('direction', ['incoming', 'outgoing'])->default('incoming');
            $table->string('handler')->nullable();
            $table->enum('status', ['ok', 'ignored', 'error'])->default('ok');
            $table->text('error_message')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('telegram_update_id');
            $table->index('telegram_user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_message_logs');
    }
};
