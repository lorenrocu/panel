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
            // Primero agrega la columna id_cliente
            $table->unsignedBigInteger('id_cliente')->nullable();  // Puedes ajustar nullable según sea necesario

            // Luego define la clave foránea
            $table->foreign('id_cliente')->references('id_cliente')->on('clientes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campanas_facebook', function (Blueprint $table) {
            // Elimina la clave foránea y la columna en caso de revertir la migración
            $table->dropForeign(['id_cliente']);
            $table->dropColumn('id_cliente');
        });
    }
};
