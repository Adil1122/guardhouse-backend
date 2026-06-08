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
        Schema::table('staff_privileges', function (Blueprint $table) {
            $table->dropColumn(['item_id', 'item_privilege']);
            $table->json('privileges')->after('staff_profile_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_privileges', function (Blueprint $table) {
            $table->string('item_id')->after('staff_profile_id');
            $table->string('item_privilege')->after('item_id');
            $table->dropColumn('privileges');
        });
    }
};
