<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ride_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->restrictOnDelete();
            $table->foreignId('weekly_period_id')->constrained()->restrictOnDelete();
            $table->decimal('distance_km', 6, 2);
            $table->string('raw_message', 500)->nullable();
            $table->unsignedBigInteger('telegram_message_id')->nullable();
            $table->enum('source', ['telegram', 'admin'])->default('telegram');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_results');
    }
};
