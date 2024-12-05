<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifySegmentoInContactosTable extends Migration
{
    public function up()
    {
        Schema::table('contactos', function (Blueprint $table) {
            // Eliminar la columna 'segmento' si existe (esto depende de tu estructura actual)
            $table->dropColumn('segmento');
            
            // Agregar la columna 'id_segmento' como clave foránea que referencia la tabla 'segmentos'
            $table->foreignId('id_segmento')->constrained('segmentos')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('contactos', function (Blueprint $table) {
            // Si la migración se revierte, volveremos a agregar la columna 'segmento'
            $table->string('segmento')->nullable();

            // Eliminar la columna 'id_segmento' y su relación
            $table->dropForeign(['id_segmento']);
            $table->dropColumn('id_segmento');
        });
    }
}
