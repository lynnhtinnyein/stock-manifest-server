<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('value')->unique();
            $table->boolean('is_admin_default')->default(false);

            //need one of a user role to have this permission
            $table->json('accessible_roles')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->smallInteger('risk_level')->default(1);
            $table->timestamps();
        });

        Schema::create('permission_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->foreignId('dependency_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['permission_id', 'dependency_id']);
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_user');
        Schema::dropIfExists('permission_dependencies');
    }
};
