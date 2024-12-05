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
        Schema::table('contactos', function (Blueprint $table) {
            // Primero eliminar la llave foránea
            $table->dropForeign('contactos_id_segmento_foreign');
            
            // Luego eliminar la columna
            $table->dropColumn('id_segmento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_segmento')->nullable();
        });
    }
};
