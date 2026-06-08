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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [ 'admin', 'supervisor', 'security-officer', 'customer' ])->after('id');
            $table->dropColumn('name');
            $table->string('first_name', 50)->after('role');
            $table->string('last_name', 50)->after('first_name');
            $table->boolean('status')->default(false)->after('last_name');
            $table->string('image', 50)->nullable()->default(null)->after('status');
            $table->string('password', 100)->nullable()->default(null)->change();
            $table->string('api_token', 100)->nullable()->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role','first_name','last_name','status','image','api_token']);
            $table->string('name');
            $table->string('password')->change();
        });
    }
};
