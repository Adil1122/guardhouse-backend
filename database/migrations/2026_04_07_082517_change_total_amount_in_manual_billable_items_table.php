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
        Schema::table('manual_billable_items', function (Blueprint $table) {
            $table->decimal('total_amount', 10, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manual_billable_items', function (Blueprint $table) {
            $table->enum('total_amount', ['created', 'complete', 'unproccessible'])->default('created')->change();
        });
    }
};
