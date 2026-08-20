<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacity_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('period_type', 10);
            $table->smallInteger('period_year');
            $table->tinyInteger('period_month')->nullable();
            $table->tinyInteger('period_quarter')->nullable();
            $table->decimal('h_base', 8, 2);
            $table->decimal('h_leave', 8, 2);
            $table->decimal('h_poss', 8, 2);
            $table->decimal('u_target', 4, 3);
            $table->decimal('h_thresh', 8, 2);
            $table->decimal('h_prod', 8, 2);
            $table->decimal('h_non_prod', 8, 2);
            $table->decimal('performance_percentage', 6, 2)->nullable();
            $table->string('performance_tier', 20);
            $table->decimal('effective_availability_hours', 8, 2);
            $table->dateTime('computed_at');
            $table->timestamps();

            $table->unique(['user_id', 'period_type', 'period_year', 'period_month', 'period_quarter'], 'capacity_metrics_unique_period');
            $table->index(['period_type', 'period_year', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacity_metrics');
    }
};
