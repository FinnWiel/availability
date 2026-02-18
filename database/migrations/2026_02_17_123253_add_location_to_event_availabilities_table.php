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
        Schema::table('event_availabilities', function (Blueprint $table) {
            $table->string('location')->nullable()->after('is_all_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_availabilities', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
