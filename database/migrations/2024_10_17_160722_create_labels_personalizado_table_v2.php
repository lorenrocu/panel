<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLabelsPersonalizadoTableV2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('labels_personalizado', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_account');
            $table->string('valor_label');
            $table->unsignedBigInteger('id_cliente');

            // Relación con la tabla clientes
            $table->foreign('id_cliente')->references('id_cliente')->on('clientes')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('labels_personalizado');
    }
}
