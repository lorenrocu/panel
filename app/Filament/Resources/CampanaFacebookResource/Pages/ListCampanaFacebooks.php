<?php

namespace App\Filament\Resources\CampanaFacebookResource\Pages;

use App\Filament\Resources\CampanaFacebookResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use App\Models\Cliente;
use App\Models\CampanaFacebook;
use Filament\Notifications\Notification;

class ListCampanaFacebooks extends ListRecords
{
    protected static string $resource = CampanaFacebookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Nueva Campaña')
                ->label('Nueva Campaña Facebook')
                ->modalHeading('Crear una nueva Campaña Facebook')
                ->modalWidth('lg')
                ->form([
                    Select::make('id_cliente')
                        ->label('Cliente')
                        ->options(Cliente::all()->pluck('nombre_empresa', 'id_cliente'))
                        ->required()
                        ->reactive()                
                        ->afterStateUpdated(function ($state, callable $set) {
                            $cliente = Cliente::find($state);
                            $set('id_account', $cliente ? $cliente->id_account : null);
                        }),

                    TextInput::make('id_campana')
                        ->label('ID de la Campaña')
                        ->required(),

                    TextInput::make('utm_source')->label('UTM Source')->nullable(),
                    TextInput::make('utm_medium')->label('UTM Medium')->nullable(),
                    TextInput::make('utm_term')->label('UTM Term')->nullable(),
                    TextInput::make('utm_content')->label('UTM Content')->nullable(),
                    TextInput::make('utm_campaign')->label('UTM Campaign')->nullable(),
                ])
                ->action(function (array $data) {
                    // Validar que id_cliente esté presente
                    if (!isset($data['id_cliente']) || empty($data['id_cliente'])) {
                        throw new \Exception('El campo id_cliente es obligatorio.');
                    }

                    // Validar si el cliente ya tiene esa campaña
                    $existe = CampanaFacebook::where('id_cliente', $data['id_cliente'])
                        ->where('id_campana', $data['id_campana'])
                        ->exists();

                    if ($existe) {
                        Notification::make()
                            ->title('Error')
                            ->body('El cliente ya tiene una campaña con ese ID.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Guardar el cliente y el id_account internamente
                    $cliente = Cliente::find($data['id_cliente']);
                    
                    // Asegurarnos de que el cliente se encontró
                    if (!$cliente) {
                        throw new \Exception('El cliente no se encontró.');
                    }
                
                    // Asignar el id_account y verificar si está correcto
                    $data['id_account'] = $cliente->id_account;
                    
                    // Verifica que el id_account sea válido
                    if (!$data['id_account']) {
                        throw new \Exception('El cliente seleccionado no tiene un id_account válido.');
                    }
                
                    // Asegurarse de que id_cliente se incluya en el array de datos
                    $data['id_cliente'] = $cliente->id_cliente;
                
                    // Crear la nueva campaña de Facebook con id_cliente
                    CampanaFacebook::create([
                        'id_cliente' => $data['id_cliente'],
                        'id_campana' => $data['id_campana'],
                        'utm_source' => $data['utm_source'],
                        'utm_medium' => $data['utm_medium'],
                        'utm_term' => $data['utm_term'],
                        'utm_content' => $data['utm_content'],
                        'utm_campaign' => $data['utm_campaign'],
                        'id_account' => $data['id_account'],
                    ]);

                    Notification::make()
                        ->title('Éxito')
                        ->body('Campaña creada exitosamente.')
                        ->success()
                        ->send();
                })
                ->modalButton('Crear Campaña'),
        ];
    }
}
