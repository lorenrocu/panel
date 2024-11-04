<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\AtributoPersonalizado;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Filament\Notifications\Notification;

class AttributeController extends Controller
{
    public function delete($id)
    {
        try {
            // Buscar el atributo personalizado que se va a eliminar
            $atributo = AtributoPersonalizado::findOrFail($id);
            $idCliente = $atributo->id_cliente; // Obtén el ID del cliente relacionado
            $customAttributeId = $atributo->custom_attribute_id; // Obtén el custom_attribute_id del atributo

            // Eliminar el atributo de la base de datos
            $atributo->delete();

            // Llamar al comando Artisan para eliminar el atributo de Chatwoot (solo ese atributo)
            Artisan::call('delete:chatwoot-attributes', [
                '--id_cliente' => $idCliente,
                '--custom_attribute_id' => $customAttributeId, // Pasar el custom_attribute_id al comando
            ]);

            // Mostrar una notificación de éxito usando Filament
            Notification::make()
                ->title('Atributo eliminado')
                ->body("El atributo ha sido eliminado correctamente de la base de datos y Chatwoot.")
                ->success()
                ->send();

            return response()->json([
                'success' => true,
                'message' => 'Atributo eliminado correctamente de la base de datos y Chatwoot'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar el atributo:', ['error' => $e->getMessage()]);

            // Mostrar una notificación de error usando Filament
            Notification::make()
                ->title('Error al eliminar')
                ->body('Hubo un error al eliminar el atributo. Revisa los logs para más detalles.')
                ->danger()
                ->send();

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el atributo'
            ], 500);
        }
    }
}