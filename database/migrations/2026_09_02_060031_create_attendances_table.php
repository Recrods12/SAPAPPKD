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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('training_schedule_id')->constrained()->restrictOnDelete();
            $table->foreignId('attendance_location_id')->constrained()->restrictOnDelete();
            $table->date('attendance_date')->index();
            $table->string('type', 20);
            $table->string('status', 40)->index();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy_meters', 8, 2);
            $table->decimal('distance_meters', 8, 2);
            $table->timestamp('recorded_at')->index();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignId('device_token_id')->nullable();
            $table->boolean('needs_review')->default(false)->index();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'training_schedule_id', 'attendance_date', 'type'], 'attendances_unique_submission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
