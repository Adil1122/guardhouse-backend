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
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('preferred_first_name', 50);
            $table->string('preferred_last_name', 50);
            $table->string('sia_bridge_number', 50);
            $table->string('contact_number', 50);
            $table->string('emergency_contact', 255)->comment('DC2Type: Array');
            $table->enum('service_status', ['on-duty', 'off-duty'])->default('off-duty');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('bank_details', 255)->comment('DC2Type: Array');
            $table->string('tax_number', 50)->nullable()->default(null);
            $table->foreignId('default_pay_group_id')->nullable()->constrained('pay_groups')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};
