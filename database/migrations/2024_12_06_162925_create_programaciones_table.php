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
        Schema::create('programaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo'); // Tipo de programación (puede ser un enum o string)
            $table->dateTime('fecha_programada'); // Un solo campo para fecha y hora
            $table->enum('estado', ['pendiente', 'ejecutado'])->default('pendiente'); // Estado de la programación
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programaciones');
    }
};
