<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegistroIngresosWebResource\Pages;
use App\Filament\Resources\RegistroIngresosWebResource\RelationManagers;
use App\Models\RegistroIngresosWeb;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;

class RegistroIngresosWebResource extends Resource
{
    protected static ?string $model = RegistroIngresosWeb::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('id_account')
                    ->required(),
                TextInput::make('registro_id')
                    ->required(),
                Textarea::make('utms')
                    ->required(),
                TimePicker::make('hora')
                    ->required(),
                DatePicker::make('fecha')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('id_account')
                    ->label('ID Account')
                    ->sortable(),
                TextColumn::make('registro_id')
                    ->label('Registro ID')
                    ->sortable(),
                TextColumn::make('utms')
                    ->label('UTMs')
                    ->limit(50),
                TextColumn::make('hora')
                    ->label('Hora'),
                TextColumn::make('fecha')
                    ->label('Fecha'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
    

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegistroIngresosWebs::route('/'),
            'create' => Pages\CreateRegistroIngresosWeb::route('/create'),
            'edit' => Pages\EditRegistroIngresosWeb::route('/{record}/edit'),
        ];
    }
}
