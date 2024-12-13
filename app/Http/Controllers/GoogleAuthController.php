<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\PeopleService;
use Illuminate\Http\Request;
use App\Models\GoogleToken;
use Illuminate\Support\Facades\Log;
use App\Models\Cliente;
use Illuminate\Support\Facades\Artisan;

class GoogleAuthController extends Controller
{
    public function authenticate(Request $request)
    {
        Log::info('Iniciando autenticación con Google', [
            'query_params' => $request->query(),
        ]);

        $client = new Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
        $client->addScope(PeopleService::CONTACTS);
        $client->setRedirectUri(route('google.callback'));
        $client->setAccessType('offline');
    
        $id_cliente = $request->query('id_cliente');
        Log::info('id_cliente obtenido del request', ['id_cliente' => $id_cliente]);

        $client->setState($id_cliente);
    
        $authUrl = $client->createAuthUrl();
        Log::info('URL de autenticación generada', ['auth_url' => $authUrl]);

        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        Log::info('Callback recibido de Google', [
            'query_params' => $request->query(),
        ]);

        $client = new Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
    
        $code = $request->input('code');
        Log::info('Intercambiando code por token', ['code' => $code]);

        $tokenData = $client->fetchAccessTokenWithAuthCode($code);
        Log::info('Datos del token retornados por Google:', $tokenData);
    
        if (isset($tokenData['error'])) {
            Log::error('Error al obtener token de Google', ['error' => $tokenData['error']]);
            return response()->json(['error' => $tokenData['error']], 400);
        }
    
        $id_cliente = $request->input('state');
        Log::info('id_cliente obtenido del estado', ['id_cliente' => $id_cliente]);

        if (!$id_cliente) {
            Log::error('No se recibió id_cliente en el state');
            return response()->json(['error' => 'Client ID not found in state parameter'], 400);
        }
    
        Log::info('Guardando tokens en la base de datos', [
            'id_cliente' => $id_cliente,
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'expires_in' => $tokenData['expires_in']
        ]);

        GoogleToken::updateOrCreate(
            ['id_cliente' => $id_cliente],
            [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]
        );
    
        Log::info('Token guardado exitosamente', ['id_cliente' => $id_cliente]);

        return response()->json(['message' => 'Authenticated successfully']);
    }

    private function getClientForCustomer($id_cliente)
    {
        Log::info('Obteniendo cliente de Google para el id_cliente', ['id_cliente' => $id_cliente]);

        $googleToken = GoogleToken::where('id_cliente', $id_cliente)->first();

        if (!$googleToken) {
            Log::error('No se encontró token en la base de datos para este id_cliente', ['id_cliente' => $id_cliente]);
            throw new \Exception('No token found for this client');
        }

        Log::info('Token encontrado', [
            'access_token' => $googleToken->access_token,
            'refresh_token' => $googleToken->refresh_token,
            'expires_at' => $googleToken->expires_at
        ]);

        $client = new Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
        $client->setAccessToken([
            'access_token' => $googleToken->access_token,
            'refresh_token' => $googleToken->refresh_token,
            'expires_in' => $googleToken->expires_at->timestamp - now()->timestamp,
            'created' => now()->timestamp,
        ]);

        if ($client->isAccessTokenExpired()) {
            Log::info('El token ha expirado, intentando refrescar', ['id_cliente' => $id_cliente]);
            if ($client->getRefreshToken()) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                Log::info('Respuesta al refrescar token (getClientForCustomer):', $newToken);

                if (isset($newToken['error'])) {
                    Log::error('Error al refrescar el token (getClientForCustomer)', ['error' => $newToken['error']]);
                    throw new \Exception('Error refreshing token: ' . $newToken['error']);
                }

                if (!isset($newToken['access_token'])) {
                    Log::error('No se recibió access_token al refrescar el token (getClientForCustomer)');
                    throw new \Exception('No access_token received during refresh.');
                }

                $googleToken->access_token = $newToken['access_token'];
                $googleToken->expires_at = now()->addSeconds($newToken['expires_in']);
                $googleToken->save();
                Log::info('Token refrescado y guardado exitosamente (getClientForCustomer)', ['id_cliente' => $id_cliente]);
            } else {
                Log::error('No hay refresh token disponible (getClientForCustomer)', ['id_cliente' => $id_cliente]);
                throw new \Exception('Refresh token not available or expired. Please re-authenticate.');
            }
        }

        return $client;
    }

    public function storeContact(Request $request)
    {
        Log::info('storeContact: Datos recibidos', $request->all());

        try {
            $id_cliente = $request->get('id_cliente');
            Log::info('Obteniendo cliente de Google para storeContact', ['id_cliente' => $id_cliente]);
            $client = $this->getClientForCustomer($id_cliente);

            Log::info('Creando servicio PeopleService en storeContact');
            $peopleService = new PeopleService($client);

            $newContact = new PeopleService\Person([
                'names' => [
                    [
                        'givenName' => $request->first_name . ' - Prospecto',
                        'familyName' => $request->last_name
                    ]
                ],
                'emailAddresses' => [
                    [
                        'value' => $request->email
                    ]
                ],
                'phoneNumbers' => [
                    [
                        'value' => $request->phone
                    ]
                ]
            ]);

            Log::info('Intentando crear contacto en Google Contacts', ['contact_data' => $newContact]);
            $result = $peopleService->people->createContact($newContact);

            Log::info('Contacto creado exitosamente en storeContact', ['result' => $result]);

            return response()->json($result, 201);
        } catch (\Exception $e) {
            Log::error('Error en storeContact', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function saveContact(Request $request)
    {
        Log::info('Paso 2: Datos recibidos en saveContact:', $request->all());
    
        $data = json_decode($request->getContent(), true);

        Log::info('JSON decodificado en saveContact:', ['data' => $data]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON inválido recibido en saveContact', ['error_msg' => json_last_error_msg()]);
            return response()->json(['message' => 'JSON inválido recibido', 'error' => json_last_error_msg()], 400);
        }
    
        $accountId = $data['account']['id'] ?? null;
        $firstName = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $phoneNumber = $data['phone_number'] ?? null;

        Log::info('Datos extraídos del JSON en saveContact', [
            'accountId' => $accountId,
            'firstName' => $firstName,
            'email' => $email,
            'phoneNumber' => $phoneNumber
        ]);
    
        if (is_null($accountId) || is_null($firstName) || is_null($phoneNumber)) {
            Log::error('Datos insuficientes en saveContact');
            return response()->json(['message' => 'Datos insuficientes para procesar la solicitud.'], 400);
        }
    
        $cliente = Cliente::where('id_account', $accountId)->first();

        if (!$cliente) {
            Log::error('Cliente no encontrado para account_id proporcionado', ['accountId' => $accountId]);
            return response()->json(['message' => 'Cliente no encontrado para el account_id proporcionado.'], 404);
        }

        $id_cliente = $cliente->id_cliente;
        Log::info('Cliente encontrado para saveContact', ['id_cliente' => $id_cliente]);
    
        $empresa = $this->buscarUsuarioEnChatwoot($accountId, $phoneNumber);
        Log::info('Empresa encontrada en Chatwoot', ['empresa' => $empresa]);

        if (!$empresa) {
            Log::info('No se encontró empresa, se usará cadena vacía');
            $empresa = '';
        }
    
        $validatedData = [
            'id_cliente' => $id_cliente,
            'first_name' => $firstName,
            'last_name' => '',
            'email' => $email,
            'phone' => $phoneNumber,
        ];

        Log::info('Datos validados para guardar contacto en Google', $validatedData);
    
        $googleToken = GoogleToken::where('id_cliente', $id_cliente)->first();
    
        if (!$googleToken) {
            Log::error('No se encontró token para este cliente', ['id_cliente' => $id_cliente]);
            return response()->json(['message' => 'No se encontró un token para este cliente.'], 404);
        }

        $expiresIn = $googleToken->expires_at->timestamp - now()->timestamp;
        Log::info('Tiempo restante antes de expiración del token', ['expires_in' => $expiresIn]);
    
        if ($expiresIn <= 0) {
            Log::info('El token ha expirado. Intentando refrescar el token', ['id_cliente' => $id_cliente]);
        
            $client = new Client();
            $client->setAuthConfig(storage_path('app/google/credentials.json'));
            $client->setAccessToken([
                'access_token' => $googleToken->access_token,
                'refresh_token' => $googleToken->refresh_token,
                'expires_in' => $expiresIn,
                'created' => now()->timestamp,
            ]);
        
            if ($client->getRefreshToken()) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

                Log::info('Respuesta al refrescar token (primer intento en saveContact):', $newToken);
        
                if (isset($newToken['error'])) {
                    Log::error('Error al refrescar el token (primer intento saveContact)', ['error' => $newToken['error']]);
                    return response()->json(['message' => 'No se pudo refrescar el token. Por favor, reautentica.'], 401);
                }
        
                if (!isset($newToken['access_token'])) {
                    Log::error('No se recibió un access_token al refrescar el token (primer intento saveContact)');
                    return response()->json(['message' => 'No se recibió un nuevo access_token. Por favor, reautentica.'], 401);
                }

                Log::info('Token refrescado exitosamente (primer intento saveContact)', [
                    'new_access_token' => $newToken['access_token'],
                    'expires_in' => $newToken['expires_in'],
                ]);
        
                $googleToken->access_token = $newToken['access_token'];
                $googleToken->expires_at = now()->addSeconds($newToken['expires_in']);
                $googleToken->save();
            } else {
                Log::error('No se pudo refrescar el token. El refresh token no está disponible (primer intento saveContact)', ['id_cliente' => $id_cliente]);
                return response()->json(['message' => 'El token de refresco no está disponible o ha expirado. Por favor, reautentique.'], 401);
            }
        }        
    
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
        $client->setAccessToken([
            'access_token' => $googleToken->access_token,
            'refresh_token' => $googleToken->refresh_token,
            'expires_in' => $expiresIn,
            'created' => now()->timestamp,
        ]);

        Log::info('Cliente Google configurado antes de isAccessTokenExpired en saveContact');

        if ($client->isAccessTokenExpired()) {
            Log::info('El token parece expirado nuevamente. Intentando segundo refresco', ['id_cliente' => $id_cliente]);

            if ($client->getRefreshToken()) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

                Log::info('Respuesta al refrescar token (segundo intento en saveContact):', $newToken);
                
                if (isset($newToken['error'])) {
                    Log::error('Error al refrescar el token (segundo intento saveContact)', ['error' => $newToken['error']]);
                    return response()->json(['message' => 'No se pudo refrescar el token. Por favor, reautentica.'], 401);
                }
                
                if (!isset($newToken['access_token'])) {
                    Log::error('No se recibió un access_token al refrescar el token (segundo intento saveContact)');
                    return response()->json(['message' => 'No se recibió un nuevo access_token. Por favor, reautentica.'], 401);
                }

                Log::info('Token refrescado exitosamente (segundo intento saveContact)', [
                    'new_access_token' => $newToken['access_token'],
                    'expires_in' => $newToken['expires_in'],
                ]);

                $googleToken->access_token = $newToken['access_token'];
                $googleToken->expires_at = now()->addSeconds($newToken['expires_in']);
                $googleToken->save();
            } else {
                Log::warning('El refresh token no está disponible o ha expirado (segundo intento saveContact)', ['id_cliente' => $id_cliente]);
                throw new \Exception('Refresh token not available or expired. Please re-authenticate.');
            }
        }        
    
        Log::info('Creando servicio PeopleService en saveContact');
        $peopleService = new PeopleService($client);
    
        $givenName = $validatedData['first_name'];
        if ($email) {
            $givenName .= ' - ' . $empresa . ' - Prospecto';
        } else {
            $givenName .= ' - Prospecto';
        }

        $newContactData = [
            'names' => [
                [
                    'givenName' => $givenName,
                    'familyName' => $validatedData['last_name'],
                ],
            ],
            'emailAddresses' => $email ? [['value' => $validatedData['email']]] : [],
            'phoneNumbers' => [
                ['value' => $validatedData['phone']],
            ],
        ];

        Log::info('Datos del nuevo contacto a crear en Google Contacts', $newContactData);
    
        try {
            $newContact = new PeopleService\Person($newContactData);
            $result = $peopleService->people->createContact($newContact);

            Log::info('Contacto guardado exitosamente en Google Contacts', ['result' => $result]);
    
            return response()->json(['message' => 'Contacto guardado exitosamente', 'contact' => $result], 201);
        } catch (\Exception $e) {
            Log::error('Error al guardar el contacto en Google Contacts', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'No se pudo guardar el contacto', 'error' => $e->getMessage()], 500);
        }
    }
    
    private function buscarUsuarioEnChatwoot($account_id, $telefono)
    {
        Log::info('Buscando usuario en Chatwoot', ['account_id' => $account_id, 'telefono' => $telefono]);
        try {
            $empresa = Artisan::call('chatwoot:buscar-usuario', [
                'account_id' => $account_id,
                'telefono' => $telefono
            ]);
    
            $output = Artisan::output();
            Log::info('Salida del comando chatwoot:buscar-usuario', ['output' => $output]);

            $empresa = null;
    
            if (strpos($output, 'Empresa encontrada:') !== false) {
                preg_match('/Empresa encontrada: (.*)/', $output, $matches);
                $empresa = $matches[1] ?? null;
            }
    
            Log::info('Empresa obtenida de Chatwoot', ['empresa' => $empresa]);

            return $empresa;
    
        } catch (\Exception $e) {
            Log::error('Error al ejecutar el comando para buscar usuario en Chatwoot', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
