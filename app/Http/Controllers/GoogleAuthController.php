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
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
        $client->addScope(PeopleService::CONTACTS);
        $client->setRedirectUri(route('google.callback'));
        $client->setAccessType('offline');
    
        // Obtener el id_cliente del parámetro de consulta
        $id_cliente = $request->query('id_cliente');
    
        // Establecer el parámetro state con el id_cliente
        $client->setState($id_cliente);
    
        $authUrl = $client->createAuthUrl();
        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        $client = new \Google\Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
    
        $tokenData = $client->fetchAccessTokenWithAuthCode($request->code);
    
        if (isset($tokenData['error'])) {
            return response()->json(['error' => $tokenData['error']], 400);
        }
    
        // Recuperar el id_cliente del parámetro state
        $id_cliente = $request->input('state');
    
        // Verificar que id_cliente no sea nulo
        if (!$id_cliente) {
            return response()->json(['error' => 'Client ID not found in state parameter'], 400);
        }
    
        // Guarda los tokens en la base de datos usando id_cliente
        GoogleToken::updateOrCreate(
            ['id_cliente' => $id_cliente],
            [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]
        );
    
        return response()->json(['message' => 'Authenticated successfully']);
    }

    private function getClientForCustomer($id_cliente)
    {
        // Recuperar el token de la base de datos
        $googleToken = GoogleToken::where('id_cliente', $id_cliente)->first();

        if (!$googleToken) {
            throw new \Exception('No token found for this client');
        }

        $client = new Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
        $client->setAccessToken([
            'access_token' => $googleToken->access_token,
            'refresh_token' => $googleToken->refresh_token,
            'expires_in' => $googleToken->expires_at->timestamp - now()->timestamp,
            'created' => now()->timestamp,
        ]);

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

                // Actualizar el token en la base de datos
                $googleToken->access_token = $newToken['access_token'];
                $googleToken->expires_at = now()->addSeconds($newToken['expires_in']);
                $googleToken->save();
            } else {
                throw new \Exception('Refresh token not available or expired. Please re-authenticate.');
            }
        }

        return $client;
    }    

    public function storeContact(Request $request)
    {
        try {
            // Usar la función getClientForCustomer para obtener el cliente de Google
            $client = $this->getClientForCustomer($request->get('id_cliente'));
    
            // Crear el servicio de People API usando el cliente válido
            $peopleService = new PeopleService($client);
    
            // Crear el contacto utilizando los datos recibidos del Request
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
    
            // Crear el contacto usando el servicio de Google
            $result = $peopleService->people->createContact($newContact);
    
            // Retornar la respuesta en caso de éxito
            return response()->json($result, 201);
        } catch (\Exception $e) {
            // Capturar cualquier error y devolver un mensaje con el código de error 500
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    

    public function saveContact(Request $request)
    {
        // Registrar el JSON entrante en los logs
        Log::info('Paso 2: Datos recibidos en saveContact:', $request->all());
    
        // Decodificar manualmente el contenido del JSON recibido
        $data = json_decode($request->getContent(), true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['message' => 'JSON inválido recibido', 'error' => json_last_error_msg()], 400);
        }
    
        // Extraer los valores necesarios del JSON recibido
        $accountId = $data['account']['id'] ?? null;
        $firstName = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $phoneNumber = $data['phone_number'] ?? null;
    
        // Verificar que los datos necesarios estén presentes
        if (is_null($accountId) || is_null($firstName) || is_null($phoneNumber)) {
            return response()->json(['message' => 'Datos insuficientes para procesar la solicitud.'], 400);
        }
    
        // Buscar el id_cliente en la base de datos usando el account_id proporcionado
        $cliente = Cliente::where('id_account', $accountId)->first();
    
        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado para el account_id proporcionado.'], 404);
        }
    
        $id_cliente = $cliente->id_cliente;
    
        // Buscar la empresa en la API de FasiaCRM antes de proceder
        $empresa = $this->buscarUsuarioEnChatwoot($accountId, $phoneNumber);
    
        // Si no se encuentra la empresa, devolver un error o continuar según sea necesario
        if (!$empresa) {
            return response()->json(['message' => 'No se encontró información de la empresa en la API de FasiaCRM.'], 404);
        }
    
        // Crear un arreglo para validar los datos antes de proceder con la lógica del token y Google Contacts
        $validatedData = [
            'id_cliente' => $id_cliente,
            'first_name' => $firstName,
            'last_name' => '', // Dejar last_name vacío ya que no lo estamos recibiendo
            'email' => $email,
            'phone' => $phoneNumber,
        ];
    
        // Recuperar el token de la base de datos
        $googleToken = GoogleToken::where('id_cliente', $id_cliente)->first();
    
        if (!$googleToken) {
            return response()->json(['message' => 'No se encontró un token para este cliente.'], 404);
        }
    
        // Verificar si el token ha expirado
        $expiresIn = $googleToken->expires_at->timestamp - now()->timestamp;
    
        if ($expiresIn <= 0) {
            // Refrescar el token si ha expirado
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
    
                // Actualizar el token en la base de datos
                $googleToken->access_token = $newToken['access_token'];
                $googleToken->expires_at = now()->addSeconds($newToken['expires_in']);
                $googleToken->save();
    
                // Recalcular expiresIn con el nuevo expires_at
                $expiresIn = $googleToken->expires_at->timestamp - now()->timestamp;
            } else {
                return response()->json(['message' => 'El token de refresco no está disponible o ha expirado. Por favor, reautentique.'], 401);
            }
        }
    
        // Configurar el servicio de Google Client
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
        $client->setAccessToken([
            'access_token' => $googleToken->access_token,
            'refresh_token' => $googleToken->refresh_token,
            'expires_in' => $expiresIn,
            'created' => now()->timestamp,
        ]);
    
        // Verificar si el token ha expirado y refrescarlo si es necesario
        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    
                // Actualizar el token en la base de datos
                $googleToken->access_token = $newToken['access_token'];
                $googleToken->expires_at = now()->addSeconds($newToken['expires_in']);
                $googleToken->save();
            } else {
                return response()->json(['message' => 'El token de refresco no está disponible o ha expirado. Por favor, reautentique.'], 401);
            }
        }
    
        // Configurar el servicio de People API
        $peopleService = new PeopleService($client);
    
        // Verificar si el email está presente para agregar la empresa al nombre
        $givenName = $validatedData['first_name'];  // Solo el nombre base
        if ($email) {
            // Si el email está presente, agregar la empresa antes de Prospecto
            $givenName .= ' - ' . $empresa . ' - Prospecto';
        } else {
            // Si no hay email, solo agregar Prospecto
            $givenName .= ' - Prospecto';
        }
    
        // Crear el nuevo contacto con el nombre modificado (agregar la empresa solo si hay email)
        $newContact = new PeopleService\Person([
            'names' => [
                [
                    'givenName' => $givenName,  // Nombre ajustado según la condición
                    'familyName' => $validatedData['last_name'],
                ],
            ],
            'emailAddresses' => $email ? [['value' => $validatedData['email']]] : [], // Solo agregar el email si está presente
            'phoneNumbers' => [
                ['value' => $validatedData['phone']],
            ],
        ]);
    
        try {
            // Guardar el contacto en Google Contacts
            $result = $peopleService->people->createContact($newContact);
    
            return response()->json(['message' => 'Contacto guardado exitosamente', 'contact' => $result], 201);
        } catch (\Exception $e) {
            // Manejar errores de la API de Google
            return response()->json(['message' => 'No se pudo guardar el contacto', 'error' => $e->getMessage()], 500);
        }
    }
    
    
    private function buscarUsuarioEnChatwoot($account_id, $telefono)
    {
        try {
            // Ejecutar el comando Artisan
            $empresa = Artisan::call('chatwoot:buscar-usuario', [
                'account_id' => $account_id,
                'telefono' => $telefono
            ]);
    
            // Ver si la salida contiene la empresa
            $output = Artisan::output();
            $empresa = null;
    
            // Buscar la empresa en la salida del comando
            if (strpos($output, 'Empresa encontrada:') !== false) {
                preg_match('/Empresa encontrada: (.*)/', $output, $matches);
                $empresa = $matches[1] ?? null;
            }
    
            return $empresa;
    
        } catch (\Exception $e) {
            Log::error('Error al ejecutar el comando para buscar usuario en Chatwoot: ' . $e->getMessage());
            return null;
        }
    }
    
}
