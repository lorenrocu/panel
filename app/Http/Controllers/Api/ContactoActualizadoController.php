<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Cliente;
use App\Models\LabelPersonalizado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;

class ContactoActualizadoController extends Controller
{
    /**
     * Maneja la solicitud de contacto actualizado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function contactoActualizado(Request $request)
    {
        // Obtener las URLs del .env dentro del método
        $urlChatwoot = env('URL_CHATWOOT');
        $urlPuppeter = env('API_PUPPETER');

        // Obtener todos los datos recibidos
        $data = $request->all();

        // Extraer el campo "id" a nivel raíz
        $id = $data['id'] ?? null;

        // Extraer los campos de "custom_attributes"
        $customAttributes = $data['custom_attributes'] ?? [];
        $tipoContacto = $customAttributes['tipo_contacto'] ?? 'N/A';
        $estadoContacto = $customAttributes['estado_contacto'] ?? 'N/A';

        // Extraer "account.id"
        $accountId = $data['account']['id'] ?? null;

        // Buscar el cliente en la tabla clientes basado en account.id
        $cliente = Cliente::where('id_account', $accountId)->first();

        if ($cliente) {
            $token = $cliente->token;
        } else {
            $token = 'N/A';
            Log::channel('chatwoot_api')->warning('Cliente no encontrado para account_id: ' . $accountId);
        }

        // **Funciones auxiliares**

        // Función para procesar atributos y actualizar si es necesario
        function procesarAtributo($attributeKey, $attributeValue, $accountId)
        {
            // Convertir el valor a minúsculas para comparación insensible a mayúsculas
            $attributeValueLower = mb_strtolower($attributeValue);

            // Si el valor es 'n/a' o 'sin seleccionar', no hacemos nada y retornamos el array existente
            if ($attributeValueLower === 'n/a' || $attributeValueLower === 'sin seleccionar') {
                Log::channel('chatwoot_api')->info('El valor de ' . $attributeKey . ' es N/A o Sin Seleccionar, se omite el procesamiento.');
                // Obtener el atributo existente sin modificar
                $atributo = DB::table('atributos_personalizados')
                    ->where('id_account', $accountId)
                    ->where('attribute_key', $attributeKey)
                    ->first();

                if ($atributo) {
                    $valorAtributo = $atributo->valor_atributo;
                    $arrayAtributo = json_decode($valorAtributo, true);
                    return $arrayAtributo;
                } else {
                    return [];
                }
            }

            $atributo = DB::table('atributos_personalizados')
                ->where('id_account', $accountId)
                ->where('attribute_key', $attributeKey)
                ->first();

            if ($atributo) {
                $valorAtributo = $atributo->valor_atributo;
                Log::channel('chatwoot_api')->info('Valor del atributo ' . $attributeKey . ':', ['valor_atributo' => $valorAtributo]);

                // Parsear el JSON para obtener el array
                $arrayAtributo = json_decode($valorAtributo, true);

                // Verificar si el valor recibido no está en el array
                if (!in_array($attributeValue, $arrayAtributo)) {
                    // Agregar el nuevo valor al array
                    $arrayAtributo[] = $attributeValue;

                    // Actualizar el valor_atributo en la base de datos
                    DB::table('atributos_personalizados')
                        ->where('id_account', $accountId)
                        ->where('attribute_key', $attributeKey)
                        ->update(['valor_atributo' => json_encode($arrayAtributo)]);

                    Log::channel('chatwoot_api')->info('Nuevo ' . $attributeKey . ' agregado a atributos_personalizados', [$attributeKey => $attributeValue]);
                }

                return $arrayAtributo;
            } else {
                Log::channel('chatwoot_api')->warning('No se encontró el atributo ' . $attributeKey . ' para account_id: ' . $accountId);
                return [];
            }
        }

        // **Función actualizada para generar códigos de labels**

        // Función para generar códigos de labels, verificando labels existentes
        function generarCodigosLabels($arrayAtributo, $prefix, &$existingLabelsMapping)
        {
            $codigosLabels = [];
            foreach ($arrayAtributo as $valor) {
                $valorLower = mb_strtolower($valor);
                $codigo = $prefix . '_' . str_replace(' ', '_', $valorLower);
                
                // Verificar si el código ya existe en el mapping
                if (!isset($existingLabelsMapping[$codigo])) {
                    $existingLabelsMapping[$codigo] = $valor;
                }
                
                $codigosLabels[] = $codigo;
            }
            return $codigosLabels;
        }

        // **Procesar los atributos y generar códigos de labels**

        // Mapeo de valores estáticos para tipo_contacto
        $valoresEstaticosTipo = [
            'prospecto' => 'tipo_contacto_prospecto',
            'cliente' => 'tipo_contacto_cliente',
            'excluido' => 'tipo_contacto_excluido',
            'n/a' => null,
            'sin seleccionar' => null
        ];

        // Mapeo de valores estáticos para estado_contacto
        $valoresEstaticosEstado = [
            'activo' => 'estado_contacto_activo',
            'inactivo' => 'estado_contacto_inactivo',
            'n/a' => null,
            'sin seleccionar' => null
        ];

        // Mapeo para almacenar los labels existentes
        $existingLabelsMapping = [];

        // Procesar tipo_contacto
        $arrayTipoContacto = procesarAtributo('tipo_contacto', $tipoContacto, $accountId);
        $codigosLabelsTipo = generarCodigosLabels($arrayTipoContacto, 'tipo_contacto', $existingLabelsMapping);

        // Procesar estado_contacto
        $arrayEstadoContacto = procesarAtributo('estado_contacto', $estadoContacto, $accountId);
        $codigosLabelsEstado = generarCodigosLabels($arrayEstadoContacto, 'estado_contacto', $existingLabelsMapping);

        // **Verificar y crear labels en la base de datos**

        // Verificar labels existentes en la base de datos
        $existingLabels = DB::connection('pgsql_chatwoot')
            ->table('labels')
            ->whereIn('title', array_keys($existingLabelsMapping))
            ->pluck('title')
            ->toArray();

        // Filtrar labels que no existen
        $newLabels = array_diff(array_keys($existingLabelsMapping), $existingLabels);

        // Preparar datos para insertar nuevos labels
        $datosLabelsRemoto = [];
        foreach ($newLabels as $codigo) {
            $datosLabelsRemoto[] = [
                'title' => $codigo,
                'description' => $existingLabelsMapping[$codigo],
                'color' => '#1f93ff',
                'show_on_sidebar' => true,
                'account_id' => $accountId,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Insertar nuevos labels en la base de datos
        $labelInserted = false;
        if (!empty($datosLabelsRemoto)) {
            DB::connection('pgsql_chatwoot')->table('labels')->insert($datosLabelsRemoto);
            $labelInserted = true;

            // Registrar en el log los labels insertados
            foreach ($datosLabelsRemoto as $labelData) {
                Log::channel('chatwoot_api')->info('Label insertado correctamente en PostgreSQL.', ['label' => $labelData['title']]);
            }
        }

        // **Llamar a la API clear-cache si se insertó algún label nuevo**
        if ($labelInserted) {
            try {
                $apiResponse = Http::post("{$urlPuppeter}/api/clear-cache", [
                    'account_id' => $accountId,
                ]);

                if ($apiResponse->successful()) {
                    Log::channel('chatwoot_api')->info('Llamada a la API clear-cache realizada con éxito.', [
                        'account_id' => $accountId,
                    ]);
                } else {
                    Log::channel('chatwoot_api')->error('Error al llamar a la API clear-cache.', [
                        'account_id' => $accountId,
                        'response' => $apiResponse->body(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::channel('chatwoot_api')->error('Excepción al llamar a la API clear-cache: ' . $e->getMessage());
            }
        }

        // **Procesar el contacto actual**

        // Convertir los valores a minúsculas para comparación
        $tipoContactoLower = mb_strtolower($tipoContacto);
        $estadoContactoLower = mb_strtolower($estadoContacto);

        // Obtener los códigos de labels para el contacto actual, omitiendo 'N/A' y 'Sin Seleccionar'
        $omitidos = ['n/a', 'sin seleccionar'];

        $valorLabelTipo = (!in_array($tipoContactoLower, $omitidos)) ? ($valoresEstaticosTipo[$tipoContactoLower] ?? null) : null;
        $valorLabelEstado = (!in_array($estadoContactoLower, $omitidos)) ? ($valoresEstaticosEstado[$estadoContactoLower] ?? null) : null;

        // Ahora hacemos la llamada a la API para obtener el id_conversation
        try {
            $response = Http::withHeaders([
                'api_access_token' => $token,
            ])->get("{$urlChatwoot}/api/v1/accounts/{$accountId}/contacts/{$id}/conversations");

            // Verificar si la respuesta es exitosa
            if ($response->successful()) {
                $conversations = $response->json()['payload'] ?? [];
                $idConversation = $conversations[0]['id'] ?? null;

                if ($idConversation) {
                    Log::channel('chatwoot_api')->info('Contacto Actualizado:', [
                        'id' => $id,
                        'account_id' => $accountId,
                        'tipo_contacto' => $tipoContacto,
                        'estado_contacto' => $estadoContacto,
                        'id_conversation' => $idConversation,
                        'token' => $token,
                    ]);

                    // **Aplicar los labels a la conversación**
                    $labelsToSend = array_values(array_filter([$valorLabelTipo, $valorLabelEstado]));

                    // **Siempre enviar los labels a la API, incluso si el array está vacío**
                    $responseLabels = Http::withHeaders([
                        'api_access_token' => $token,
                    ])->post("{$urlChatwoot}/api/v1/accounts/{$accountId}/conversations/{$idConversation}/labels", ['labels' => $labelsToSend]);

                    if ($responseLabels->successful()) {
                        Log::channel('chatwoot_api')->info('Labels enviados correctamente a la API para la conversación: ' . $idConversation, [
                            'labels' => $labelsToSend,
                        ]);
                    } else {
                        Log::channel('chatwoot_api')->error('Error al enviar los labels a la API.', [
                            'response' => $responseLabels->body(),
                        ]);
                    }
                } else {
                    Log::channel('chatwoot_api')->warning('No se encontró id_conversation para contact_id: ' . $id);
                }
            } else {
                Log::channel('chatwoot_api')->error('Error al obtener las conversaciones de la API.');
            }
        } catch (\Exception $e) {
            Log::channel('chatwoot_api')->error('Error en la solicitud HTTP: ' . $e->getMessage());
        }

        // Actualizar el nombre del contacto si el tipo_contacto ha cambiado
        if (isset($data['changed_attributes'])) {
            foreach ($data['changed_attributes'] as $change) {
                if (isset($change['custom_attributes'])) {
                    $previousValue = $change['custom_attributes']['previous_value'] ?? [];
                    $currentValue = $change['custom_attributes']['current_value'] ?? [];
                    
                    // Verificar si el tipo_contacto ha cambiado
                    if (isset($previousValue['tipo_contacto']) && isset($currentValue['tipo_contacto']) 
                        && $previousValue['tipo_contacto'] !== $currentValue['tipo_contacto']) {
                        
                        // Obtener el nombre actual
                        $currentName = $data['name'] ?? '';
                        
                        // Extraer la parte antes del guión
                        $nameParts = explode(' - ', $currentName);
                        if (count($nameParts) > 1) {
                            // Construir el nuevo nombre con el nuevo tipo_contacto
                            $newName = $nameParts[0] . ' - ' . $currentValue['tipo_contacto'];
                            
                            // Usar el comando Artisan para actualizar el nombre
                            try {
                                $exitCode = Artisan::call('chatwoot:update-contact-name', [
                                    'account_id' => $accountId,
                                    'contact_id' => $id,
                                    'name' => $newName
                                ]);

                                if ($exitCode === 0) {
                                    Log::channel('chatwoot_api')->info('Nombre del contacto actualizado correctamente usando el comando', [
                                        'old_name' => $currentName,
                                        'new_name' => $newName,
                                        'tipo_contacto' => $currentValue['tipo_contacto']
                                    ]);
                                } else {
                                    Log::channel('chatwoot_api')->error('Error al actualizar el nombre del contacto usando el comando', [
                                        'exit_code' => $exitCode
                                    ]);
                                }
                            } catch (\Exception $e) {
                                Log::channel('chatwoot_api')->error('Error al ejecutar el comando de actualización: ' . $e->getMessage());
                            }
                        }
                    }
                }
            }
        }

        return response()->json([
            'mensaje' => 'Datos de contacto actualizados y registrados correctamente.'
        ], 200);
    }

    /**
     * Maneja la creación de un nuevo contacto.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function contactoCreado(Request $request)
    {
        try {
            $data = $request->all();
            Log::info('Datos recibidos en contactoCreado:', $data);

            // Extraer los datos necesarios
            $accountId = $data['account']['id'] ?? null;
            $contactId = $data['id'] ?? null;
            $name = $data['name'] ?? null;

            if (!$accountId || !$contactId || !$name) {
                Log::error('Faltan datos requeridos', [
                    'account_id' => $accountId,
                    'contact_id' => $contactId,
                    'name' => $name
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Faltan datos requeridos'
                ], 400);
            }

            // Ejecutar el comando para actualizar el nombre
            $exitCode = Artisan::call('chatwoot:update-contact-name', [
                'account_id' => $accountId,
                'contact_id' => $contactId,
                'name' => $name
            ]);

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contacto actualizado exitosamente'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar el contacto'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error en contactoCreado:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
