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
    
        $tokenData = $client->fetchAccessTokenWithAuthCode($request->code);
    
        if (isset($tokenData['error'])) {
            return response()->json(['error' => $tokenData['error']], 400);
        }
        $clienteId = $request->get('cliente_id');
        // Guarda los tokens en la base de datos
        GoogleToken::updateOrCreate(
            ['id_cliente' => auth()->id()], // Cambiar según cómo identifiques al cliente
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
