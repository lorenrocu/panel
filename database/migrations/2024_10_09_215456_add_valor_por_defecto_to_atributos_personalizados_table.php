<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddValorPorDefectoToAtributosPersonalizadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('atributos_personalizados', function (Blueprint $table) {
            $table->string('valor_por_defecto')->nullable()->after('valor_atributo'); // Agregar la columna después de 'valor_atributo'
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('atributos_personalizados', function (Blueprint $table) {
            $table->dropColumn('valor_por_defecto'); // Eliminar la columna en caso de rollback
        });
    }
}
