<?php

namespace App\Filament\Resources\RegistroIngresosWebResource\Pages;

use App\Filament\Resources\RegistroIngresosWebResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRegistroIngresosWebs extends ListRecords
{
    protected static string $resource = RegistroIngresosWebResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
