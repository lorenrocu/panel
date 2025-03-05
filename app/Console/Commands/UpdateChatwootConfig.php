<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class UpdateChatwootConfig extends Command
{
    protected $signature = 'chatwoot:update-config';
    protected $description = 'Actualiza la tabla installation_configs en la base de datos de Chatwoot y ejecuta un script remoto';

    public function handle()
    {
        // Instanciar el cliente Guzzle para las peticiones HTTP
        $client = new Client();

        try {
            // Iniciar transacción en la base de datos de Chatwoot
            DB::connection('pgsql_chatwoot')->beginTransaction();

            // Valores a actualizar, obtenidos de variables de entorno y con valores por defecto
            $values = [
                34 => env('CHATWOOT_SUPPORT_SCRIPT_URL'),
                1  => env('CHATWOOT_INSTALLATION_NAME'),
                2  => env('CHATWOOT_LOGO_THUMBNAIL'),
                7  => env('CHATWOOT_BRAND_NAME'),
                3  => env('CHATWOOT_LOGO'),
                4  => env('CHATWOOT_LOGO_DARK'),
                5  => env('CHATWOOT_BRAND_URL'),
                6  => env('CHATWOOT_WIDGET_BRAND_URL'),
                8  => env('CHATWOOT_TERMS_URL'),
                9  => env('CHATWOOT_PRIVACY_URL'),
                10 => 'false', // Se guarda como cadena
                31 => 'premium'
            ];

            // Para los IDs que requieren que el valor vaya entre comillas escapadas
            $enclosed_ids = [2, 3, 4, 10];

            // Construir y ejecutar cada actualización de forma individual
            foreach ($values as $id => $value) {
                if (in_array($id, $enclosed_ids)) {
                    $formatted_value = '\\"' . $value . '\\"';
                } else {
                    $formatted_value = $value;
                }
                // Se genera la cadena exacta como en Node:
                // " --- !ruby/hash:ActiveSupport::HashWithIndifferentAccess\nvalue: <valor formateado>\n"
                $serialized_value = '"--- !ruby/hash:ActiveSupport::HashWithIndifferentAccess\\nvalue: ' . $formatted_value . '\\n"';

                DB::connection('pgsql_chatwoot')->update(
                    "UPDATE installation_configs SET serialized_value = ? WHERE id = ?",
                    [$serialized_value, $id]
                );
            }

            // Confirmar la transacción
            DB::connection('pgsql_chatwoot')->commit();
            $this->info('Actualización realizada en la tabla installation_configs de la base de datos de Chatwoot');

            // Llamar a la API de Puppeteer con el mismo endpoint que en Node
            $response = $client->get('http://135.181.87.23:3007/run-script');
            $body = trim((string)$response->getBody());
            $this->info('Respuesta de la API Puppeteer: ' . $body);

            if ($body === 'Interacción completada con éxito') {
                // Actualización adicional: cambiar el valor del id 31 a "community"
                $new_value = 'community';
                $serialized_value = '"--- !ruby/hash:ActiveSupport::HashWithIndifferentAccess\\nvalue: ' . $new_value . '\\n"';
                DB::connection('pgsql_chatwoot')->update(
                    "UPDATE installation_configs SET serialized_value = ? WHERE id = ?",
                    [$serialized_value, 31]
                );
                $this->info('Actualización adicional realizada en la tabla installation_configs de la base de datos de Chatwoot');

                // Enviar notificación de éxito
                //$this->sendNotification('La tabla fue actualizada y el script Puppeteer fue ejecutado con éxito');
            }
        } catch (\Exception $e) {
            // En caso de error, revertir la transacción
            DB::connection('pgsql_chatwoot')->rollBack();
            $this->error('Error realizando la actualización: ' . $e->getMessage());
            $this->sendNotification('Hubo error al momento de actualizar la tabla');
        }
    }

    private function sendNotification($message)
    {
        try {
            $client = new Client();
            // Obtener los parámetros desde el archivo .env
            $apiUrl = env('EVO_API_URL');
            $apiKey = env('EVO_API_KEY');
            // Concatenar el endpoint deseado
            $endpoint = $apiUrl . '/message/sendText/Molino Digital';
    
            $response = $client->post($endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'apikey' => $apiKey
                ],
                'json' => [
                    'number' => '51938649457',
                    'text' => $message
                ]
            ]);
    
            $this->info('Notificación enviada: ' . $response->getBody()->getContents());
        } catch (RequestException $e) {
            $this->error('Error enviando la notificación: ' . $e->getMessage());
        }
    }
    
}
