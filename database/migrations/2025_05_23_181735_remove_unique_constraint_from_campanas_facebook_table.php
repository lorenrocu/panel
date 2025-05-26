<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Obtenemos el SchemaManager de la conexión actual
        $sm = DB::getDoctrineSchemaManager();
        // Si usas prefijos de tabla, haz:
        // $sm->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        // Listamos los índices de la tabla
        $indexes = $sm->listTableIndexes('campanas_facebook');

        // Nombre que Laravel generaría por convención:
        $keyName = 'campanas_facebook_id_cliente_id_campana_unique';

        if (isset($indexes[$keyName])) {
            // Si existe, lo borramos
            Schema::table('campanas_facebook', function (Blueprint $table) {
                $table->dropUnique(['id_cliente', 'id_campana']);
            });
        }
    }

    public function down(): void
    {
        // Al revertir, comprobamos que NO exista aún
        $sm = DB::getDoctrineSchemaManager();
        $indexes = $sm->listTableIndexes('campanas_facebook');
        $keyName = 'campanas_facebook_id_cliente_id_campana_unique';

        if (! isset($indexes[$keyName])) {
            Schema::table('campanas_facebook', function (Blueprint $table) {
                $table->unique(['id_cliente', 'id_campana']);
            });
        }
    }
};
