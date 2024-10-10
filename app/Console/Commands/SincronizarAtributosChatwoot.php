<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cliente;
use App\Models\AtributoPersonalizado;
use Illuminate\Support\Facades\Http;

class SincronizarAtributosChatwoot extends Command
{
    protected $signature = 'sync:chatwoot {--id_cliente=}'; // Opción para sincronizar un cliente específico
    protected $description = 'Sincroniza los atributos personalizados de los clientes desde Chatwoot';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $idCliente = $this->option('id_cliente'); // Obtenemos el ID del cliente, si se pasa

        $clientes = $idCliente
            ? Cliente::where('id_cliente', $idCliente)->get() // Sincronizamos solo un cliente
            : Cliente::whereNotNull('id_account')->get(); // O sincronizamos todos si no se pasa un ID

        $baseUrl = 'https://app.fasiacrm.com'; // URL de Chatwoot

        foreach ($clientes as $cliente) {
            // Llamada a la API de Chatwoot para obtener los atributos personalizados
            $response = Http::withHeaders([
                'api_access_token' => $cliente->token,
            ])->get($baseUrl . '/api/v1/accounts/' . $cliente->id_account . '/custom_attribute_definitions');

            if ($response->successful()) {
                $atributos = $response->json();

                foreach ($atributos as $atributo) {
                    $nombreAtributo = $atributo['attribute_display_name'] ?? 'Atributo Desconocido';
                    $claveAtributo = $atributo['attribute_key'] ?? 'clave_desconocida';
                    $valorAtributo = !empty($atributo['attribute_values']) ? json_encode($atributo['attribute_values']) : ($atributo['default_value'] ?? '');

                    AtributoPersonalizado::updateOrCreate(
                        [
                            'id_cliente' => $cliente->id_cliente,
                            'nombre_atributo' => $nombreAtributo,
                            'id_account' => $cliente->id_account,
                        ],
                        [
                            'valor_atributo' => $valorAtributo,
                        ]
                    );
                }
            } else {
                $this->error('Error al sincronizar el cliente: ' . $cliente->nombre_empresa);
            }
        }

        $this->info('Sincronización completa.');
    }
}
