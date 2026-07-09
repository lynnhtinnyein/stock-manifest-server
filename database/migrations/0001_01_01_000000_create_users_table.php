<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nickname');
            $table->string('username')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->enum('role', ['SUPER_ADMIN', 'ADMIN', 'USER'])->default('USER');
            $table->boolean('is_suspended')->default(false);

            $table->unsignedBigInteger('suspended_by')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->foreign('suspended_by')->references('id')->on('users')->onDelete('set null');

            $table->unsignedBigInteger('restored_by')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->foreign('restored_by')->references('id')->on('users')->onDelete('set null');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
    }
};
