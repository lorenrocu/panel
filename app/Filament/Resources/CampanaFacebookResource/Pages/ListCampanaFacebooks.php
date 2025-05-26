<?php

namespace App\Filament\Resources\CampanaFacebookResource\Pages;

use App\Filament\Resources\CampanaFacebookResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use App\Models\Cliente;
use Filament\Forms;
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
                ->form(function () {
                    $user = auth()->user();
                    $conditionalFields = [];

                    if ($user->hasRole('client')) {
                        $cliente = $user->clientes()->first();
                        if ($cliente) {
                            $conditionalFields[] = Forms\Components\Hidden::make('id_cliente')->default($cliente->id_cliente);
                            // El campo id_account se omite aquí para ocultarlo al rol 'client'
                            // Su valor se gestionará internamente en mutateFormDataUsing y en la acción.
                        } else {
                            // Manejo si el cliente no tiene cliente asociado
                            $conditionalFields[] = Select::make('id_cliente')
                                ->label('Empresa')
                                ->options(Cliente::all()->pluck('nombre_empresa', 'id_cliente'))
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $cliente = Cliente::find($state);
                                    $set('id_account', $cliente ? $cliente->id_account : null);
                                });
                            // El campo id_account también se omite aquí para el rol 'client' en este caso.
                        }
                    } else {
                        $conditionalFields[] = Select::make('id_cliente')
                            ->label('Empresa')
                            ->options(Cliente::all()->pluck('nombre_empresa', 'id_cliente'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                    $cliente = Cliente::find($state);
                                    $set('id_account', $cliente ? $cliente->id_account : null);
                                });
                        $conditionalFields[] = TextInput::make('id_account')
                            ->label('ID de la Cuenta')
                            ->disabled() // Se llenará reactivamente
                            ->required();
                    }

                    $commonFields = [
                        TextInput::make('id_campana')
                            ->label('ID de la Campaña')
                            ->required(),
                        TextInput::make('utm_source')->label('UTM Source')->nullable(),
                        TextInput::make('utm_medium')->label('UTM Medium')->nullable(),
                        TextInput::make('utm_term')->label('UTM Term')->nullable(),
                        TextInput::make('utm_content')->label('UTM Content')->nullable(),
                        TextInput::make('utm_campaign')->label('UTM Campaign')->nullable(),
                    ];

                    return array_merge($conditionalFields, $commonFields);
                })
                ->mutateFormDataUsing(function (array $data): array {
                    $user = auth()->user();
                    if ($user->hasRole('client')) {
                        $cliente = $user->clientes()->first();
                        if ($cliente) {
                            $data['id_cliente'] = $cliente->id_cliente;
                            if (empty($data['id_account']) && $cliente->id_account) {
                                $data['id_account'] = $cliente->id_account;
                            }
                        }
                    }
                    
                    if (isset($data['id_cliente']) && (!isset($data['id_account']) || empty($data['id_account']))) {
                        $clienteModel = Cliente::find($data['id_cliente']);
                        if ($clienteModel && $clienteModel->id_account) {
                            $data['id_account'] = $clienteModel->id_account;
                        }
                    }
                    return $data;
                })
                ->action(function (array $data) {
                    if (!isset($data['id_cliente']) || empty($data['id_cliente'])) {
                         Notification::make()->title('Error de Validación')->body('El campo Empresa es obligatorio.')->danger()->send();
                        return;
                    }
                    
                    $cliente = Cliente::find($data['id_cliente']);
                    if (!$cliente) {
                        Notification::make()->title('Error')->body('Cliente no encontrado. Por favor, seleccione una empresa válida.')->danger()->send();
                        return;
                    }
                    
                    $final_id_account = $data['id_account'] ?? $cliente->id_account;

                    if (empty($final_id_account)) {
                        Notification::make()->title('Error de Configuración')->body('El ID de la Cuenta (id_account) para la empresa seleccionada no está configurado o está vacío. Por favor, actualice la información de la empresa.')->danger()->send();
                        return;
                    }
                    
                    if (!isset($data['id_campana']) || empty($data['id_campana'])) {
                        Notification::make()->title('Error de Validación')->body('El campo ID de la Campaña es obligatorio.')->danger()->send();
                        return;
                    }

                    $existe = CampanaFacebook::where('id_cliente', $data['id_cliente'])
                        ->where('id_campana', $data['id_campana'])
                        ->exists();

                    if ($existe) {
                        Notification::make()
                            ->title('Registro Duplicado')
                            ->body('Ya existe un registro con el mismo ID de Cliente y ID de Campaña.')
                            ->danger()
                            ->send();
                        return;
                    }
                
                    CampanaFacebook::create([
                        'id_cliente' => $data['id_cliente'],
                        'id_campana' => $data['id_campana'],
                        'utm_source' => $data['utm_source'] ?? null,
                        'utm_medium' => $data['utm_medium'] ?? null,
                        'utm_term' => $data['utm_term'] ?? null,
                        'utm_content' => $data['utm_content'] ?? null,
                        'utm_campaign' => $data['utm_campaign'] ?? null,
                        'id_account' => $final_id_account,
                    ]);

                    Notification::make()
                        ->title('Éxito')
                        ->body('La campaña de Facebook ha sido creada exitosamente.')
                        ->success()
                        ->send();
                })
                ->modalButton('Crear Campaña'),
        ];
    }
}
