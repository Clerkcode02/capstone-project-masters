<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('complexity_tier', 10);
            $table->tinyInteger('complexity_weight')->unsigned();
            $table->decimal('standard_hours', 8, 2);
            $table->decimal('actual_hours', 8, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->boolean('is_bottleneck')->default(false);
            $table->date('due_date')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['assigned_to', 'status']);
            $table->index(['complexity_tier', 'status']);
            $table->index('account_id');
            $table->index('due_date');
            $table->index('is_bottleneck');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
