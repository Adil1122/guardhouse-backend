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
        Schema::create('organization_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('two_factor_auth')->default(false);
            $table->enum('live_ops_sorting', ['time-asc', 'time-desc', 'duration-asc', 'duration-desc'])->default('time-asc');
            $table->longText('custom_clockin_questionnaire')->nullable()->comment('DC2Type: Array');
            $table->integer('shift_alert_response_time')->nullable()->default(null);
            $table->foreignId('default_pay_group_id')->nullable()->constrained('pay_groups')->nullOnDelete();
            $table->foreignId('default_service_group_id')->nullable()->constrained('service_groups')->nullOnDelete();
            $table->integer('genfence_check_in_distance')->default(200);
            $table->boolean('enable_digital_occurrence_logs')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_settings');
    }
};
