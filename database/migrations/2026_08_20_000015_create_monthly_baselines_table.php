<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_baselines', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('period_year');
            $table->tinyInteger('period_month');
            $table->decimal('baseline_hours', 8, 2);
            $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['period_year', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_baselines');
    }
};
