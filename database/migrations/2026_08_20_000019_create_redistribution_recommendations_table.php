<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redistribution_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('suggested_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('trigger_type', 30);
            $table->decimal('actual_hours', 8, 2);
            $table->decimal('historical_avg_hours', 8, 2)->nullable();
            $table->decimal('variance_percentage', 6, 2)->nullable();
            $table->smallInteger('from_workload_score');
            $table->smallInteger('suggested_workload_score')->nullable();
            $table->string('basis', 30);
            $table->string('reason', 500);
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redistribution_recommendations');
    }
};
