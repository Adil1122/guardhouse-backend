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
        Schema::create('digital_occurrence_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->enum('type', [
                'clock_in',
                'clock_out',
                'qr_scan',
                'welfare_check_missed_alert',
                'low_battery',
                'emergency'
            ]);
            $table->longText('description')->nullable();
            $table->json('files')->nullable();
            $table->boolean('show_to_customer')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_occurrence_logs');
    }
};
