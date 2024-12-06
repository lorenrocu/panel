<?php

namespace App\Jobs;

use App\Models\Programacion;
use App\Models\Segmento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EjecutarProgramacion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $programacion;

    /**
     * Crear una nueva instancia del Job.
     *
     * @param  \App\Models\Programacion  $programacion
     * @return void
     */
    public function __construct(Programacion $programacion)
    {
        $this->programacion = $programacion;
    }

    /**
     * Ejecutar el Job.
     *
     * @return void
     */
    public function handle()
    {
        $programacion = $this->programacion;

        // Verificamos si la fecha programada es en el pasado y si el estado está pendiente
        if ($programacion->estado == 0 && Carbon::now()->greaterThanOrEqualTo($programacion->fecha_programada)) {
            try {
                // Lógica para ejecutar la programación
                if ($programacion->tipo === 'Segmento') {
                    $segmento = Segmento::find($programacion->segmento_id); // Suponiendo que tenemos un campo segmento_id
                    // Aquí se puede ejecutar la acción relacionada al segmento, como enviar un mensaje
                    Log::info("Ejecutando programación para el segmento: " . $segmento->nombre);
                }

                // Actualizar el estado de la programación a "Procesado"
                $programacion->update([
                    'estado' => 1, // Cambiar a "procesado"
                ]);

                Log::info("Programación con ID {$programacion->id} procesada correctamente.");

            } catch (\Exception $e) {
                Log::error("Error al procesar la programación con ID {$programacion->id}: " . $e->getMessage());
            }
        }
    }
}
