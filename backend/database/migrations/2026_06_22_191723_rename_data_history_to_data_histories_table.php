<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('data_history', 'data_histories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('data_histories', 'data_history');
    }
};
