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
        Schema::create('atributos_personalizados', function (Blueprint $table) {
            $table->id(); // ID del registro en la tabla
            $table->unsignedBigInteger('id_account'); // ID del account en Chatwoot
            $table->string('nombre_atributo'); // Nombre del atributo personalizado
            $table->text('valor_atributo'); // Valor del atributo personalizado
            $table->timestamps(); // Fechas de creación y actualización
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atributos_personalizados');
    }
};
