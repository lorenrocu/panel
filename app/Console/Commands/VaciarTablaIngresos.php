<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VaciarTablaIngresos extends Command
{
    // Nombre y descripción del comando
    protected $signature = 'tabla:vaciar-ingresos';
    protected $description = 'Vacia la tabla registro_ingresos_web';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Vaciar la tabla
        DB::table('registro_ingresos_web')->truncate();

        // Mensaje para confirmar que el comando fue ejecutado
        $this->info('La tabla registro_ingresos_web ha sido vaciada correctamente.');
    }
}
