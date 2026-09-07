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
        Schema::create('training_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_location_id')->nullable()->index();
            $table->date('schedule_date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->time('morning_open');
            $table->time('morning_on_time_limit');
            $table->time('morning_close');
            $table->time('afternoon_open');
            $table->time('afternoon_early_limit');
            $table->time('afternoon_close');
            $table->string('subject')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->unique(['training_class_id', 'schedule_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_schedules');
    }
};
