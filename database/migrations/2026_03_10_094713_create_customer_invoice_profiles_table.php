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
        Schema::create('customer_invoice_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_profile_id')->constrained('customer_profiles')->cascadeOnDelete();
            $table->string('company_name', 50);
            $table->string('contact_first_name', 50);
            $table->string('contact_last_name', 50);
            $table->string('email', 30)->nullable()->default(null);
            $table->string('contact_number', 30);
            $table->string('po_number_prefix', 20)->nullable()->default(null);
            $table->decimal('tax_rate', 10, 2)->nullable()->default(null);
            $table->enum('export_invoice_type', ['summary', 'detail'])->default('detail');
            $table->string('address', 100)->nullable()->default(null)->comment('DC2Type: Array');
            $table->mediumText('terms')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_invoice_profiles');
    }
};
