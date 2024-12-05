<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClienteIdToContactosTable extends Migration
{
    public function up()
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->foreignId('cliente_id')->constrained('clientes', 'id_cliente');
        });
    }

    public function down()
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropColumn('cliente_id');
        });
    }
}
