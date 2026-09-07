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
        Schema::create('participant_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_class_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active')->index();
            $table->date('enrolled_at');
            $table->date('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'training_batch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participant_enrollments');
    }
};
