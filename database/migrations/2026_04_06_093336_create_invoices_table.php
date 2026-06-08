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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 30);
            $table->foreignId('customer_invoice_profile_id')->nullable()->constrained('customer_invoice_profiles')->nullOnDelete();
            $table->json('tax')->nullable();
            $table->decimal('sub_total', 10, 2);
            $table->decimal('grand_total', 10, 2);
            $table->decimal('paid_amount', 10, 2);
            $table->date('due_date');
            $table->enum('status', ['draft', 'complete', 'unproccessible'])->default('draft');
            $table->mediumText('notes')->nullable();
            $table->enum('payment_status', ['pending', 'partially-paid', 'paid', 'failed', 'retry-failed'])->default('pending');
            $table->mediumText('payment_status_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
