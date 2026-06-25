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
        Schema::create('data', function (Blueprint $table) {
            $table->id();
            $table->string('category')->index();
            $table->decimal('value', 15, 2);
            $table->date('date')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('created_at');
            $table->index(['category', 'date']);
            $table->index(['status', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data');
    }
};
