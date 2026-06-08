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
        Schema::create('organization_compliances', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('remind_in_days');
            $table->boolean('is_critical')->default(false);
            $table->boolean('show_to_customer')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_compliances');
    }
};
