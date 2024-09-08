<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Filament\Resources\ClienteResource\RelationManagers;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form as FilamentForm;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class ClienteResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('admin');
    }
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(FilamentForm $form): FilamentForm
    {
        return $form
            ->schema([
                TextInput::make('nombre_empresa')
                    ->label('Nombre de la Empresa')
                    ->required(),
                Select::make('id_plan')
                    ->label('Plan')
                    ->relationship('plan', 'nombre')  // Asocia con la tabla planes
                    ->required(),
                TextInput::make('token')
                    ->label('Token')
                    ->required(),
                TextInput::make('id_account')
                    ->label('ID de la cuenta')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id_cliente')->label('ID Cliente'),
                TextColumn::make('nombre_empresa')->label('Nombre Empresa'),
                TextColumn::make('plan.nombre')->label('Plan'),  // Relación con la tabla planes
                TextColumn::make('token')->label('Token'),
                TextColumn::make('id_account')->label('ID de la Cuenta'),
                TextColumn::make('email')->label('Email'),
                TextColumn::make('created_at')->label('Creado el')
                    ->dateTime(),
            ])
            ->filters([
                // Puedes agregar filtros aquí si lo necesitas
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
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}
