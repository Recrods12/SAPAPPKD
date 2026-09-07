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
        Schema::create('participant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nik', 20)->unique();
            $table->string('participant_number')->unique();
            $table->string('gender', 20);
            $table->string('birth_place');
            $table->date('birth_date');
            $table->text('address');
            $table->string('phone', 20);
            $table->string('profile_photo_path')->nullable();
            $table->string('participant_status')->default('active')->index();
            $table->timestamp('privacy_accepted_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participant_profiles');
    }
};
