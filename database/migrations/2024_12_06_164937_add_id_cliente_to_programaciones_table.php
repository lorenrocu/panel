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
            $table->unsignedBigInteger('id_cliente')->after('estado'); // Campo id_cliente
            $table->foreign('id_cliente')->references('id_cliente')->on('clientes')->onDelete('cascade'); // Relación con la tabla clientes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programaciones', function (Blueprint $table) {
            $table->dropForeign(['id_cliente']); // Eliminar la restricción de la clave foránea
            $table->dropColumn('id_cliente'); // Eliminar el campo id_cliente
        });
    }
};
