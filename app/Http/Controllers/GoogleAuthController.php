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

        $authUrl = $client->createAuthUrl();
        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        $client = new \Google\Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
    
        // Obtén el token de Google
        $tokenData = $client->fetchAccessTokenWithAuthCode($request->code);
    
        if (isset($tokenData['error'])) {
            return response()->json(['error' => $tokenData['error']], 400);
        }
    
        // Validar que el id_cliente existe
        $validated = $request->validate([
            'id_cliente' => 'required|exists:clientes,id_cliente', // Verifica que el cliente existe
        ]);
    
        // Guarda el token en la base de datos
        GoogleToken::updateOrCreate(
            ['id_cliente' => $validated['id_cliente']], // Usar el id_cliente validado
            [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]
        );
    
        return response()->json(['message' => 'Token guardado exitosamente']);
    }    

    public function storeContact(Request $request)
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
        $client->setAccessToken(session('google_access_token'));

        if ($client->isAccessTokenExpired()) {
            return response()->json(['message' => 'Access token expired'], 401);
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
