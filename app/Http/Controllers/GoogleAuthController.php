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

        $id_cliente = $request->query('id_cliente');
        $client->setState($id_cliente);

        $authUrl = $client->createAuthUrl();
        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));

        $tokenData = $client->fetchAccessTokenWithAuthCode($request->code);

        if (isset($tokenData['error'])) {
            return response()->json(['error' => $tokenData['error']], 400);
        }

        $id_cliente = $request->input('state');

        if (!$id_cliente) {
            return response()->json(['error' => 'Client ID not found in state parameter'], 400);
        }

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

    private function refreshGoogleToken(GoogleToken $googleToken, $id_cliente)
    {
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
                $googleToken->access_token = $newToken['access_token'];
                $googleToken->expires_at = now()->addSeconds($newToken['expires_in']);
                $googleToken->save();
            } else {
                throw new \Exception('Refresh token not available or expired. Please re-authenticate.');
            }
        }

        return $client;
    }

    public function saveContact(Request $request)
    {
        Log::info('Paso 2: Datos recibidos en saveContact:', $request->all());

        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['message' => 'JSON inválido recibido', 'error' => json_last_error_msg()], 400);
        }

        $accountId = $data['account']['id'] ?? null;
        $firstName = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $phoneNumber = $data['phone_number'] ?? null;

        if (is_null($accountId) || is_null($firstName) || is_null($phoneNumber)) {
            return response()->json(['message' => 'Datos insuficientes para procesar la solicitud.'], 400);
        }

        $cliente = Cliente::where('id_account', $accountId)->first();

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado para el account_id proporcionado.'], 404);
        }

        $id_cliente = $cliente->id_cliente;

        $googleToken = GoogleToken::where('id_cliente', $id_cliente)->first();

        if (!$googleToken) {
            return response()->json(['message' => 'No se encontró un token para este cliente.'], 404);
        }

        try {
            $client = $this->refreshGoogleToken($googleToken, $id_cliente);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }

        $peopleService = new PeopleService($client);

        $givenName = $firstName . ' - Prospecto';

        $newContact = new PeopleService\Person([
            'names' => [
                [
                    'givenName' => $givenName,
                    'familyName' => '',
                ],
            ],
            'emailAddresses' => $email ? [['value' => $email]] : [],
            'phoneNumbers' => [
                ['value' => $phoneNumber],
            ],
        ]);

        try {
            $result = $peopleService->people->createContact($newContact);

            return response()->json(['message' => 'Contacto guardado exitosamente', 'contact' => $result], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear el contacto en Google:', [
                'error' => $e->getMessage(),
                'contact_data' => $newContact,
            ]);
            return response()->json(['message' => 'No se pudo guardar el contacto', 'error' => $e->getMessage()], 500);
        }
    }

    private function buscarUsuarioEnChatwoot($account_id, $telefono)
    {
        try {
            Artisan::call('chatwoot:buscar-usuario', [
                'account_id' => $account_id,
                'telefono' => $telefono
            ]);

            $output = Artisan::output();
            $empresa = null;

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
