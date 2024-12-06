<?php

namespace App\Filament\Resources\SegmentoResource\Pages;

use App\Filament\Resources\SegmentoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use Illuminate\Support\Facades\Storage;

class CreateSegmento extends CreateRecord
{
    protected static string $resource = SegmentoResource::class;

    protected function afterCreate(): void
    {
        // Log de información sobre el segmento creado
        Log::info('afterCreate() disparado. ID del segmento: ' . $this->record->id);

        // Obtener el segmento recién creado
        $segmento = $this->record;

        Log::info('Tipo de segmento: ' . $segmento->tipo_de_segmento . ', Archivo CSV: ' . $segmento->archivo_csv);

        // Verifica que el tipo sea CSV y que el archivo esté disponible
        if ($segmento->tipo_de_segmento === 'csv' && $segmento->archivo_csv) {
            $csvFilePath = storage_path('app/' . $segmento->archivo_csv);

            // Verificar si el archivo existe en la ruta esperada
            if (!Storage::exists($segmento->archivo_csv)) {
                Log::error('El archivo CSV no existe: ' . $csvFilePath);
                return;
            }

            // Abrir el archivo CSV para leerlo
            try {
                $csv = Reader::createFromPath($csvFilePath, 'r');
                $csv->setHeaderOffset(0);  // Define el primer registro como encabezado

                $records = $csv->getRecords();  // Obtener los registros del CSV
            } catch (\Exception $e) {
                Log::error('Error al leer el archivo CSV: ' . $e->getMessage());
                return;
            }

            // Procesar cada fila del CSV y crear los contactos
            foreach ($records as $record) {
                // Verificar que los campos 'nombre' y 'celular' existan en la fila
                if (!isset($record['nombre']) || !isset($record['celular'])) {
                    Log::warning('Fila incompleta en CSV, omitiendo: ' . json_encode($record));
                    continue; // Si no tiene nombre o celular, omitir esta fila
                }

                // Crear el contacto con los datos del CSV
                $contactoData = [
                    'nombre' => $record['nombre'],
                    'celular' => $record['celular'],
                    // Asignar cliente_id a partir del segmento, si está disponible
                    'cliente_id' => $segmento->cliente_id,  // Usar el cliente_id asociado al segmento
                ];

                try {
                    $contacto = \App\Models\Contacto::create($contactoData);  // Crear el contacto

                    // Asociar el contacto con el segmento recién creado
                    $contacto->segmento()->attach($segmento->id);

                    Log::info('Contacto creado: ' . json_encode($contactoData));

                } catch (\Exception $e) {
                    Log::error('Error al crear contacto: ' . $e->getMessage() . ' con datos: ' . json_encode($contactoData));
                }
            }

            // Opcional: Borrar el archivo CSV después de procesarlo
            try {
                Storage::delete($segmento->archivo_csv);
                Log::info("Archivo CSV {$segmento->archivo_csv} borrado después de procesar.");
            } catch (\Exception $e) {
                Log::error('No se pudo borrar el archivo CSV: ' . $e->getMessage());
            }
        } else {
            Log::info('El segmento no es de tipo CSV o no tiene archivo CSV asociado, no se procesa nada.');
        }
    }
}
