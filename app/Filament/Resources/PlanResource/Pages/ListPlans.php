<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use App\Models\Plan;
use Filament\Notifications\Notification;


class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Nuevo Plan')
                ->label('Nuevo Plan')
                ->modalHeading('Crear un nuevo Plan')
                ->modalWidth('lg')
                ->form([
                    Forms\Components\TextInput::make('nombre')
                        ->required()
                        ->label('Nombre del Plan')
                        ->maxLength(255),
                ])
                ->action(function (array $data) {
                    Plan::create($data);

                    // Usar Notification::make() para mostrar la notificación
                    Notification::make()
                        ->title('Plan creado con éxito')
                        ->success()
                        ->send();
                })
                ->modalButton('Crear Plan'),
        ];
    }
}
