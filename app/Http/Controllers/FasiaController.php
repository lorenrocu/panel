<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Para consultas a la base de datos
use Illuminate\Support\Facades\Http; // Para hacer solicitudes HTTP externas (equivalente a Axios)
use Carbon\Carbon; // Para trabajar con fechas

class FasiaController extends Controller
{
    public function validarUtmFasia(Request $request)
    {
        $content = $request->input('content');
        $account = $request->input('account');
        $userId = $request->input('sender.id');

        // Log para seguimiento
        \Log::info('--- Nueva solicitud POST validar-utm-fasia ---');
        \Log::info('Body:', $request->all());

        // Validar si existe un valor en corchetes
        if (preg_match('/^\[([a-zA-Z0-9]+)\]/', $content, $match)) {
            $capturedValue = $match[1];
            \Log::info('Valor capturado dentro de los corchetes:', ['valor' => $capturedValue]);

            // Capturar el id de la cuenta
            $accountId = $account['id'];

            // Verificar si el valor capturado es un número
            if (is_numeric($capturedValue)) {
                // Obtener la fecha actual en formato yyyy-MM-dd
                $currentDate = Carbon::now()->format('Y-m-d');
                \Log::info('Fecha actual:', ['fecha' => $currentDate]);

                // Consultar la base de datos para las UTMs
                $utms = DB::table('registro_ingresos_web')
                          ->where('id', $capturedValue)
                          ->where('id_account', $accountId)
                          ->where('fecha', $currentDate)
                          ->value('utms');

                if ($utms) {
                    \Log::info('UTMs encontradas:', ['utms' => $utms]);

                    // Consultar el token para el id_account
                    $token = DB::table('clientes')
                               ->where('id_account', $accountId)
                               ->value('token');

                    if ($token) {
                        $webhookUrl = "https://app.fasiacrm.com/api/v1/accounts/{$accountId}/contacts/{$userId}";
                        $response = Http::withHeaders(['api_access_token' => $token])
                            ->patch($webhookUrl, ['custom_attributes' => json_decode($utms, true)]);

                        return response()->json([
                            'success' => true,
                            'message' => 'UTMs y token encontrados correctamente',
                            'utms' => $utms,
                            'token' => $token
                        ]);
                    } else {
                        \Log::error('No se encontró un token para el id_account especificado.');
                        return response()->json(['error' => 'No se encontró un token para el id_account especificado.'], 500);
                    }
                } else {
                    \Log::error('No se encontraron UTMs para los parámetros especificados.');
                    return response()->json(['error' => 'No se encontraron UTMs para los parámetros especificados.'], 500);
                }
            } else {
                // Si no es un número, manejar la lógica para Facebook UTMs
                \Log::info('El valor dentro de los corchetes no es un número.');

                // Consulta a la base de datos campanas_utm_facebook
                $utmData = DB::table('campanas_utm_facebook')
                            ->where('id_account', $accountId)
                            ->where('id', $capturedValue)
                            ->first();

                if ($utmData) {
                    \Log::info('Datos de la campaña UTM encontrados.', (array) $utmData);

                    // Obtener el token para id_account
                    $token = DB::table('cliente')
                               ->where('id_account', $accountId)
                               ->value('token');

                    if ($token) {
                        $webhookUrl = "https://app.fasiacrm.com/api/v1/accounts/{$accountId}/contacts/{$userId}";
                        $data = [
                            'custom_attributes' => [
                                'utm_source' => $utmData->utm_source,
                                'utm_medium' => $utmData->utm_medium,
                                'utm_term' => $utmData->utm_term,
                                'utm_content' => $utmData->utm_content,
                                'utm_campaign' => $utmData->utm_campaign
                            ]
                        ];

                        Http::withHeaders(['api_access_token' => $token])
                            ->patch($webhookUrl, $data);

                        return response()->json([
                            'success' => true,
                            'message' => 'Datos UTM enviados al webhook correctamente'
                        ]);
                    } else {
                        \Log::error('No se encontró un token para el id_account especificado.');
                        return response()->json(['error' => 'No se encontró un token para el id_account especificado.'], 500);
                    }
                } else {
                    \Log::error('No se encontraron datos de la campaña UTM para los parámetros especificados.');
                    return response()->json(['error' => 'No se encontraron datos de la campaña UTM para los parámetros especificados.'], 500);
                }
            }
        } else {
            \Log::error('No se encontraron valores dentro de los corchetes en el mensaje.');
            return response()->json(['error' => 'No se encontraron valores dentro de los corchetes en el mensaje.'], 500);
        }
    }
}
