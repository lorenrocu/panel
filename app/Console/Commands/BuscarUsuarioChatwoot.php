<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Models\Cliente; // Asegúrate de importar el modelo Cliente

class BuscarUsuarioChatwoot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chatwoot:buscar-usuario {account_id} {telefono}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Busca un usuario en Chatwoot a través de la API de FasiaCRM y guarda la respuesta en los logs.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $account_id = $this->argument('account_id');
        $telefono = $this->argument('telefono');

        // Mensaje inicial
        $this->info("Buscando usuario con teléfono $telefono en la cuenta $account_id.");

        // Buscar el cliente en la base de datos usando el account_id
        $cliente = Cliente::where('id_account', $account_id)->first();

        if (!$cliente) {
            $this->error('No se encontró un cliente con ese account_id.');
            return;
        }

        // Obtener el token de acceso del cliente
        $apiAccessToken = $cliente->token;

        // URL de la API con el número de teléfono y account_id dinámico
        $url = "https://app.fasiacrm.com/api/v1/accounts/{$account_id}/contacts/search?q=" . urlencode($telefono);

        // Cliente Guzzle
        $client = new Client();

        try {
            // Realizamos la solicitud GET
            $response = $client->get($url, [
                'headers' => [
                    'api_access_token' => $apiAccessToken, // Usar el token recuperado de la base de datos
                    'Accept' => 'application/json',
                ],
            ]);

            // Si la respuesta es exitosa, la registramos en los logs
            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody(), true);
                Log::info('Respuesta de la API de FasiaCRM:', $data);
                $this->info('La respuesta de la API ha sido registrada en los logs.');
            } else {
                $this->error('Error al consultar la API de FasiaCRM. Código de estado: ' . $response->getStatusCode());
            }

        } catch (\Exception $e) {
            // En caso de error en la solicitud, registrar el error
            Log::error('Error al hacer la solicitud a la API de FasiaCRM: ' . $e->getMessage());
            $this->error('Hubo un problema al hacer la solicitud. Revisa los logs para más detalles.');
        }
    }
}
