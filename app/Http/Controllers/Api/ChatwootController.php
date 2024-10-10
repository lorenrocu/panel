<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatwootController extends Controller
{
    public function actualizarContactoAtributos(Request $request)
    {
        // Almacenar el JSON recibido en los logs
        Log::info('Webhook de Chatwoot recibido', ['data' => $request->all()]);

        // Respuesta de éxito
        return response()->json(['status' => 'success', 'message' => 'Datos recibidos y registrados.'], 200);
    }
}