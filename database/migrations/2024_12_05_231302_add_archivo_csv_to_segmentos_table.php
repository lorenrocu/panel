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
        Schema::table('segmentos', function (Blueprint $table) {
            $table->string('archivo_csv')->nullable()->after('tipo_de_segmento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('segmentos', function (Blueprint $table) {
            $table->dropColumn('archivo_csv');
        });
    }
};
