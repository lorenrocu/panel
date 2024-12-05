<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SegmentoResource\Pages;
use App\Models\Segmento;
use App\Models\Contacto;
use League\Csv\Reader;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasSuperAdminAccess;
use App\Traits\HasNavigationConfig;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Método que se ejecuta después de guardar el segmento.
     * Este método procesará el archivo CSV y guardará los contactos en la tabla.
     */
    public static function saved(Segmento $segmento): void
    {
        // Verificamos si el tipo de segmento es CSV y si el archivo existe
        if ($segmento->tipo_de_segmento === 'csv' && $segmento->archivo_csv) {
            // Obtener el path del archivo CSV
            $csvFilePath = storage_path('app/' . $segmento->archivo_csv);  // Puede estar en otro path si usas discos personalizados

            // Leer el archivo CSV usando la librería League\Csv
            $csv = Reader::createFromPath($csvFilePath, 'r');
            $csv->setHeaderOffset(0); // Indicar que la primera fila contiene los encabezados

            // Recorrer las filas del CSV y crear los contactos
            $records = $csv->getRecords();  // Obtiene todas las filas

            foreach ($records as $row) {
                // Crear un nuevo contacto para cada fila del CSV
                Contacto::create([
                    'nombre' => $row['nombre'],  // Asegúrate de que los encabezados del CSV coincidan con estos campos
                    'celular' => $row['celular'],
                    'segmento_id' => $segmento->id,  // Asignamos el segmento al contacto
                    'cliente_id' => $segmento->cliente_id,  // Asegúrate de que el cliente_id esté relacionado
                ]);
            }

            // Borrar el archivo CSV si ya no lo necesitas
            Storage::delete($segmento->archivo_csv);
        }
    }
}
