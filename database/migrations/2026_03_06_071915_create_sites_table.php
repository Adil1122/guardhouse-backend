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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['static', 'mobile-patrol']);
            $table->foreignId('customer_profile_id')->nullable()->default(null)->constrained('customer_profiles')->nullOnDelete();
            $table->string('name', 100);
            $table->string('geofence', 255)->comment('DC2Type: Array');
            $table->string('address', 255)->comment('DC2Type: Array');
            $table->foreignId('default_pay_group_id')->nullable()->default(null)->constrained('pay_groups')->nullOnDelete();
            $table->foreignId('default_service_group_id')->nullable()->default(null)->constrained('service_groups')->nullOnDelete();
            $table->mediumText('custom_clockin_questionnaire')->nullable()->default(null)->comment('DC2Type: Array');
            $table->longText('instructions')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
