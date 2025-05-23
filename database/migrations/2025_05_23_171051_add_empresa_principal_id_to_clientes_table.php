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
        Schema::table('clientes', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_principal_id')->nullable()->after('id_plan');
            $table->foreign('empresa_principal_id')->references('id_cliente')->on('clientes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['empresa_principal_id']);
            $table->dropColumn('empresa_principal_id');
        });
    }
};
