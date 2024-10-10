<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AtributoPersonalizadoResource\Pages;
use App\Filament\Resources\AtributoPersonalizadoResource\RelationManagers;
use App\Models\AtributoPersonalizado;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AtributoPersonalizadoResource extends Resource
{
    protected static ?string $model = AtributoPersonalizado::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre_empresa')
                    ->required()
                    ->label('Nombre del Cliente'),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->label('Email del Cliente'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre_empresa') // Mostrar el nombre del cliente
                    ->label('Nombre del Cliente')
                    ->sortable(),
                Tables\Columns\TextColumn::make('email') // Mostrar el email del cliente
                    ->label('Email del Cliente')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('sincronizar')
                    ->label('Recargar Atributos')
                    ->icon('heroicon-o-arrow-path') // Ícono de recargar
                    ->action(function (Cliente $record) {
                        // Ejecutar el comando de Artisan para sincronizar los atributos del cliente
                        Artisan::call('sync:chatwoot', [
                            '--id_cliente' => $record->id_cliente, // Pasar el ID del cliente para la sincronización
                        ]);
                        // Notificación de éxito
                        $this->notify('success', 'Atributos sincronizados correctamente para ' . $record->nombre_empresa);
                    }),
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
            'index' => Pages\ListAtributoPersonalizados::route('/'),
            'create' => Pages\CreateAtributoPersonalizado::route('/create'),
            'edit' => Pages\EditAtributoPersonalizado::route('/{record}/edit'),
        ];
    }
}
