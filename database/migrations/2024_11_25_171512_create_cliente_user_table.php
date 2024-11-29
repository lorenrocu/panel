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
        Schema::create('cliente_user', function (Blueprint $table) {
            $table->id(); // ID de la tabla pivot
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // FK hacia la tabla users
            
            // Definir el campo cliente_id que será una llave foránea a la columna id_cliente de la tabla clientes
            $table->unsignedBigInteger('cliente_id'); // Define la columna cliente_id como unsignedBigInteger
    
            // Establecer la llave foránea hacia la columna id_cliente de la tabla clientes
            $table->foreign('cliente_id')
                  ->references('id_cliente') // Referencia la columna id_cliente
                  ->on('clientes') // Define la tabla referenciada
                  ->onDelete('cascade'); // Borra en cascada si se elimina un cliente
    
            $table->timestamps(); // Opcional, para created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_user');
    }
};
