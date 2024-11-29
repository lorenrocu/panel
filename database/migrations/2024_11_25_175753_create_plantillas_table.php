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
        Schema::create('plantillas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Columna nombre
            $table->text('mensaje'); // Columna mensaje
            $table->string('imagen')->nullable(); // Columna imagen, puede ser nula
            $table->unsignedBigInteger('id_cliente'); // Columna id_cliente para la referencia a la tabla clientes
            $table->timestamps();
    
            // Relación con la tabla clientes
            $table->foreign('id_cliente')->references('id_cliente')->on('clientes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantillas');
    }
};
