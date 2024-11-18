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
    
        $id_cliente = $request->query('id_cliente');
    
        // Generar un token de estado único
        $csrfToken = Str::random(40);
    
        // Almacenar el token y el id_cliente en la sesión
        session(['google_oauth_state' => $csrfToken, 'google_oauth_id_cliente' => $id_cliente]);
    
        // Incluir ambos en el parámetro state
        $state = json_encode(['csrf_token' => $csrfToken, 'id_cliente' => $id_cliente]);
        $state = base64_encode($state);
        $client->setState($state);
    
        $authUrl = $client->createAuthUrl();
        return redirect($authUrl);
    }
    

    public function callback(Request $request)
    {
        $client = new \Google\Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
    
        // Decodificar y verificar el parámetro state
        $stateData = json_decode(base64_decode($request->input('state')), true);
    
        if (!$stateData || !isset($stateData['csrf_token']) || !isset($stateData['id_cliente'])) {
            return response()->json(['error' => 'Invalid state parameter'], 400);
        }
    
        // Validar el token CSRF
        if ($stateData['csrf_token'] !== session('google_oauth_state')) {
            return response()->json(['error' => 'Invalid CSRF token'], 400);
        }
    
        // Obtener el id_cliente
        $id_cliente = $stateData['id_cliente'];
    
        $tokenData = $client->fetchAccessTokenWithAuthCode($request->code);
    
        if (isset($tokenData['error'])) {
            return response()->json(['error' => $tokenData['error']], 400);
        }
    
        // Guarda los tokens en la base de datos
        GoogleToken::updateOrCreate(
            ['id_cliente' => $id_cliente],
            [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]
        );
    
        // Limpiar los datos de la sesión
        session()->forget(['google_oauth_state', 'google_oauth_id_cliente']);
    
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
    
}
