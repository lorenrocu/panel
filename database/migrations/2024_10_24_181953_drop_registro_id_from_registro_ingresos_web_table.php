<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropRegistroIdFromRegistroIngresosWebTable extends Migration
{
    public function up()
    {
        Schema::table('registro_ingresos_web', function (Blueprint $table) {
            $table->dropColumn('registro_id'); // Eliminar la columna registro_id
        });
    }

    public function down()
    {
        Schema::table('registro_ingresos_web', function (Blueprint $table) {
            $table->integer('registro_id'); // Para restaurar la columna en caso de rollback
        });
    }
}
