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
        Schema::create('campanas_facebook', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_cliente');  // Ajusta esto según sea necesario
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campanas_facebook', function (Blueprint $table) {
            // Elimina la clave foránea y la columna en caso de revertir la migración
            $table->dropForeign(['id_cliente']);
            $table->dropColumn('id_cliente');
        });
    }
};
