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
        if (! Schema::hasColumn('events', 'created_by')) {
            Schema::table('events', function (Blueprint $table): void {
                $table->unsignedBigInteger('created_by')->nullable()->index()->after('color');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('events', 'created_by')) {
            Schema::table('events', function (Blueprint $table): void {
                $table->dropIndex('events_created_by_index');
                $table->dropColumn('created_by');
            });
        }
    }
};
