<?php

namespace App\Filament\Resources\SegmentoResource\Pages;

use App\Filament\Resources\SegmentoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSegmentos extends ListRecords
{
    protected static string $resource = SegmentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
