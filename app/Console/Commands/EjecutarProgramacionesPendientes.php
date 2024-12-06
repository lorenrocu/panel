<?php

namespace App\Console\Commands;

use App\Models\Programacion;
use App\Jobs\EjecutarProgramacion;
use Illuminate\Console\Command;
use Carbon\Carbon;

class EjecutarProgramacionesPendientes extends Command
{
    /**
     * El nombre y la firma del comando.
     *
     * @var string
     */
    protected $signature = 'programaciones:ejecutar';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Ejecuta las programaciones pendientes que están listas para ser procesadas';

    /**
     * Ejecutar el comando.
     *
     * @return void
     */
    public function handle()
    {
        // Obtenemos las programaciones pendientes cuya fecha ya haya llegado
        $programaciones = Programacion::where('estado', 0) // Pendientes
            ->where('fecha_programada', '<=', Carbon::now()) // Ya han pasado
            ->get();

        foreach ($programaciones as $programacion) {
            // Encolamos el Job para ejecutar la programación
            EjecutarProgramacion::dispatch($programacion);
        }

        $this->info('Todas las programaciones pendientes han sido encoladas.');
    }
}
