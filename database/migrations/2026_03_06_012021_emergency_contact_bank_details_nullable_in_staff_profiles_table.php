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
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->string('emergency_contact', 255)->comment('DC2Type: Array')->nullable()->default(null)->change();
            $table->string('bank_details', 255)->comment('DC2Type: Array')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->string('emergency_contact', 255)->nullable(false)->default('')->comment('DC2Type: Array')->change();
            $table->string('bank_details', 255)->nullable(false)->default('')->comment('DC2Type: Array')->change();
        });
    }
};
