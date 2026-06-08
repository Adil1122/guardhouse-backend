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
        Schema::table('shift_timeclock_logs', function (Blueprint $table) {
            $table->dateTime('clocked_out')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_timeclock_logs', function (Blueprint $table) {
            $table->time('clocked_out')->nullable(false)->default('00:00:00')->change();
        });
    }
};
