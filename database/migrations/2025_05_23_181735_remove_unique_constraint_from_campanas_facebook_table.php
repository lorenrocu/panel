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
        Schema::table('campanas_facebook', function (Blueprint $table) {
            $table->dropUnique(['id_cliente', 'id_campana']); // Elimina la restricción UNIQUE
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campanas_facebook', function (Blueprint $table) {
            $table->unique(['id_cliente', 'id_campana']); // Vuelve a crear la restricción UNIQUE si se revierte
        });
    }
};
