<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use App\Models\AtributoPersonalizado;
use Filament\Forms;
use Filament\Forms\Components\View;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;
use App\Models\Plan;
use Illuminate\Support\Facades\Log;
use Filament\Facades\Filament;
use Filament\Pages\Actions\Action;



class EditCliente extends EditRecord
{
    protected static string $resource = ClienteResource::class;

    public $atributos_personalizados = [];

    protected $atributosOcultos = [
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

    protected $listeners = ['refreshComponent'];

public function refreshComponent()
{
    $this->emit('refresh');
    $this->record->refresh();
}


    public function mount($record): void
    {
        parent::mount($record);

        // Inicializamos el array con los valores existentes, filtrando los atributos a ocultar
        $this->atributos_personalizados = $this->record->atributosPersonalizados()
            ->orderBy('orden')
            ->whereNotIn('attribute_key', $this->atributosOcultos)
            ->pluck('valor_por_defecto', 'id')
            ->toArray();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        // Obtenemos los atributos personalizados y filtramos los que no deben mostrarse
        $atributosPersonalizados = $this->record->atributosPersonalizados()
            ->orderBy('orden')
            ->whereNotIn('attribute_key', $this->atributosOcultos)
            ->get();
        
        // Obtenemos solo los atributos del cliente actual que tienen datos en la columna valor_atributo
        $atributosConValores = AtributoPersonalizado::where('id_cliente', $this->record->id_cliente)
            ->whereNotNull('valor_atributo')           // El atributo debe tener un valor no nulo
            ->where('valor_atributo', '!=', '')        // El valor no debe estar vacío
            ->pluck('nombre_atributo', 'id');          // Obtenemos los atributos con valor
        
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre_empresa')
                    ->required()
                    ->label('Nombre del Cliente'),
    
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->label('Email'),
    
                Forms\Components\Select::make('id_plan')
                    ->label('Plan')
                    ->options(Plan::query()->pluck('nombre', 'id_plan'))
                    ->default($this->record->id_plan)
                    ->required(),
    
                Forms\Components\TextInput::make('token')
                    ->label('Token')
                    ->default($this->record->token),
    
                Forms\Components\TextInput::make('id_account')
                    ->label('Account ID')
                    ->default($this->record->id_account),
    
                Forms\Components\Section::make('Atributos Personalizados')
                    ->schema([
                        View::make('forms.custom-attributes')
                            ->viewData([
                                'atributosPersonalizados' => $atributosPersonalizados,
                            ]),

                                    // Agregar esto justo después del View::make
        Forms\Components\Actions::make([
            Forms\Components\Actions\Action::make('crear_atributo_personalizado')
                ->label('Nuevo atributo personalizado')
                ->action(function (array $data) {
                    AtributoPersonalizado::create($data);
                })
                ->form([
                    Forms\Components\Select::make('id_cliente')
                        ->label('Cliente')
                        ->relationship('cliente', 'nombre_empresa')
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $cliente = \App\Models\Cliente::find($state);
                            if ($cliente) {
                                $set('id_account', $cliente->id_account);
                            }
                        }),

                    Forms\Components\Hidden::make('id_account')->required(),

                    Forms\Components\TextInput::make('nombre_atributo')
                        ->label('Nombre del Atributo')
                        ->required()
                        ->reactive()
                        ->debounce(1000)
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('attribute_key', \Str::snake($state));
                        }),

                    Forms\Components\TextInput::make('attribute_key')
                        ->label('Clave del Atributo')
                        ->required(),

                    Forms\Components\Select::make('tipo_atributo')
                        ->label('Tipo de Atributo')
                        ->options([
                            'text' => 'Text',
                            'list' => 'List',
                        ])
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state === 'text') {
                                $set('valor_atributo', null);
                            }
                        }),

                    Forms\Components\Repeater::make('opciones')
                        ->label('Opciones para el List')
                        ->schema([
                            Forms\Components\TextInput::make('opcion')->label('Opción')->required(),
                        ])
                        ->visible(fn ($get) => $get('tipo_atributo') === 'list')
                        ->required(fn ($get) => $get('tipo_atributo') === 'list')
                        ->columns(1)
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('valor_atributo', collect($state)->pluck('opcion')->toJson());
                        }),

                    Forms\Components\Hidden::make('valor_atributo')->required(),
                ])
                ->modalHeading('Crear Nuevo Atributo Personalizado')
                ->modalButton('Guardar')
                ->modalWidth('lg'),
        ]),

                            Forms\Components\Checkbox::make('mostrar_atributos')
                    ->label('Mostrar atributos personalizados')
                    ->reactive(),  // El checkbox será reactivo para detectar cambios
    
                        // Nuevo grupo de campos
                        Forms\Components\Group::make()
                            ->schema([
                                // Select del lado izquierdo
                                Forms\Components\Select::make('atributo_seleccionado')
                                    ->label('Atributo')
                                    ->options($atributosConValores)  // Mostrar solo atributos con valor no nulo y no vacío
                                    //->required()
                                    ->reactive() // El campo será reactivo para actualizar el valor
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // Obtener el valor del atributo seleccionado y actualizar el campo de valor
                                        $atributo = AtributoPersonalizado::find($state);
                                        if ($atributo) {
                                            $set('valor_atributo', $atributo->valor_atributo);
                                        }
                                    }),
    
                                // Campo de texto del lado derecho para el valor
                                Forms\Components\TextInput::make('valor_atributo')
                                ->label('Valor')
                                //->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    // Convertimos los valores de la BD en una lista de elementos
                                    $atributo = AtributoPersonalizado::find($state);
                                    if ($atributo) {
                                        $set('valor_atributo', json_encode($atributo->valor_atributo));
                                    }
                                })
                                ->view('components.drag-and-drop-list') 
                            ])
                            ->columns(2) // Dos columnas para los dos campos (select y text input)
                            //->visible(fn ($get) => $get('mostrar_atributos')), // Solo será visible si el checkbox está marcado
                    ])
                    ->collapsible(),
            ]);
    }
      

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('sincronizar')
                ->label('Sincronizar con Chatwoot')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    Artisan::call('sync:chatwoot', [
                        '--id_cliente' => $this->record->id_cliente,
                    ]);

                    Notification::make()
                        ->title('Sincronización completada')
                        ->success()
                        ->body('Sincronización completada para ' . $this->record->nombre_empresa)
                        ->send();
                }),
                Action::make('Conectar con Google')
                ->url(route('google.authenticate'))
                ->openUrlInNewTab(true)
                ->label('Conectar con Google'),
        ];
    }

    public function updateAttributeOrder($order)
    {
        // Obtener los IDs de los atributos visibles en el nuevo orden
        $visibleAttributeIdsInNewOrder = array_column($order, 'id');

        // Obtener todos los atributos (visibles y ocultos) ordenados por 'orden'
        $allAttributes = $this->record->atributosPersonalizados()->orderBy('orden')->get();

        // Crear un array para el nuevo orden
        $newOrder = [];

        // Índice para el nuevo orden
        $currentIndex = 0;

        // Contador para atributos visibles
        $visibleIndex = 0;

        // Recorrer los atributos actuales
        foreach ($allAttributes as $attribute) {
            if (in_array($attribute->id, $visibleAttributeIdsInNewOrder)) {
                // Si el atributo es visible, asignamos el índice basado en su posición en el nuevo orden
                $newPosition = array_search($attribute->id, $visibleAttributeIdsInNewOrder);
                $newOrder[$attribute->id] = $newPosition;
            } else {
                // Si el atributo es oculto, lo colocamos en su posición actual más el offset
                $newOrder[$attribute->id] = count($visibleAttributeIdsInNewOrder) + $currentIndex;
                $currentIndex++;
            }
        }

        // Ordenamos el array $newOrder por el valor de orden
        asort($newOrder);

        // Actualizamos el campo 'orden' en la base de datos
        foreach ($newOrder as $attributeId => $ordenValue) {
            AtributoPersonalizado::where('id', $attributeId)->update(['orden' => $ordenValue]);
        }

        // Refrescamos el registro para asegurarnos de que el orden está actualizado
        $this->record->refresh();
    }

    public function saveNewArrayOrder($data)
    {
        // Registrar los datos recibidos desde el frontend
        Log::info('Datos recibidos desde el frontend:', $data);
    
        // Extraer el ID y los nuevos valores
        $id = $data['id'];
        $newValues = json_encode($data['new_values']); // Convertimos el array en JSON
    
        Log::info('ID recibido:', ['id' => $id]);
        Log::info('Nuevo valor (JSON) recibido:', ['new_values' => $newValues]);
    
        // Intentar actualizar la base de datos
        try {
            $updated = AtributoPersonalizado::where('id', $id)
                ->update(['valor_atributo' => $newValues]);
    
            if ($updated) {
                Log::info('Actualización exitosa en la base de datos para el ID:', ['id' => $id]);
                // Emitir un evento Livewire para refrescar la vista del componente
                $this->emit('refreshComponent');
    
                // Retornar una respuesta de éxito
                return [
                    'success' => true,
                    'new_values' => $newValues,
                ];
            } else {
                Log::warning('No se pudo actualizar el registro en la base de datos para el ID:', ['id' => $id]);
                return [
                    'success' => false,
                    'message' => 'No se pudo actualizar el registro.',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error al actualizar la base de datos:', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Ocurrió un error al actualizar la base de datos.',
            ];
        }
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        // Actualizamos los atributos personalizados en la base de datos
        foreach ($this->atributos_personalizados as $idAtributo => $valorPorDefecto) {
            AtributoPersonalizado::where('id', $idAtributo)
                ->update([
                    'valor_por_defecto' => $valorPorDefecto,
                ]);
        }

        // Llamamos al método save del padre para guardar el registro principal
        parent::save($shouldRedirect, $shouldSendSavedNotification);

        // Después de guardar, llamamos al comando para eliminar los atributos personalizados
        Artisan::call('delete:chatwoot-attributes', [
            '--id_cliente' => $this->record->id_cliente,
        ]);
            // Verificar el resultado y notificar al usuario
            $outputDelete = Artisan::output();

            if (strpos($outputDelete, 'Eliminación de atributos personalizada completada.') !== false) {
                // Después de eliminar, llamamos al comando para crear los atributos personalizados
                Artisan::call('create:chatwoot-attributes', [
                    '--id_cliente' => $this->record->id_cliente,
                ]);
        
                // Capturamos y registramos la salida del comando
                $outputCreate = Artisan::output();
                Log::info('Salida del comando create:chatwoot-attributes: ' . $outputCreate);
        
                if (strpos($outputCreate, 'Creación de atributos en Fasia completada.') !== false) {
                    Notification::make()
                        ->title('Atributos sincronizados')
                        ->success()
                        ->body('Los atributos personalizados han sido sincronizados correctamente en Chatwoot.')
                        ->send();
                } else {
                    Notification::make()
                    ->title('Error al crear atributos')
                    ->danger()
                    ->body('Hubo un error al crear los atributos personalizados en Chatwoot. Por favor, revise los logs.')
                    ->send();
                }
            } else {
                Notification::make()
                    ->title('Error al eliminar atributos')
                    ->danger()
                    ->body('Hubo un error al eliminar los atributos personalizados en Chatwoot. Por favor, revise los logs.')
                    ->send();
            }
    }
}
