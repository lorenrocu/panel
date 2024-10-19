<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cliente;
use App\Models\AtributoPersonalizado;
use Illuminate\Support\Facades\Http;

class CrearAtributosChatwoot extends Command
{
    protected $signature = 'create:chatwoot-attributes {--id_cliente=}';

    protected $description = 'Crea los atributos personalizados de los clientes en Fasia con el orden actualizado';

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

            // Obtener los atributos personalizados del cliente ordenados según tu orden
            $atributosPersonalizados = $cliente->atributosPersonalizados()->orderBy('orden')->get();

            foreach ($atributosPersonalizados as $atributo) {
                $this->info("Creando atributo '{$atributo->nombre_atributo}' en Fasia.");

                $url = "{$baseUrl}/api/v1/accounts/{$cliente->id_account}/custom_attribute_definitions";

                // Construir las cabeceras de la petición
                $requestHeaders = [
                    'api_access_token' => $cliente->token,
                    'Accept' => 'application/json',
                ];

                // Determinar el tipo de atributo y los valores
                $valorAtributo = $atributo->valor_atributo;

                // Intentar decodificar el valor como JSON
                $decodedValue = json_decode($valorAtributo, true);

                if (is_array($decodedValue)) {
                    // Si es un array, asumimos que es una lista
                    $attributeDisplayType = 'list';
                    $attributeValues = $decodedValue;
                    $defaultValue = null; // Puedes ajustar esto si deseas un valor por defecto específico
                } else {
                    // No es un array, es un texto simple
                    $attributeDisplayType = 'text';
                    $attributeValues = [];
                    $defaultValue = $valorAtributo;
                }

                // Datos del atributo a crear
                $data = [
                    'attribute_display_name' => $atributo->nombre_atributo,
                    'attribute_key' => $atributo->attribute_key,
                    'attribute_display_type' => $attributeDisplayType,
                    'attribute_description' => $atributo->nombre_atributo,
                    'attribute_values' => $attributeValues,
                    'default_value' => $defaultValue,
                    'attribute_model' => 'contact_attribute', // O 'conversation_attribute' según corresponda
                ];

                // Enviar la petición
                $response = Http::withHeaders($requestHeaders)->post($url, $data);

                // Mostrar la respuesta
                if ($response->successful()) {
                    $this->info("Atributo '{$atributo->nombre_atributo}' creado exitosamente en fasia.");

                    // Actualizar el custom_attribute_id con el nuevo ID devuelto por fasia
                    $responseData = $response->json();
                    $atributo->custom_attribute_id = $responseData['id'] ?? null;
                    $atributo->save();
                } else {
                    $statusCode = $response->status();
                    $responseBody = $response->body();
                    $this->error("Error al crear el atributo '{$atributo->nombre_atributo}'. Código de estado: {$statusCode}. Respuesta: {$responseBody}");
                }
            }

            $this->info('Creación de atributos en Fasia completada.');
        }  catch (\Exception $e) {
            $this->error('Error al ejecutar el comando: ' . $e->getMessage());
            \Log::error('Error en CrearAtributosFasia: ' . $e->getMessage());
        }
    }
}
