<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Filament\Resources\PlanResource\RelationManagers;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form as FilamentForm;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;

class PlanResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function getLabel(): string
    {
        return 'Plan';
    }

    public static function getPluralLabel(): string
    {
        return 'Planes';
    }

    public static function getNavigationLabel(): string
    {
        return 'Planes';
    }
    
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    public static function form(FilamentForm $form): FilamentForm
    {
        return $form
            ->schema([
                TextInput::make('nombre')
                    ->label('Nombre del Plan')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id_plan')->label('ID'),
                TextColumn::make('nombre')->label('Nombre del Plan'),
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
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
