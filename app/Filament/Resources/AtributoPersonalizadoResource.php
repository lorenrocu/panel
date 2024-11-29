<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AtributoPersonalizadoResource\Pages;
use App\Models\AtributoPersonalizado;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use App\Traits\HasSuperAdminAccess;
use App\Traits\HasNavigationConfig;

class AtributoPersonalizadoResource extends Resource
{
    use HasSuperAdminAccess, HasNavigationConfig;
    
    protected static ?string $model = AtributoPersonalizado::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_cliente')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre_empresa')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $cliente = \App\Models\Cliente::find($state);
                        if ($cliente) {
                            $set('id_account', $cliente->id_account);
                        }
                    }),

                Forms\Components\Hidden::make('id_account')->required(),

                Forms\Components\TextInput::make('nombre_atributo')
                    ->required()
                    ->label('Nombre del Atributo')
                    ->reactive()
                    ->debounce(1000)
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('attribute_key', \Str::snake($state));
                    }),

                Forms\Components\TextInput::make('attribute_key')
                    ->label('Clave del Atributo')
                    ->required(),

                Forms\Components\Select::make('tipo_atributo')
                    ->label('Tipo de Atributo')
                    ->options([
                        'text' => 'Text',
                        'list' => 'List',
                    ])
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state === 'text') {
                            $set('valor_atributo', null);
                        }
                    }),

                Forms\Components\Repeater::make('opciones')
                    ->label('Opciones para el List')
                    ->schema([
                        Forms\Components\TextInput::make('opcion')->label('Opción')->required(),
                    ])
                    ->visible(fn ($get) => $get('tipo_atributo') === 'list')
                    ->required(fn ($get) => $get('tipo_atributo') === 'list')
                    ->columns(1)
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('valor_atributo', collect($state)->pluck('opcion')->toJson());
                    }),

                Forms\Components\Hidden::make('valor_atributo')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cliente.nombre_empresa')->label('Nombre Empresa')->sortable()->searchable(),
                TextColumn::make('nombre_atributo')->label('Nombre del Atributo')->sortable()->searchable(),
                TextColumn::make('valor_atributo')->label('Valor del Atributo')->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Reemplazamos EditAction con una acción personalizada
                Action::make('edit')
                    ->icon('heroicon-o-pencil')
                    ->button()
                    ->color('warning')
                    ->mountUsing(fn (Forms\Form $form, AtributoPersonalizado $record) => $form->fill([
                        'id_cliente' => $record->id_cliente,
                        'id_account' => $record->id_account,
                        'nombre_atributo' => $record->nombre_atributo,
                        'attribute_key' => $record->attribute_key,
                        'tipo_atributo' => $record->tipo_atributo,
                        'valor_atributo' => $record->tipo_atributo === 'text' ? '' : $record->valor_atributo,
                        'opciones' => $record->tipo_atributo === 'list' 
                            ? collect(json_decode($record->valor_atributo))->map(fn($opcion) => ['opcion' => $opcion])->toArray()
                            : [],
                    ]))
                    ->action(function (array $data, AtributoPersonalizado $record): void {
                        // Asignamos 'valor_atributo' a un string vacío si el tipo es 'text'
                        if ($data['tipo_atributo'] === 'text') {
                            $data['valor_atributo'] = '';
                        }
                        $record->update($data);
                    })
                    ->form(static::getFormSchema())
                    ->modalHeading('Editar Atributo Personalizado')
                    ->modalButton('Actualizar')
                    ->modalWidth('lg'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Action::make('crear_atributo_personalizado')
                    ->label('New atributo personalizado')
                    ->button()
                    ->action(function (array $data) {
                        // Asignamos 'valor_atributo' a un string vacío si el tipo es 'text'
                        if ($data['tipo_atributo'] === 'text') {
                            $data['valor_atributo'] = '';
                        }
                        AtributoPersonalizado::create($data);
                    })
                    ->form(static::getFormSchema())
                    ->modalHeading('Crear Nuevo Atributo Personalizado')
                    ->modalButton('Guardar')
                    ->requiresConfirmation(false)
                    ->modalWidth('lg'),
            ]);
    }
    
    // Método auxiliar para reutilizar el esquema del formulario
    protected static function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('id_cliente')
                ->label('Cliente')
                ->relationship('cliente', 'nombre_empresa')
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $cliente = \App\Models\Cliente::find($state);
                    if ($cliente) {
                        $set('id_account', $cliente->id_account);
                    }
                }),
    
            Forms\Components\Hidden::make('id_account')->required(),
    
            Forms\Components\TextInput::make('nombre_atributo')
                ->label('Nombre del Atributo')
                ->required()
                ->reactive()
                ->debounce(1000)
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('attribute_key', \Str::snake($state));
                }),
    
            Forms\Components\TextInput::make('attribute_key')
                ->label('Clave del Atributo')
                ->required(),
    
            Forms\Components\Select::make('tipo_atributo')
                ->label('Tipo de Atributo')
                ->options([
                    'text' => 'Text',
                    'list' => 'List',
                ])
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state === 'text') {
                        $set('valor_atributo', '');
                    }
                }),
    
            Forms\Components\Repeater::make('opciones')
                ->label('Opciones para el List')
                ->schema([
                    Forms\Components\TextInput::make('opcion')->label('Opción')->required(),
                ])
                ->visible(fn ($get) => $get('tipo_atributo') === 'list')
                ->required(fn ($get) => $get('tipo_atributo') === 'list')
                ->columns(1)
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('valor_atributo', collect($state)->pluck('opcion')->toJson());
                }),
    
            // Cambiamos 'Hidden' por 'TextInput' con visibilidad condicional
            Forms\Components\TextInput::make('valor_atributo')
            ->label('Valor del Atributo')
            ->default('')
            ->hidden()
        ];
    }
    

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAtributoPersonalizados::route('/'),
        ];
    }
}