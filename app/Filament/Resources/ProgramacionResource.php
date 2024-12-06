<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramacionResource\Pages;
use App\Filament\Resources\ProgramacionResource\RelationManagers;
use App\Models\Programacion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\HasClientFilter;
use App\Traits\HasSuperAdminAccess;
use App\Traits\HasNavigationConfig;

class ProgramacionResource extends Resource
{
    use HasClientFilter, HasSuperAdminAccess, HasNavigationConfig;

    protected static ?string $model = Programacion::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Programación';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Select::make('tipo')
                ->label('Tipo')
                ->options([
                    'Segmento' => 'Segmento', // Aquí agregas las opciones, por ahora solo "Segmento"
                ])
                ->required()
                ->default('Segmento') // Establecemos el valor por defecto a "Segmento"
                ->reactive() // Hace que el formulario se actualice dinámicamente cuando se cambia el valor
                ->afterStateUpdated(function (callable $set) {
                    // Cuando cambia el tipo, reseteamos el segmento a null si es necesario
                    $set('segmento_id', null);
                }),
            Select::make('segmento_id')  // El campo select para los segmentos
                ->label('Segmento')
                ->options(function (callable $get) {
                    // Solo mostramos los segmentos si el tipo es "Segmento"
                    if ($get('tipo') === 'Segmento') {
                        // Obtener los segmentos del cliente asociado al usuario logueado
                        $userId = auth()->id();
                        $clienteUser = \App\Models\ClienteUser::where('user_id', $userId)->first();
                        if ($clienteUser) {
                            // Retornamos los segmentos que pertenecen a este cliente
                            return \App\Models\Segmento::where('cliente_id', $clienteUser->cliente_id)
                                ->pluck('nombre', 'id');
                        }
                    }

                    // Retornamos un array vacío si no se selecciona "Segmento" como tipo
                    return [];
                })
                ->visible(fn (callable $get) => $get('tipo') === 'Segmento') 
                ->searchable()
                ->required(),  
            
            DateTimePicker::make('fecha_programada')
                ->label('Fecha y Hora de Programación')
                ->required(),
            
            Select::make('estado')
                ->options([
                    0 => 'Pendiente',
                    1 => 'Procesado',
                ])
                ->default(0) // El valor por defecto es 0 (Pendiente)
                ->label('Estado'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tipo')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('fecha_programada')->sortable(),
                Tables\Columns\TextColumn::make('estado_text')  // Usar el atributo `estado_text`
                    ->label('Estado') // Puedes personalizar el encabezado si lo deseas
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                // Filtros si es necesario
            ])
            ->actions([
                // Acciones si es necesario
            ])
            ->bulkActions([
                // Acciones en bloque si es necesario
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::getClientFilteredQuery(); // Llama al método del trait
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
            'index' => Pages\ListProgramacions::route('/'),
            'create' => Pages\CreateProgramacion::route('/create'),
            'edit' => Pages\EditProgramacion::route('/{record}/edit'),
        ];
    }
}
