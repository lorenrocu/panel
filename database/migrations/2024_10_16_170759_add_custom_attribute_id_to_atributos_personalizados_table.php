<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomAttributeIdToAtributosPersonalizadosTable extends Migration
{
    public function up()
    {
        Schema::table('atributos_personalizados', function (Blueprint $table) {
            $table->unsignedBigInteger('custom_attribute_id')->nullable()->after('valor_atributo');
        });
    }

    public function down()
    {
        Schema::table('atributos_personalizados', function (Blueprint $table) {
            $table->dropColumn('custom_attribute_id');
        });
    }
}
