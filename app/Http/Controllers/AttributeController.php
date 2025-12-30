<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AtributoPersonalizado;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class AttributeController extends Controller
{
    public function delete($id)
    {
        try {
            // Buscar el atributo personalizado que se va a eliminar
            $atributo = AtributoPersonalizado::findOrFail($id);
            $idCliente = $atributo->id_cliente; // Obtén el ID del cliente relacionado

            // Eliminar el atributo de la base de datos
            $atributo->delete();

            // Llamar al comando Artisan para eliminar el atributo de Chatwoot
            Artisan::call('delete:chatwoot-attributes', [
                '--id_cliente' => $idCliente,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Atributo eliminado correctamente de la base de datos y Chatwoot'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar el atributo:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el atributo'
            ], 500);
        }
    }
}