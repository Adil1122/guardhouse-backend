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
        Schema::table('organization_compliances', function (Blueprint $table) {
            $table->integer('remind_in_days')->default(30)->change();
        });

        Schema::table('staff_compliances', function (Blueprint $table) {
            $table->dropForeign(['compliance_id']);

            $table->foreign('compliance_id')
                ->references('id')
                ->on('organization_compliances')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff_compliances', function (Blueprint $table) {
            $table->dropForeign(['compliance_id']);
            $table->foreignId('compliance_id')->nullable()->change();

            $table->foreign('compliance_id')
                ->references('id')
                ->on('organization_compliances')
                ->nullOnDelete();
        });

        Schema::table('organization_compliances', function (Blueprint $table) {
            $table->integer('remind_in_days')->change();
        });
    }
};
