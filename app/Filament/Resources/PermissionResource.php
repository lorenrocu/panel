<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Filament\Resources\PermissionResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Spatie\Permission\Models\Permission;


class PermissionResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('admin');
    }
    
    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    
    public static function getLabel(): string
    {
        return 'Permiso';
    }

    public static function getPluralLabel(): string
    {
        return 'Permisos';
    }

    public static function getNavigationLabel(): string
    {
        return 'Permisos';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->label('Permission Name')->required(),
            ]);
    }

    public static function getNavigationGroup(): string
    {
        return 'Administración'; // Agrupar bajo una sección del menú
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('Permission Name')->sortable()->searchable(),
                TextColumn::make('created_at')->label('Created At')->dateTime(),
            ])
            ->filters([
                // Filtros si los necesitas
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
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
