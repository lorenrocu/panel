<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use App\Models\Cliente;
use Filament\Notifications\Notification;

class ListClientes extends ListRecords
{
    protected static string $resource = ClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Nuevo Cliente')
                ->label('Nuevo Cliente')
                ->modalHeading('Crear un nuevo Cliente')
                ->modalWidth('lg')
                ->form([
                    Forms\Components\TextInput::make('nombre_empresa')
                        ->label('Nombre de la Empresa')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('id_plan')
                        ->label('Plan')
                        ->relationship('plan', 'nombre') // Relación con la tabla planes
                        ->required(),
                    Forms\Components\TextInput::make('token')
                        ->label('Token')
                        ->required(),
                    Forms\Components\TextInput::make('id_account')
                        ->label('ID de la Cuenta')
                        ->required(),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required(),
                ])
                ->action(function (array $data) {
                    Cliente::create($data);  // Crear el cliente con los datos del formulario

                    // Mostrar una notificación de éxito
                    Notification::make()
                        ->title('Cliente creado con éxito')
                        ->success()
                        ->send();
                })
                ->modalButton('Crear Cliente'),
        ];
    }
}
