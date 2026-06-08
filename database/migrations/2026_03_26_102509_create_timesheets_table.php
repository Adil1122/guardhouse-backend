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
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->cascadeOnDelete();
            $table->enum('entry_type', ['work', 'leave']);
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('employee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('break_duration')->nullable();
            $table->enum('status', [
                'drafted',
                'approved',
                'rejected',
                'invoiced',
                'paid',
                'settled'
            ])->default('drafted');
            $table->mediumText('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timesheets');
    }
};
