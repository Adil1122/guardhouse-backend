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
        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_checkpoint_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('location_description')->nullable();
            $table->text('notes')->nullable();
            $table->string('type')->default('regular'); // regular, incident, checkpoint
            $table->string('photo_path')->nullable();
            $table->decimal('photo_lat', 10, 8)->nullable();
            $table->decimal('photo_lng', 11, 8)->nullable();
            $table->timestamp('checked_in_at');
            $table->boolean('inside_geofence')->default(false);
            $table->decimal('distance_from_site', 8, 2)->nullable(); // in meters
            $table->timestamps();

            $table->index(['shift_id', 'checked_in_at']);
            $table->index(['user_id', 'checked_in_at']);
            $table->index(['inside_geofence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkins');
    }
};
