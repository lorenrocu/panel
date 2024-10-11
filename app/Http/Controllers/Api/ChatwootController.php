<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; // Importar Http para solicitudes externas
use App\Models\Cliente; // Modelo Cliente
use App\Models\AtributoPersonalizado; // Modelo AtributoPersonalizado

class ChatwootController extends Controller
{
    public function actualizarContactoAtributos(Request $request)
    {
            // Depurar para asegurarte de que estás recibiendo la solicitud
    \Log::info('Llamada API recibida', ['data' => $request->all()]);
        // Almacenar el JSON recibido en los logs (contenido completo)
        Log::info('Webhook de Chatwoot recibido', ['data' => $request->all()]);

        // Extraer el id del contacto y el id de la cuenta directamente del nivel superior
        $contactoId = $request->input('id');
        $accountId = $request->input('account.id');

        // Registrar el id del contacto y la cuenta
        Log::info('ID de contacto y ID de cuenta capturados', [
            'contactoId' => $contactoId,
            'accountId' => $accountId,
        ]);

        // Inicializar el array para custom_attributes
        $customAttributes = [];

        // Verificar que el accountId no sea null antes de proceder
        if ($accountId !== null) {
            // Buscar en la tabla clientes el id_account
            $cliente = Cliente::where('id_account', $accountId)->first();

            if ($cliente) {
                // Capturar el token
                $token = $cliente->token;

                // Mostrar el token en los logs
                Log::info('Token capturado', ['token' => $token]);

                // Buscar todos los atributos personalizados relacionados con el id_account que tengan valor en 'valor_por_defecto'
                $atributosPersonalizados = AtributoPersonalizado::where('id_account', $accountId)
                    ->whereNotNull('valor_por_defecto') // Filtrar solo los que tienen valor en valor_por_defecto
                    ->get();

                // Verificar si se encontraron atributos personalizados con valor en 'valor_por_defecto'
                if ($atributosPersonalizados->isNotEmpty()) {
                    foreach ($atributosPersonalizados as $atributo) {
                        $valorReal = null;

                        // Caso 1: valor_atributo es un JSON (array)
                        if (!empty($atributo->valor_atributo)) {
                            // Intentar decodificar 'valor_atributo' como JSON
                            $arrayValores = json_decode($atributo->valor_atributo, true);

                            // Verificar si el valor se convirtió correctamente en un array y si el índice existe
                            if (is_array($arrayValores) && isset($arrayValores[$atributo->valor_por_defecto])) {
                                // Obtener el valor basado en el índice 'valor_por_defecto'
                                $valorReal = $arrayValores[$atributo->valor_por_defecto];
                            }
                        }

                        // Caso 2: valor_atributo está vacío, tomar 'valor_por_defecto' directamente
                        if ($valorReal === null) {
                            $valorReal = $atributo->valor_por_defecto;
                        }

                        // Añadir el atributo al array de custom_attributes
                        $customAttributes[$atributo->attribute_key] = $valorReal;
                    }
                } else {
                    Log::info('No se encontraron atributos personalizados con valor por defecto para el id_account: ' . $accountId);
                }

                // Generar el JSON de salida
                $output = [
                    'custom_attributes' => $customAttributes
                ];

                // Registrar el JSON en los logs para depuración
                Log::info('JSON de custom_attributes generado', ['json' => $output]);

                // Construir la URL
                $url = "https://app.fasiacrm.com/api/v1/accounts/{$accountId}/contacts/{$contactoId}";

                // Registrar la URL generada en los logs para validación
                Log::info('URL generada para la solicitud PATCH', ['url' => $url]);

                try {
                    // Enviar la solicitud PATCH al endpoint especificado
                    $response = Http::withHeaders([
                        'api_access_token' => $token
                    ])->patch($url, $output);

                    // Registrar la respuesta del servidor
                    if ($response->successful()) {
                        Log::info('Datos enviados exitosamente a FasciaCRM', ['response' => $response->json()]);
                    } else {
                        Log::error('Error al enviar datos a FasciaCRM', [
                            'status' => $response->status(),
                            'response' => $response->body()
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Excepción al enviar datos a FasciaCRM', [
                        'message' => $e->getMessage()
                    ]);
                }

                // Respuesta de éxito local
                return response()->json(['status' => 'success', 'message' => 'Datos recibidos, registrados y enviados.'], 200);
            } else {
                Log::warning('No se encontró el cliente con id_account: ' . $accountId);
            }
        } else {
            Log::warning('id_account es null después de intentar capturarlo del request.');
        }

        // Respuesta de error si algo falla
        return response()->json(['status' => 'error', 'message' => 'No se pudo procesar la solicitud.'], 400);
    }
}
