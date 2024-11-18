<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\PeopleService;
use Illuminate\Http\Request;
use App\Models\GoogleToken;

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
    

    public function storeContact(Request $request)
    {
        $id_cliente = $request->get('id_cliente');
    
        // Recuperar el token de la base de datos
        $googleToken = GoogleToken::where('id_cliente', $id_cliente)->first();
    
        if (!$googleToken) {
            return response()->json(['message' => 'No token found for this client'], 404);
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
            // Refrescar el token si es necesario
            $refreshToken = $googleToken->refresh_token;
            if ($refreshToken) {
                $client->refreshToken($refreshToken);
                // Actualizar el token en la base de datos
                $newToken = $client->getAccessToken();
                $googleToken->access_token = $newToken['access_token'];
                $googleToken->expires_at = now()->addSeconds($newToken['expires_in']);
                $googleToken->save();
            } else {
                return response()->json(['message' => 'Refresh token not available'], 401);
            }
        }
    
        $peopleService = new PeopleService($client);
    
        $newContact = new PeopleService\Person([
            'names' => [
                ['givenName' => $request->first_name, 'familyName' => $request->last_name]
            ],
            'emailAddresses' => [
                ['value' => $request->email]
            ],
            'phoneNumbers' => [
                ['value' => $request->phone]
            ]
        ]);
    
        $result = $peopleService->people->createContact($newContact);
    
        return response()->json($result);
    }

    public function saveContact(Request $request)
{
    // Validar los datos entrantes
    $validatedData = $request->validate([
        'id_cliente' => 'required',
        'first_name' => 'required|string',
        'last_name' => 'nullable|string',
        'email' => 'required|email',
        'phone' => 'nullable|string',
    ]);

    $id_cliente = $validatedData['id_cliente'];

    // Recuperar el token de la base de datos
    $googleToken = GoogleToken::where('id_cliente', $id_cliente)->first();

    if (!$googleToken) {
        return response()->json(['message' => 'No token found for this client'], 404);
    }

    // Now, $googleToken->expires_at is a Carbon instance
    $expiresIn = $googleToken->expires_at->timestamp - now()->timestamp;

        // Verificar si el token ha expirado o está a punto de expirar
        if ($expiresIn <= 0) {
            // El token ha expirado, intentar refrescarlo
            $client = new Client();
            $client->setAuthConfig(storage_path('app/google/credentials.json'));
            $client->setAccessToken([
                'access_token' => $googleToken->access_token,
                'refresh_token' => $googleToken->refresh_token,
                'expires_in' => $expiresIn,
                'created' => now()->timestamp,
            ]);
    
            if ($client->getRefreshToken()) {
                // Refrescar el token de acceso
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
            return response()->json(['message' => 'Refresh token not available or expired. Please re-authenticate.'], 401);
        }
    }

    // Configurar el servicio de People API
    $peopleService = new PeopleService($client);

    // Crear el nuevo contacto
    $newContact = new PeopleService\Person([
        'names' => [
            [
                'givenName' => $validatedData['first_name'],
                'familyName' => $validatedData['last_name'] ?? '',
            ],
        ],
        'emailAddresses' => [
            ['value' => $validatedData['email']],
        ],
        'phoneNumbers' => [
            ['value' => $validatedData['phone'] ?? ''],
        ],
    ]);

    try {
        // Guardar el contacto en Google Contacts
        $result = $peopleService->people->createContact($newContact);

        return response()->json(['message' => 'Contact saved successfully', 'contact' => $result], 201);
    } catch (\Exception $e) {
        // Manejar errores de la API de Google
        return response()->json(['message' => 'Failed to save contact', 'error' => $e->getMessage()], 500);
    }
}

    
}
