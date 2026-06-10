<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->string('title');
            $table->string('type');
            $table->text('description');
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('submitted');
            $table->boolean('requires_action')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('review_comments')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_reports');
    }
};
