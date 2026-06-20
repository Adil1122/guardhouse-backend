<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_message_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_message_id')->constrained('team_messages')->cascadeOnDelete();
            $table->foreignId('thread_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_message_replies');
    }
};
