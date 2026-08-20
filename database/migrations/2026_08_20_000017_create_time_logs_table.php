<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->date('log_date');
            $table->string('hour_type', 20);
            $table->integer('duration_minutes')->unsigned();
            $table->string('entry_method', 20);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->string('notes', 255)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'log_date']);
            $table->index(['user_id', 'hour_type', 'log_date']);
            $table->index('task_id');
            $table->index(['account_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_logs');
    }
};
