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
        if (! Schema::hasColumn('event_availabilities', 'event_id')) {
            Schema::table('event_availabilities', function (Blueprint $table): void {
                $table->unsignedBigInteger('event_id')->nullable()->after('id');
            });
        }

        if (! Schema::hasColumn('event_availabilities', 'user_id')) {
            Schema::table('event_availabilities', function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id')->nullable()->after('event_id');
            });
        }

        if (! Schema::hasColumn('event_availabilities', 'available_at')) {
            Schema::table('event_availabilities', function (Blueprint $table): void {
                $table->dateTime('available_at')->nullable()->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_availabilities', function (Blueprint $table): void {
            if (Schema::hasColumn('event_availabilities', 'available_at')) {
                $table->dropColumn('available_at');
            }

            if (Schema::hasColumn('event_availabilities', 'user_id')) {
                $table->dropColumn('user_id');
            }

            if (Schema::hasColumn('event_availabilities', 'event_id')) {
                $table->dropColumn('event_id');
            }
        });
    }
};
