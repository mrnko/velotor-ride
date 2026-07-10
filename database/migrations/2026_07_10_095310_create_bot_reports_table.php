<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_period_id')->constrained()->cascadeOnDelete();
            $table->string('chat_id');
            $table->unsignedBigInteger('telegram_message_id')->nullable();
            $table->enum('report_type', ['weekly_close'])->default('weekly_close');
            $table->text('content');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_reports');
    }
};
