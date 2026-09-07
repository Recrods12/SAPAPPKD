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
        Schema::table('attendance_photos', function (Blueprint $table) {
            $table->string('source_sha256', 64)->nullable()->index()->after('sha256');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_photos', function (Blueprint $table) {
            $table->dropColumn('source_sha256');
        });
    }
};
