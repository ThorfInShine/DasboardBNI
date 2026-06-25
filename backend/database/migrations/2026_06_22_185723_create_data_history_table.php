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
        Schema::create('data_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_id')->constrained('data')->onDelete('cascade');
            $table->enum('action', ['created', 'updated', 'deleted']);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('data_id');
            $table->index('changed_by');
            $table->index('created_at');
            $table->index(['data_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_history');
    }
};
