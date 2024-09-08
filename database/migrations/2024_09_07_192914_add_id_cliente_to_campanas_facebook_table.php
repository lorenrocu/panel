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
            // Eliminar la línea que intenta agregar la columna
            // Solo agregar la clave foránea si la columna ya existe
            $table->foreign('id_cliente')->references('id_cliente')->on('clientes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campanas_facebook', function (Blueprint $table) {
            //
        });
    }
};
