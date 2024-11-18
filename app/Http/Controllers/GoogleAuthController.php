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
    
        // Validar que el id_cliente fue enviado
        $validated = $request->validate([
            'id_cliente' => 'required|exists:clientes,id_cliente', // Validar el cliente
        ]);
    
        // Generar la URL de autenticación de Google
        $authUrl = $client->createAuthUrl();
    
        // Agregar el id_cliente como parámetro adicional
        $authUrl .= '&state=' . $validated['id_cliente']; // Usamos 'state' que es recomendado por Google
    
        // Debug: Verifica la URL generada antes de redirigir
        \Log::info("Redirigiendo a la URL de Google: " . $authUrl);
    
        // Redirigir al usuario
        return redirect()->away($authUrl); // `away` asegura que sea una URL externa
    }
    

    public function callback(Request $request)
    {
        $client = new \Google\Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
    
        $tokenData = $client->fetchAccessTokenWithAuthCode($request->code);
    
        if (isset($tokenData['error'])) {
            return response()->json(['error' => $tokenData['error']], 400);
        }
    
        // Guarda los tokens en la base de datos
        GoogleToken::updateOrCreate(
            ['cliente_id' => auth()->id()], // Cambiar según cómo identifiques al cliente
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
