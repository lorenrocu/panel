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

        $baseUrl = env('URL_CHATWOOT');

        // Definir los valores predeterminados para los nuevos atributos
        $valoresPredeterminados = [
            'estado_contacto' => [
                'No contactado',
                'Contactado',
                'Agendado',
                'Agendado fin',
                'Cotizado',
                'Ganado sin pago',
                'Ganado pagado',
                'Perdido',
            ],
            'tipo_contacto' => [
                'Prospecto',
                'Cliente',
                'Proveedor',
                'Colaborador',
                'Spam',
            ],
        ];

        // Lista de atributos de seguimiento de campañas que deben estar al final
        $atributosCampana = [
            'utm_source',
            'utm_term',
            'utm_medium',
            'utm_id',
            'utm_content',
            'utm_campaign',
            'secondary_content',
            'id_campana',
            'gclid',
            'fbclid',
        ];

        // Lista de atributos generales que deben estar al principio
        $atributosGenerales = [
            'tipo_contacto',   
            'estado_contacto',  
        ];

        foreach ($clientes as $cliente) {
            // Llamada a la API de Chatwoot para obtener los atributos personalizados
            $response = Http::withHeaders([
                'api_access_token' => $cliente->token,
            ])->get($baseUrl . '/api/v1/accounts/' . $cliente->id_account . '/custom_attribute_definitions');

            if ($response->successful()) {
                $atributos = $response->json();

                // Orden inicial para atributos generales
                $orden = 1;

                // Sincronizar los atributos generales primero, asegurando que tengan un orden al inicio
                foreach ($atributosGenerales as $attributeKey) {
                    // Verificar si el atributo ya existe en la base de datos para este cliente
                    $existe = AtributoPersonalizado::where('id_cliente', $cliente->id_cliente)
                        ->where('attribute_key', $attributeKey)
                        ->exists();

                    if (!$existe) {
                        // Definir el nombre del atributo
                        $nombreAtributo = ucfirst(str_replace('_', ' ', $attributeKey));

                        // Asignar valores predeterminados si el atributo es uno de los nuevos
                        if (array_key_exists($attributeKey, $valoresPredeterminados)) {
                            $valorAtributo = json_encode($valoresPredeterminados[$attributeKey]);
                        } else {
                            $valorAtributo = ''; // Valor por defecto para otros atributos
                        }

                        // Crear el atributo requerido
                        AtributoPersonalizado::create([
                            'id_cliente' => $cliente->id_cliente,
                            'nombre_atributo' => $nombreAtributo,
                            'attribute_key' => $attributeKey,
                            'valor_atributo' => $valorAtributo,
                            'id_account' => $cliente->id_account,
                            'custom_attribute_id' => null, // No existe en Chatwoot
                            'orden' => $orden, // Asignamos el orden secuencial
                        ]);

                        $orden++; // Incrementar el orden para el próximo atributo general
                    }
                }

                // Luego, sincronizar los atributos personalizados obtenidos desde Chatwoot
                foreach ($atributos as $atributo) {
                    $nombreAtributo = $atributo['attribute_display_name'] ?? 'Atributo Desconocido';
                    $claveAtributo = $atributo['attribute_key'] ?? 'clave_desconocida';
                    $valorAtributo = !empty($atributo['attribute_values'])
                        ? json_encode($atributo['attribute_values'])
                        : ($atributo['default_value'] ?? '');

                    // Agregamos la captura del 'id' del atributo
                    $customAttributeId = $atributo['id'] ?? null;

                    // Definir el orden para los atributos obtenidos de la API, deben continuar donde se quedaron los generales
                    if (!in_array($claveAtributo, $atributosCampana)) {
                        AtributoPersonalizado::updateOrCreate(
                            [
                                'id_cliente' => $cliente->id_cliente,
                                'attribute_key' => $claveAtributo,
                                'id_account' => $cliente->id_account,
                            ],
                            [
                                'nombre_atributo' => $nombreAtributo,
                                'valor_atributo' => $valorAtributo,
                                'custom_attribute_id' => $customAttributeId, // Guardamos el 'id' aquí
                                'orden' => $orden, // Atributo personalizado toma el siguiente número de orden
                            ]
                        );

                        $orden++; // Incrementamos el orden secuencialmente
                    }
                }

                // Finalmente, sincronizamos los atributos de campañas, que deben estar siempre al final
                foreach ($atributosCampana as $attributeKey) {
                    // Verificar si el atributo ya existe en la base de datos para este cliente
                    $existe = AtributoPersonalizado::where('id_cliente', $cliente->id_cliente)
                        ->where('attribute_key', $attributeKey)
                        ->exists();

                    if (!$existe) {
                        // Definir el nombre del atributo
                        $nombreAtributo = ucfirst(str_replace('_', ' ', $attributeKey));

                        // Crear el atributo requerido con un valor vacío o predeterminado
                        AtributoPersonalizado::create([
                            'id_cliente' => $cliente->id_cliente,
                            'nombre_atributo' => $nombreAtributo,
                            'attribute_key' => $attributeKey,
                            'valor_atributo' => '', // Valor vacío por defecto
                            'id_account' => $cliente->id_account,
                            'custom_attribute_id' => null, // No existe en Chatwoot
                            'orden' => $orden, // Estos atributos van al final
                        ]);

                        $orden++; // Incrementamos el orden secuencialmente para cada atributo de campaña
                    }
                }

            } else {
                $this->error('Error al sincronizar el cliente: ' . $cliente->nombre_empresa);
            }
        }

        $this->info('Sincronización completa.');
    }
}
