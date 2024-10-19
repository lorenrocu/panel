<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cliente;
use Illuminate\Support\Facades\Http;

class EliminarAtributosChatwoot extends Command
{
    protected $signature = 'delete:chatwoot-attributes {--id_cliente=}';

    protected $description = 'Elimina los atributos personalizados de los clientes en Chatwoot';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
                
            $idCliente = $this->option('id_cliente');

            if (!$idCliente) {
                $this->error('Debe proporcionar el ID del cliente usando la opción --id_cliente=');
                return;
            }

            $cliente = Cliente::find($idCliente);

            if (!$cliente) {
                $this->error('Cliente no encontrado con el ID proporcionado.');
                return;
            }

            $baseUrl = env('URL_CHATWOOT');

            // Obtener todos los atributos personalizados del cliente
            $atributosPersonalizados = $cliente->atributosPersonalizados;

            foreach ($atributosPersonalizados as $atributo) {
                $customAttributeId = $atributo->custom_attribute_id;

                if (!$customAttributeId) {
                    $this->error("El atributo '{$atributo->nombre_atributo}' no tiene 'custom_attribute_id'.");
                    continue;
                }

                // Usar custom_attribute_id en la URL
                $url = "{$baseUrl}/api/v1/accounts/{$cliente->id_account}/custom_attribute_definitions/{$customAttributeId}";

                $this->info("Enviando petición DELETE a URL: $url");

                // Construir las cabeceras de la petición
                $requestHeaders = [
                    'api_access_token' => $cliente->token,
                    'Accept' => 'application/json',
                ];

                // Enviar la petición
                $response = Http::withHeaders($requestHeaders)->delete($url);

                if ($response->successful()) {
                    // No eliminamos el atributo de la base de datos local
                    $this->info("Atributo '{$atributo->nombre_atributo}' eliminado en Chatwoot exitosamente.");
                } else {
                    $statusCode = $response->status();
                    $responseBody = $response->body();
                    $this->error("Error al eliminar el atributo '{$atributo->nombre_atributo}'. Código de estado: {$statusCode}. Respuesta: {$responseBody}");
                }
            }

            $this->info('Eliminación de atributos personalizada completada.');
        } catch (\Exception $e) {
            $this->error('Error al ejecutar el comando: ' . $e->getMessage());
            \Log::error('Error en EliminarAtributosChatwoot: ' . $e->getMessage());
        }
    }
}
