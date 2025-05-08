<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class FasiaController extends Controller
{
    public function validarUtmFasia(Request $request)
    {
        $content = $request->input('content');
        $account = $request->input('account');
        $userId = $request->input('sender.id');

        \Log::info('--- Nueva solicitud POST validar-utm-fasia ---');
        \Log::info('Body:', $request->all());

        if (preg_match('/^\[([a-zA-Z0-9]+)\]/', $content, $match)) {
            $capturedValue = $match[1];
            \Log::info('Valor capturado dentro de los corchetes:', ['valor' => $capturedValue]);

            $accountId = $account['id'];

            if (is_numeric($capturedValue)) {
                $currentDate = Carbon::now()->format('Y-m-d');
                \Log::info('Fecha actual:', ['fecha' => $currentDate]);

                $utms = DB::table('registro_ingresos_web')
                    ->where('registro_id', $capturedValue)
                    ->where('id_account', $accountId)
                    ->where('fecha', $currentDate)
                    ->value('utms');

                if ($utms) {
                    \Log::info('UTMs encontradas:', ['utms' => $utms]);

                    $token = DB::table('clientes')
                        ->where('id_account', $accountId)
                        ->value('token');

                    if ($token) {
                        $webhookUrl = "https://app.fasiacrm.com/api/v1/accounts/{$accountId}/contacts/{$userId}";
                        $data = ['custom_attributes' => json_decode($utms, true)];

                        $response = Http::withHeaders(['api_access_token' => $token])
                            ->patch($webhookUrl, $data);

                        if ($response->successful()) {
                            \Log::info('UTMs enviadas correctamente al webhook (numérico).', ['response' => $response->json()]);
                        } else {
                            \Log::error('Error al enviar UTMs al webhook (numérico).', [
                                'status' => $response->status(),
                                'body' => $response->body()
                            ]);
                        }

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
                \Log::info('El valor dentro de los corchetes no es un número.');

                $utmData = DB::table('campanas_facebook')
                    ->where('id_account', $accountId)
                    ->where('id_campana', $capturedValue)
                    ->first();

                if ($utmData) {
                    \Log::info('Datos de la campaña UTM encontrados.', (array) $utmData);

                    $token = DB::table('clientes')
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

                        $response = Http::withHeaders(['api_access_token' => $token])
                            ->patch($webhookUrl, $data);

                        if ($response->successful()) {
                            \Log::info('UTMs enviadas correctamente al webhook (Facebook).', ['response' => $response->json()]);
                        } else {
                            \Log::error('Error al enviar UTMs al webhook (Facebook).', [
                                'status' => $response->status(),
                                'body' => $response->body()
                            ]);
                        }

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
