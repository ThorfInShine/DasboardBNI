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
        Schema::table('import_histories', function (Blueprint $table) {
            $table->integer('warning_count')->default(0)->after('error_count');
            $table->json('warnings')->nullable()->after('errors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_histories', function (Blueprint $table) {
            $table->dropColumn(['warning_count', 'warnings']);
        });
    }
};
