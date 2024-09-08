<?php

namespace App\Filament\Resources\CampanaFacebookResource\Pages;

use App\Filament\Resources\CampanaFacebookResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCampanaFacebook extends EditRecord
{
    protected static string $resource = CampanaFacebookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
