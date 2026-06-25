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
        Schema::create('import_histories', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->enum('status', ['success', 'failed', 'partial'])->default('success');
            $table->integer('success_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->integer('total_rows')->default(0);
            $table->json('errors')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_histories');
    }
};
