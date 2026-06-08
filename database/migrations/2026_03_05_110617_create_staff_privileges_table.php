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
        Schema::create('staff_privileges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->enum('item_id', [
                'live_op',
                'staff_detail',
                'staff_document',
                'staff_complicance',
                'staff_privilege',
                'staff_salary',
                'shift',
                'pay_group',
                'service_group',
                'invoice',
                'timesheet',
                'static_site',
                'patrol_site',
                'customer_detail',
                'customer_site',
                'customer_contact',
                'customer_invoice_profile',
                'all'
            ]);
            $table->enum('item_privilege', ['view_only','add','edit','delete','all']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_privileges');
    }
};
