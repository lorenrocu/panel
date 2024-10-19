<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Cliente;
use App\Models\LabelPersonalizado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http; 

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

        // Definir los valores estáticos para tipo_contacto
        $valoresEstaticosTipo = [
            'Prospecto' => 't1_prospecto',
            'Cliente' => 't2_cliente',
            'Proveedor' => 't3_proveedor',
            'Colaborador' => 't4_colaborador',
            'Spam' => 't5_spam',
            'Prueba' => 't6_prueba',
        ];

        // Definir los valores estáticos para estado_contacto
        $valoresEstaticosEstado = [
            'No contactado' => 'e1_no-contactado',
            'Contactado' => 'e2_contactado',
            'Agendado' => 'e3_agendado',
            'Agendado fin' => 'e4_agendado-fin',
            'Cotizado' => 'e5_cotizado',
            'Ganado sin pago' => 'e6_ganado-sin-pago',
            'Ganado pagado' => 'e7_ganado-pagado',
            'Perdido' => 'e8_perdido',
        ];

        // Crear valor para "valor_label" basado en tipo_contacto y estado_contacto
        if (array_key_exists($tipoContacto, $valoresEstaticosTipo)) {
            $valorLabelTipo = $valoresEstaticosTipo[$tipoContacto];
        } else {
            $ultimoRegistroTipo = LabelPersonalizado::where('id_account', $accountId)->orderBy('id', 'desc')->first();
            $correlativoTipo = $ultimoRegistroTipo ? intval(substr($ultimoRegistroTipo->valor_label, 1)) + 1 : 6;
            $valorLabelTipo = 't' . $correlativoTipo . '_' . strtolower($tipoContacto);
        }

        if (array_key_exists($estadoContacto, $valoresEstaticosEstado)) {
            $valorLabelEstado = $valoresEstaticosEstado[$estadoContacto];
        } else {
            $ultimoRegistroEstado = LabelPersonalizado::where('id_account', $accountId)->orderBy('id', 'desc')->first();
            $correlativoEstado = $ultimoRegistroEstado ? intval(substr($ultimoRegistroEstado->valor_label, 1)) + 1 : 9;
            $valorLabelEstado = 'e' . $correlativoEstado . '_' . strtolower($estadoContacto);
        }

        // Verificar si ya existe un registro con este valor_label en la base de datos local
        // Solo crear etiquetas si los valores no son 'N/A'
        if ($tipoContacto !== 'N/A') {
            $registroExistenteTipo = LabelPersonalizado::where('id_account', $accountId)
                                    ->where('valor_label', $valorLabelTipo)
                                    ->first();

            if (!$registroExistenteTipo) {
                LabelPersonalizado::create([
                    'id_account' => $accountId,
                    'valor_label' => $valorLabelTipo,
                    'id_cliente' => $cliente ? $cliente->id_cliente : null,
                ]);
            }
        }

        if ($estadoContacto !== 'N/A') {
            $registroExistenteEstado = LabelPersonalizado::where('id_account', $accountId)
                                    ->where('valor_label', $valorLabelEstado)
                                    ->first();

            if (!$registroExistenteEstado) {
                LabelPersonalizado::create([
                    'id_account' => $accountId,
                    'valor_label' => $valorLabelEstado,
                    'id_cliente' => $cliente ? $cliente->id_cliente : null,
                ]);
            }
        }

        // Generar un color aleatorio para el campo "color"
        $color = '#' . dechex(rand(0x000000, 0xFFFFFF));

        // Inicializar variable para rastrear si se insertó algún label en PostgreSQL
        $labelInserted = false;

        // Verificar si el label ya existe en la base de datos PostgreSQL antes de insertar
        try {
            // Verificar si el label de tipo_contacto ya existe en la base de datos PostgreSQL
            if ($tipoContacto !== 'N/A') {
                $labelTipoExistente = DB::connection('pgsql_chatwoot')
                    ->table('labels')
                    ->where('title', $valorLabelTipo)
                    ->where('account_id', $accountId)
                    ->first();

                if (!$labelTipoExistente) {
                    DB::connection('pgsql_chatwoot')->table('labels')->insert([
                        'title' => $valorLabelTipo,
                        'description' => $valorLabelTipo,
                        'color' => $color,
                        'show_on_sidebar' => 't',
                        'account_id' => $accountId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Log::channel('chatwoot_api')->info('Label de tipo_contacto insertado correctamente en PostgreSQL.');
                    $labelInserted = true;
                } else {
                    Log::channel('chatwoot_api')->info('Label de tipo_contacto ya existe en PostgreSQL.');
                }
            }

            // Verificar si el label de estado_contacto ya existe en la base de datos PostgreSQL
            if ($estadoContacto !== 'N/A') {
                $labelEstadoExistente = DB::connection('pgsql_chatwoot')
                    ->table('labels')
                    ->where('title', $valorLabelEstado)
                    ->where('account_id', $accountId)
                    ->first();

                if (!$labelEstadoExistente) {
                    DB::connection('pgsql_chatwoot')->table('labels')->insert([
                        'title' => $valorLabelEstado,
                        'description' => $valorLabelEstado,
                        'color' => $color,
                        'show_on_sidebar' => 't',
                        'account_id' => $accountId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Log::channel('chatwoot_api')->info('Label de estado_contacto insertado correctamente en PostgreSQL.');
                    $labelInserted = true;
                } else {
                    Log::channel('chatwoot_api')->info('Label de estado_contacto ya existe en PostgreSQL.');
                }
            }

            // Después de insertar correctamente en PostgreSQL, realizar la llamada a la API
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

        } catch (\Exception $e) {
            Log::channel('chatwoot_api')->error('Error al insertar o verificar los labels en la base de datos PostgreSQL: ' . $e->getMessage());
        }

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

                    // Ahora enviar los labels a la API de etiquetas usando el id_conversation
                    $labelsToSend = [];

                    if ($tipoContacto !== 'N/A') {
                        $labelsToSend[] = $valorLabelTipo;
                    }

                    if ($estadoContacto !== 'N/A') {
                        $labelsToSend[] = $valorLabelEstado;
                    }

                    // Solo enviar los labels si hay alguno válido
                    if (!empty($labelsToSend)) {
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
}
