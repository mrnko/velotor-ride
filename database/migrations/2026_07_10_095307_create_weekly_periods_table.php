<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_periods', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year');
            $table->smallInteger('week_number');
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->decimal('total_distance', 10, 2)->default(0);
            $table->timestamp('report_sent_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['year', 'week_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_periods');
    }
};
