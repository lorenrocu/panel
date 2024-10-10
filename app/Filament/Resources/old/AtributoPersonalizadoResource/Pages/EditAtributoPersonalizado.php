<?php

namespace App\Filament\Resources\AtributoPersonalizadoResource\Pages;

use App\Filament\Resources\AtributoPersonalizadoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAtributoPersonalizado extends EditRecord
{
    protected static string $resource = AtributoPersonalizadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
