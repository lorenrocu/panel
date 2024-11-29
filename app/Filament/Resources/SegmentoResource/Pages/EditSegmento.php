<?php

namespace App\Filament\Resources\SegmentoResource\Pages;

use App\Filament\Resources\SegmentoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSegmento extends EditRecord
{
    protected static string $resource = SegmentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
