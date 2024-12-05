<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactoResource\Pages;
use App\Filament\Resources\ContactoResource\RelationManagers;
use App\Models\Contacto;
use App\Models\Segmento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TagsColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\HasSuperAdminAccess;
use App\Traits\HasNavigationConfig;

class ContactoResource extends Resource
{
    use HasSuperAdminAccess, HasNavigationConfig;

    protected static ?string $model = Contacto::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nombre')
                    ->required()
                    ->label('Nombre'),
                TextInput::make('celular')
                    ->required()
                    ->label('Celular')
                    ->maxLength(15),
                Select::make('segmento')
                    ->label('Segmento')
                    ->relationship('segmento', 'nombre', function ($query) {
                        // Aquí filtras los segmentos por el cliente del usuario logueado (igual que antes)
                        $clienteUser = \App\Models\ClienteUser::where('user_id', auth()->id())->first();
                        return $query->where('cliente_id', $clienteUser->cliente_id ?? null);
                    })
                    ->multiple()
                    ->searchable()
                    ->required()
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('nombre')->sortable()->searchable(),
                TextColumn::make('celular')->sortable()->searchable(),
                TextColumn::make('segmento')
                ->label('Segmento')
                ->getStateUsing(fn ($record) => $record->segmento ? $record->segmento->pluck('nombre')->join(', ') : ''),            
                TextColumn::make('created_at')->label('Fecha de Creación')->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
        return parent::getEloquentQuery()
            ->where('cliente_id', $clienteUser->cliente_id ?? null)
            ->with('segmento');
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
            'index' => Pages\ListContactos::route('/'),
            'create' => Pages\CreateContacto::route('/create'),
            'edit' => Pages\EditContacto::route('/{record}/edit'),
        ];
    }
}
