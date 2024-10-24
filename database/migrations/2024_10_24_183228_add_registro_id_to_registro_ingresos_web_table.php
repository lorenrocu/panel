<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRegistroIdToRegistroIngresosWebTable extends Migration
{
    public function up()
    {
        Schema::table('registro_ingresos_web', function (Blueprint $table) {
            $table->integer('registro_id')->after('id_account'); // Agregar columna registro_id después de id_account
        });
    }

    public function down()
    {
        Schema::table('registro_ingresos_web', function (Blueprint $table) {
            $table->dropColumn('registro_id'); // Eliminar la columna registro_id en caso de rollback
        });
    }
}
