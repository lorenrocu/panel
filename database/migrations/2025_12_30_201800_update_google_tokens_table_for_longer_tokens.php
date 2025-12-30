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
        Schema::table('google_tokens', function (Blueprint $table) {
            // Change access_token and refresh_token from VARCHAR(255) to TEXT
            // to accommodate longer Google OAuth tokens
            $table->text('access_token')->change();
            $table->text('refresh_token')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('google_tokens', function (Blueprint $table) {
            // Revert back to string (VARCHAR 255)
            $table->string('access_token')->change();
            $table->string('refresh_token')->nullable()->change();
        });
    }
};
