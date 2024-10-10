<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttributeKeyToAtributosPersonalizadosTable extends Migration
{
    /**
     * Ejecutar la migración para añadir la columna `attribute_key`.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('atributos_personalizados', function (Blueprint $table) {
            $table->text('attribute_key')->nullable()->after('nombre_atributo');
        });
    }

    /**
     * Revertir la migración para eliminar la columna `attribute_key`.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('atributos_personalizados', function (Blueprint $table) {
            $table->dropColumn('attribute_key');
        });
    }
}
