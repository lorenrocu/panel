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

        // Extraer "account.id"
        $accountId = $data['account']['id'] ?? null;

        // Extraer el nombre del contacto directamente del webhook
        $contactName = $data['name'] ?? '';
        
        // Extraer los campos de "custom_attributes"
        $customAttributes = $data['custom_attributes'] ?? [];
        $tipoContacto = $customAttributes['tipo_contacto'] ?? 'N/A';
        $estadoContacto = $customAttributes['estado_contacto'] ?? 'N/A';
        
        // Log para debugging
        Log::channel('chatwoot_api')->info('Datos recibidos en contactoActualizado:', [
            'contact_id' => $id,
            'account_id' => $accountId,
            'contact_name' => $contactName,
            'tipo_contacto' => $tipoContacto,
            'estado_contacto' => $estadoContacto
        ]);

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
            $valoresEstaticos = [];
            $contador = 1; // Iniciar el contador

            // Extraer códigos existentes para evitar duplicados
            $existingCodes = [];
            foreach ($existingLabelsMapping as $valorLower => $codigoLabel) {
                $parts = explode('_', $codigoLabel, 2);
                if (count($parts) == 2) {
                    $existingCodes[] = $parts[0]; // Solo el código (e.g., 't01')
                }
            }

            foreach ($arrayAtributo as $valor) {
                $valorLower = mb_strtolower($valor);

                // Omitir 'n/a' o 'sin seleccionar'
                if ($valorLower === 'n/a' || $valorLower === 'sin seleccionar') {
                    continue;
                }

                // Verificar si este valor de atributo ya tiene un código
                if (isset($existingLabelsMapping[$valorLower])) {
                    $codigoLabel = $existingLabelsMapping[$valorLower];
                } else {
                    // Asignar un nuevo código
                    // Encontrar el siguiente código disponible
                    do {
                        $nextCode = $prefix . str_pad($contador, 2, '0', STR_PAD_LEFT);
                        $contador++;
                    } while (in_array($nextCode, $existingCodes));

                    // Generar el label completo
                    $codigoLabel = $nextCode . '_' . strtolower(str_replace(' ', '-', $valor));

                    // Actualizar los mapeos
                    $existingLabelsMapping[$valorLower] = $codigoLabel;
                    $existingCodes[] = $nextCode;
                }

                $valoresEstaticos[$valorLower] = $codigoLabel;
            }

            return $valoresEstaticos;
        }

        // **Fin de la función actualizada**

        // **Procesamiento de atributos**

        // Procesar 'tipo_contacto' y 'estado_contacto'
        $arrayTipoContacto = procesarAtributo('tipo_contacto', $tipoContacto, $accountId);
        $arrayEstadoContacto = procesarAtributo('estado_contacto', $estadoContacto, $accountId);

        // **Construir mapeo de labels existentes**

        // Obtener labels existentes de las bases de datos local y remota
        $existingLocalLabels = LabelPersonalizado::where('id_account', $accountId)
            ->pluck('valor_label')
            ->toArray();

        $existingRemoteLabels = DB::connection('pgsql_chatwoot')
            ->table('labels')
            ->where('account_id', $accountId)
            ->pluck('title')
            ->toArray();

        // Combinar labels locales y remotos
        $allExistingLabels = array_unique(array_merge($existingLocalLabels, $existingRemoteLabels));

        // Construir un mapeo de valores de atributos a códigos de labels
        $existingLabelsMapping = [];

        foreach ($allExistingLabels as $labelTitle) {
            // Dividir el labelTitle en el primer '_'
            $parts = explode('_', $labelTitle, 2);
            if (count($parts) == 2) {
                $code = $parts[0];
                $valor = $parts[1];

                // Revertir las transformaciones para obtener el valor del atributo
                $valorAttribute = str_replace('-', ' ', $valor);
                $valorLower = mb_strtolower($valorAttribute);

                // Almacenar en el mapeo
                $existingLabelsMapping[$valorLower] = $labelTitle; // El título completo del label incluye código y valor
            }
        }

        // **Generar códigos de labels utilizando el mapeo existente**

        // Generar códigos de labels para 'tipo_contacto' y 'estado_contacto' usando el mapeo de labels existentes
        $valoresEstaticosTipo = generarCodigosLabels($arrayTipoContacto, 't', $existingLabelsMapping);
        $valoresEstaticosEstado = generarCodigosLabels($arrayEstadoContacto, 'e', $existingLabelsMapping);

        // **Continuación del código original**

        // Preparar labels necesarios
        $labelsNecesarios = array_merge($valoresEstaticosTipo, $valoresEstaticosEstado);

        // Determinar labels que faltan en la base de datos local
        $labelsFaltantesLocal = array_diff($labelsNecesarios, $existingLocalLabels);

        // Preparar datos para insertar en la base de datos local
        $datosLabelsLocal = [];
        foreach ($labelsFaltantesLocal as $codigoLabel) {
            $datosLabelsLocal[] = [
                'id_account' => $accountId,
                'valor_label' => $codigoLabel,
                'id_cliente' => $cliente ? $cliente->id_cliente : null,
            ];
        }

        // Insertar labels faltantes en la base de datos local
        if (!empty($datosLabelsLocal)) {
            LabelPersonalizado::insert($datosLabelsLocal);
        }

        // Determinar labels que faltan en la base de datos remota
        $labelsFaltantesRemoto = array_diff($labelsNecesarios, $existingRemoteLabels);

        // Preparar datos para insertar en la base de datos remota
        $datosLabelsRemoto = [];
        foreach ($labelsFaltantesRemoto as $codigoLabel) {
            // Generar un color aleatorio para el label
            $color = '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);

            $datosLabelsRemoto[] = [
                'title' => $codigoLabel,
                'description' => $codigoLabel,
                'color' => $color,
                'show_on_sidebar' => 't',
                'account_id' => $accountId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insertar labels faltantes en la base de datos remota
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

                    // **Actualizar el nombre del contacto si hay un tipo_contacto válido**
                    Log::channel('chatwoot_api')->info('Verificando actualización de nombre:', [
                        'tipo_contacto' => $tipoContacto,
                        'condicion' => ($tipoContacto !== 'N/A' && $tipoContacto !== 'Sin Seleccionar')
                    ]);
                    
                    if ($tipoContacto !== 'N/A' && $tipoContacto !== 'Sin Seleccionar') {
                        try {
                            Log::channel('chatwoot_api')->info('Iniciando actualización de nombre del contacto', [
                                'contact_id' => $id,
                                'account_id' => $accountId,
                                'contact_name_from_webhook' => $contactName,
                                'tipo_contacto' => $tipoContacto
                            ]);
                            
                            // Usar el nombre del webhook o asignar uno por defecto si está vacío
                            $nameToUpdate = !empty($contactName) ? $contactName : 'Contacto';
                            
                            // Verificar si el nombre ya termina con el tipo correcto para evitar bucles infinitos
                            $expectedEnding = ' - ' . $tipoContacto;
                            $alreadyHasCorrectType = str_ends_with($nameToUpdate, $expectedEnding);
                            
                            Log::channel('chatwoot_api')->info('Verificando si necesita actualización', [
                                'contact_id' => $id,
                                'original_name' => $contactName,
                                'name_to_update' => $nameToUpdate,
                                'expected_ending' => $expectedEnding,
                                'already_has_correct_type' => $alreadyHasCorrectType
                            ]);
                            
                            if ($alreadyHasCorrectType) {
                                Log::channel('chatwoot_api')->info('El nombre ya tiene el tipo correcto, omitiendo actualización', [
                                    'contact_id' => $id,
                                    'current_name' => $nameToUpdate,
                                    'tipo_contacto' => $tipoContacto
                                ]);
                            } else {
                                Log::channel('chatwoot_api')->info('Ejecutando comando Artisan para actualizar nombre', [
                                    'account_id' => $accountId,
                                    'contact_id' => $id,
                                    'name' => $nameToUpdate,
                                    'type' => $tipoContacto
                                ]);
                                
                                // Actualizar el nombre usando el comando existente con el nuevo tipo
                                $exitCode = Artisan::call('chatwoot:update-contact-name', [
                                    'account_id' => $accountId,
                                    'contact_id' => $id,
                                    'name' => $nameToUpdate,
                                    '--type' => $tipoContacto
                                ]);
                                
                                Log::channel('chatwoot_api')->info('Comando Artisan ejecutado', [
                                    'exit_code' => $exitCode
                                ]);

                                if ($exitCode === 0) {
                                    Log::channel('chatwoot_api')->info('Nombre del contacto actualizado exitosamente con nuevo tipo', [
                                        'contact_id' => $id,
                                        'account_id' => $accountId,
                                        'tipo_contacto' => $tipoContacto,
                                        'nombre_usado' => $nameToUpdate
                                    ]);
                                } else {
                                    Log::channel('chatwoot_api')->error('Error al actualizar el nombre del contacto', [
                                        'contact_id' => $id,
                                        'account_id' => $accountId,
                                        'tipo_contacto' => $tipoContacto,
                                        'exit_code' => $exitCode
                                    ]);
                                }
                            }
                        } catch (\Exception $e) {
                            Log::channel('chatwoot_api')->error('Excepción al actualizar nombre del contacto: ' . $e->getMessage(), [
                                'contact_id' => $id,
                                'account_id' => $accountId,
                                'tipo_contacto' => $tipoContacto
                            ]);
                        }
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

            // Ejecutar el comando para actualizar el nombre con tipo por defecto
            $exitCode = Artisan::call('chatwoot:update-contact-name', [
                'account_id' => $accountId,
                'contact_id' => $contactId,
                'name' => $name,
                '--type' => 'Prospecto'
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