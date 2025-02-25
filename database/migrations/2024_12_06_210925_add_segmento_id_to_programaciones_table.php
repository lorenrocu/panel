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
        Schema::table('programaciones', function (Blueprint $table) {
            // Agregar campo segmento_id que puede ser nulo
            $table->unsignedBigInteger('segmento_id')->nullable()->after('estado');

            // Definir la relación con la tabla segmentos
            $table->foreign('segmento_id')->references('id')->on('segmentos')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programaciones', function (Blueprint $table) {
            // Eliminar la clave foránea y el campo segmento_id
            $table->dropForeign(['segmento_id']);
            $table->dropColumn('segmento_id');
        });
    }
};
