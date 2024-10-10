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
        Schema::table('atributos_personalizados', function (Blueprint $table) {
            $table->unsignedBigInteger('id_cliente')->nullable(); // ID del cliente
            $table->foreign('id_cliente')->references('id_cliente')->on('clientes'); // Clave foránea
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atributos_personalizados', function (Blueprint $table) {
            //
        });
    }
};
