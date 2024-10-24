<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegistroIngresosWebTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('registro_ingresos_web', function (Blueprint $table) {
            $table->id(); // Esto ya crea la columna `id` como clave primaria auto-incremental
            $table->integer('id_account'); // El campo para id_account
            $table->integer('registro_id'); // Renombra el campo de `id` a `registro_id` (u otro nombre que prefieras)
            $table->json('utms'); // El campo para utms como tipo JSON
            $table->integer('hora'); // El campo para hora (suponiendo que es un entero)
            $table->date('fecha'); // El campo para fecha
            $table->timestamps(); // Esto agregará created_at y updated_at automáticamente
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('registro_ingresos_web');
    }
}
