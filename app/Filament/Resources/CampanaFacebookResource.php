<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampanaFacebookResource\Pages;
use App\Filament\Resources\CampanaFacebookResource\RelationManagers;
use App\Models\CampanaFacebook;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use App\Models\Cliente;

class CampanaFacebookResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->hasRole('staff');
    }
    
    protected static ?string $model = CampanaFacebook::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('id_cliente')
                ->label('Cliente')
                ->options(Cliente::all()->pluck('nombre_empresa', 'id_cliente'))  // Cambia 'id' por 'id_cliente'
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $cliente = Cliente::find($state);
                    $set('id_account', $cliente ? $cliente->id_account : null);
                }),
    
                TextInput::make('id_account')
                    ->label('ID de la Cuenta')
                    ->disabled() // Este campo será llenado automáticamente
                    ->required(),
    
                TextInput::make('id_campana')
                    ->label('ID de la Campaña')
                    ->required(),
    
                TextInput::make('utm_source')->label('UTM Source')->nullable(),
                TextInput::make('utm_medium')->label('UTM Medium')->nullable(),
                TextInput::make('utm_term')->label('UTM Term')->nullable(),
                TextInput::make('utm_content')->label('UTM Content')->nullable(),
                TextInput::make('utm_campaign')->label('UTM Campaign')->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cliente.nombre_empresa'),
                TextColumn::make('id_campana')->label('ID Campaña'),
                TextColumn::make('utm_source')->label('UTM Source'),
                TextColumn::make('utm_medium')->label('UTM Medium'),
                TextColumn::make('utm_term')->label('UTM Term'),
                TextColumn::make('utm_content')->label('UTM Content'),
                TextColumn::make('utm_campaign')->label('UTM Campaign'),
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
            'index' => Pages\ListCampanaFacebooks::route('/'),
            'create' => Pages\CreateCampanaFacebook::route('/create'),
            'edit' => Pages\EditCampanaFacebook::route('/{record}/edit'),
        ];
    }
}
