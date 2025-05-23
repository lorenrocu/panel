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
        Schema::table('campanas_facebook', function (Blueprint $table) {
            if (!Schema::hasColumn('campanas_facebook', 'id_account')) {
                $table->string('id_account')->nullable();
            }
            if (!Schema::hasColumn('campanas_facebook', 'id_campana')) {
                $table->string('id_campana')->nullable();
            }
            if (!Schema::hasColumn('campanas_facebook', 'utm_source')) {
                $table->string('utm_source')->nullable();
            }
            if (!Schema::hasColumn('campanas_facebook', 'utm_medium')) {
                $table->string('utm_medium')->nullable();
            }
            if (!Schema::hasColumn('campanas_facebook', 'utm_term')) {
                $table->string('utm_term')->nullable();
            }
            if (!Schema::hasColumn('campanas_facebook', 'utm_content')) {
                $table->string('utm_content')->nullable();
            }
            if (!Schema::hasColumn('campanas_facebook', 'utm_campaign')) {
                $table->string('utm_campaign')->nullable();
            }
            if (Schema::hasColumn('campanas_facebook', 'nombre_campana')) {
                $table->dropColumn('nombre_campana');
            }

            // $table->unique(['id_cliente', 'id_campana']); // Comentado para permitir duplicados según nuevo requisito
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campanas_facebook', function (Blueprint $table) {
            // $table->dropUnique(['id_cliente', 'id_campana']); // Comentado porque la restricción unique se elimina
            $table->dropColumn([
                'id_account',
                'id_campana',
                'utm_source',
                'utm_medium',
                'utm_term',
                'utm_content',
                'utm_campaign',
            ]);
        });
    }
};
