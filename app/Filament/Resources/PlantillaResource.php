<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlantillaResource\Pages;
use App\Filament\Resources\PlantillaResource\RelationManagers;
use App\Models\Plantilla;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasSuperAdminAccess;
use App\Traits\HasNavigationConfig;

class PlantillaResource extends Resource
{
    use HasSuperAdminAccess, HasNavigationConfig;

    protected static ?string $model = Plantilla::class;

    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\TextInput::make('nombre')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('mensaje')
                ->required(),
            Forms\Components\FileUpload::make('imagen')
                ->required()
                ->disk('public') // Configurar el sistema de almacenamiento
                ->directory('imagenes'), // Carpeta donde se guardarán las imágenes
            Forms\Components\Hidden::make('id_cliente'), // Campo oculto
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('mensaje')->limit(50),
                Tables\Columns\ImageColumn::make('imagen')->disk('public'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de actualización')
                    ->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $userId = Auth::id(); // Obtener el ID del usuario actual

        // Buscar en la tabla pivot el cliente asociado al usuario
        $clienteUser = \App\Models\ClienteUser::where('user_id', $userId)->first();

        // Filtrar registros de Plantilla solo por el cliente asociado
        return parent::getEloquentQuery()->where('id_cliente', $clienteUser->cliente_id ?? null);
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
            'index' => Pages\ListPlantillas::route('/'),
            'create' => Pages\CreatePlantilla::route('/create'),
            'edit' => Pages\EditPlantilla::route('/{record}/edit'),
        ];
    }
}
