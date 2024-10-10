<?php

namespace App\Filament\Resources\AtributoPersonalizadoResource\Pages;

use App\Filament\Resources\AtributoPersonalizadoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAtributoPersonalizados extends ListRecords
{
    protected static string $resource = AtributoPersonalizadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
