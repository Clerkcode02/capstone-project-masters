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
            $table->string('employee_code', 30)->unique()->nullable();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email', 150)->unique();
            $table->string('password', 255);
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->restrictOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            $table->index('role_id');
            $table->index('designation_id');
            $table->index('manager_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
