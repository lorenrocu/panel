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
        Schema::create('contacto_segmento', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contacto_id');
            $table->unsignedBigInteger('segmento_id');
            // $table->timestamps();
    
            // Llaves foráneas para mantener integridad referencial
            $table->foreign('contacto_id')->references('id')->on('contactos')->onDelete('cascade');
            $table->foreign('segmento_id')->references('id')->on('segmentos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacto_segmento');
    }
};
