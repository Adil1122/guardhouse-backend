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
        Schema::table('timesheets', function (Blueprint $table) {
            $table->foreignId('service_group_id')->nullable()->constrained('service_groups')->cascadeOnDelete()->after('status');
            $table->foreignId('pay_group_id')->nullable()->constrained('pay_groups')->cascadeOnDelete()->after('service_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropForeign(['service_group_id']);
            $table->dropColumn('service_group_id');

            $table->dropForeign(['pay_group_id']);
            $table->dropColumn('pay_group_id');
        });
    }
};
