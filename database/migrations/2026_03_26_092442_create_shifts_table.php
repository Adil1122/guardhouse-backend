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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->enum('service_type', ['static-guard', 'mobile-patrol']);
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('break_duration')->nullable();
            $table->enum('status', [
                'created', 'offered', 'confirmed', 'rejected',
                'awaiting', 'checking-welfare', 'clocked-in',
                'clocked-out', 'clocked-out-offsite', 'missed-alert',
                'missed-clock-in'
            ])->default('confirmed');
            $table->string('customer_po', 30)->nullable();
            $table->foreignId('service_group_id')->nullable()->constrained('service_groups')->nullOnDelete();
            $table->foreignId('pay_group_id')->nullable()->constrained('pay_groups')->nullOnDelete();
            $table->enum('questionnaire', ['global', 'site'])->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
