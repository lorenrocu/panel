<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SegmentoResource\Pages;
use App\Models\Segmento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasSuperAdminAccess;
use App\Traits\HasNavigationConfig;

class SegmentoResource extends Resource
{
    use HasSuperAdminAccess, HasNavigationConfig;

    protected static ?string $model = Segmento::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Segmento';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(100),
                Forms\Components\Select::make('tipo_de_segmento')
                    ->label('Tipo de Segmento')
                    ->options([
                        'chatwoot' => 'Chatwoot',
                        'csv' => 'CSV',
                    ])
                    ->required()
                    ->reactive(),
                Forms\Components\FileUpload::make('archivo_csv')
                    ->label('Archivo CSV')
                    ->hidden(fn (callable $get) => $get('tipo_de_segmento') !== 'csv')
                    ->required(fn (callable $get) => $get('tipo_de_segmento') === 'csv'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tipo_de_segmento')->label('Tipo de Segmento'),
                Tables\Columns\TextColumn::make('nombre')->label('Nombre'),
                Tables\Columns\TextColumn::make('cliente.nombre_empresa')->label('Cliente'),
            ])
            ->filters([
                // Puedes añadir filtros si es necesario
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $userId = auth()->id(); // Obtener el ID del usuario actual

        // Obtener el cliente asociado al usuario actual
        $clienteUser = \App\Models\ClienteUser::where('user_id', $userId)->first();

        // Filtrar los registros de Segmento para que solo se muestren los del cliente asociado
        return parent::getEloquentQuery()->where('cliente_id', $clienteUser->cliente_id ?? null);
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
            'index' => Pages\ListSegmentos::route('/'),
            'create' => Pages\CreateSegmento::route('/create'),
            'edit' => Pages\EditSegmento::route('/{record}/edit'),
        ];
    }
}
