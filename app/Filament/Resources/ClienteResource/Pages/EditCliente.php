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

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        foreach ($this->atributos_personalizados as $idAtributo => $valorPorDefecto) {
            AtributoPersonalizado::where('id', $idAtributo)
                ->update([
                    'valor_por_defecto' => $valorPorDefecto,
                ]);
        }

        parent::save($shouldRedirect, $shouldSendSavedNotification);
    }
}
