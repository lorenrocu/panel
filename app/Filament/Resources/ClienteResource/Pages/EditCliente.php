<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use App\Models\AtributoPersonalizado;
use Filament\Forms;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;
use App\Models\Plan; // Asegúrate de importar el modelo Plan

class EditCliente extends EditRecord
{
    protected static string $resource = ClienteResource::class;

    public function form(Forms\Form $form): Forms\Form
    {
        $atributosPersonalizados = $this->record->atributosPersonalizados()->get();
    
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
                    ->schema(
                        $atributosPersonalizados->map(function ($atributo) {
                            if (is_array(json_decode($atributo->valor_atributo, true))) {
                                return Forms\Components\Select::make('atributos_personalizados.' . $atributo->id)
                                    ->label($atributo->nombre_atributo)
                                    ->options(json_decode($atributo->valor_atributo, true))
                                    ->afterStateHydrated(function ($component) use ($atributo) {
                                        $component->state($atributo->valor_por_defecto);
                                    })
                                    ->placeholder('Seleccione una opción');
                            } else {
                                return Forms\Components\TextInput::make('atributos_personalizados.' . $atributo->id)
                                    ->label($atributo->nombre_atributo)
                                    ->afterStateHydrated(function ($component) use ($atributo) {
                                        $component->state($atributo->valor_por_defecto);
                                    })
                                    ->placeholder('Ingrese un valor por defecto');
                            }
                        })->toArray()
                    )
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

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        $data = $this->form->getState();
    
        if (isset($data['atributos_personalizados'])) {
            foreach ($data['atributos_personalizados'] as $idAtributo => $valorPorDefecto) {
                if (!is_null($valorPorDefecto)) {
                    AtributoPersonalizado::where('id', $idAtributo)
                        ->update([
                            'valor_por_defecto' => $valorPorDefecto
                        ]);
                }
            }
        }
    
        parent::save($shouldRedirect, $shouldSendSavedNotification);
    }
    
}    
