<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('torcoin_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('weekly_period_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 8, 2);
            $table->string('reason');
            $table->timestamps();

            $table->unique(['weekly_period_id', 'reason']);
            $table->index(['participant_id', 'weekly_period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('torcoin_bonuses');
    }
};
