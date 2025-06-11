<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Cliente;

class UpdateContactNameChatwoot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chatwoot:update-contact-name {account_id} {contact_id} {name} {--type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el nombre de un contacto en Chatwoot. Si se proporciona --type, actualiza el tipo de contacto.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accountId = $this->argument('account_id');
        $contactId = $this->argument('contact_id');
        $name = $this->argument('name');
        $type = $this->option('type');

        // Buscar el cliente por id_account
        $cliente = Cliente::where('id_account', $accountId)->first();

        if (!$cliente) {
            $this->error("No se encontró un cliente con id_account: {$accountId}");
            Log::error("No se encontró un cliente con id_account: {$accountId}");
            return 1;
        }

        // Si se proporciona un tipo, actualizar el nombre con el tipo
        if ($type) {
            // Usar regex para reemplazar el tipo de contacto
            // Primero intentamos el patrón con dos guiones (ej: "Nombre - Empresa - Tipo")
            $newName = preg_replace('/\s*-\s*[^-]+\s*-\s*[^-]+$/', ' - ' . $type, $name);
            
            // Si no hubo cambios (no encontró dos guiones), intentamos con un solo guión
            if ($newName === $name) {
                $newName = preg_replace('/\s*-\s*[^-]+$/', ' - ' . $type, $name);
            }
        } else {
            // Si no se proporciona tipo, agregar "- Prospecto" si no existe
            if (!preg_match('/\s*-\s*Prospecto$/', $name)) {
                $newName = "{$name} - Prospecto";
            } else {
                $newName = $name;
            }
        }

        try {
            // Realizar la petición PUT a la API de Chatwoot
            $response = Http::withHeaders([
                'api_access_token' => $cliente->token
            ])->put("https://app.fasiacrm.com/api/v1/accounts/{$accountId}/contacts/{$contactId}", [
                'name' => $newName
            ]);

            if ($response->successful()) {
                $this->info("Nombre actualizado exitosamente para el contacto {$contactId}");
                Log::info("Nombre actualizado exitosamente", [
                    'account_id' => $accountId,
                    'contact_id' => $contactId,
                    'new_name' => $newName
                ]);
                return 0;
            } else {
                $this->error("Error al actualizar el nombre: " . $response->body());
                Log::error("Error al actualizar el nombre", [
                    'account_id' => $accountId,
                    'contact_id' => $contactId,
                    'response' => $response->body()
                ]);
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error en la petición: " . $e->getMessage());
            Log::error("Error en la petición", [
                'account_id' => $accountId,
                'contact_id' => $contactId,
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }
} 