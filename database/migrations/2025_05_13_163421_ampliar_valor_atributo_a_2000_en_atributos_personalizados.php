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
            // Cambiamos el tipo de la columna valor_atributo a TEXT
            $table->text('valor_atributo')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atributos_personalizados', function (Blueprint $table) {
            // Si quisieras volver atrás, lo devolvemos a VARCHAR(255)
            $table->string('valor_atributo', 255)->nullable()->change();
        });
    }
};
