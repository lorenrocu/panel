<?php

namespace App\Traits;

use App\Models\ClienteUser;

trait HasClientFilter
{
    /**
     * Aplicar el filtro para obtener solo los registros asociados al cliente del usuario autenticado.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getClientFilteredQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $userId = auth()->id(); 
        $clienteUser = \App\Models\ClienteUser::where('user_id', $userId)->first();
    
        // Si no se encuentra un cliente asociado, retornar una consulta vacía
        if (!$clienteUser) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // No devolverá ningún registro
        }
    
        // Verificar si existe la columna 'cliente_id' o 'id_cliente'
        $column = \Schema::hasColumn('programaciones', 'cliente_id') ? 'cliente_id' : 'id_cliente';
    
        return parent::getEloquentQuery()->where($column, $clienteUser->cliente_id);
    }
}
