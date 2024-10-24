<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Para manejar las consultas a la base de datos
use Carbon\Carbon; // Para manejar fechas

class RegistroIngresosWebController extends Controller
{
    public function store(Request $request)
    {
        // Obtener los datos del cuerpo de la solicitud
        $id_account = $request->input('id_account');
        $utms = $request->input('utms');

        // Log para seguimiento
        \Log::info('--- Nueva solicitud POST registro-ingresos-web ---');
        \Log::info('Body:', $request->all());

        try {
            // Validar que se reciban los datos necesarios
            if (!$id_account || !$utms) {
                return response()->json(['error' => 'Falta información requerida'], 400);
            }

            // Consultar si ya existen registros para el id_account
            $existingRecordsCount = DB::table('registro_ingresos_web')
                                      ->where('id_account', $id_account)
                                      ->count();

            // Determinar el siguiente id consecutivo
            $nextId = $existingRecordsCount + 1;

            // Obtener la hora actual en formato HH:mm
            $currentTime = Carbon::now()->format('H');

            // Obtener la fecha actual en formato yyyy-MM-dd
            $currentDate = Carbon::now()->format('Y-m-d');

            // Insertar el nuevo registro en la base de datos
            DB::table('registro_ingresos_web')->insert([
                'id' => $nextId,
                'id_account' => $id_account,
                'utms' => json_encode($utms), // Convertir utms a JSON si es necesario
                'hora' => $currentTime,
                'fecha' => $currentDate
            ]);

            // Devolver la respuesta con el ID del nuevo registro
            return response()->json([
                'success' => true,
                'message' => 'Registro de ingreso web realizado correctamente',
                'id' => $nextId  // Devolver el ID recién creado
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al registrar ingreso web:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al registrar ingreso web'], 500);
        }
    }
}
