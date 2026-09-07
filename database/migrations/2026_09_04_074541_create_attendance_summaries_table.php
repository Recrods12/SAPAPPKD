<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_schedule_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date')->index();
            $table->string('morning_status', 30);
            $table->string('afternoon_status', 30);
            $table->string('overall_status', 30)->index();
            $table->timestamp('finalized_at');
            $table->timestamps();
            $table->unique(['user_id', 'training_schedule_id']);
            $table->index(['training_schedule_id', 'overall_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_summaries');
    }
};
