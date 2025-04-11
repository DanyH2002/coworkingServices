<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Espacios;
use App\Models\Reservaciones;
use Illuminate\Support\Facades\Hash;

class AdministradoresController extends Controller
{
    // lista de clientes
    public function listUsers()
    {
        $usuarios = Usuario::all();
        return response()->json([
            'status' => 1,
            'usuarios' => $usuarios,
        ], 200);
    }

    // Cambiar rol de usuario
    public function changeRol(Request $id)
    {
        $usuario = Usuario::find($id->id);
        if (!$usuario) {
            return response()->json([
                'status' => 0,
                'message' => 'Usuario no encontrado',
            ], 404);
        }
        $usuario->rol = $usuario->rol === 1 ? 0 : 1; // Cambiar rol
        $usuario->save();
        return response()->json([
            'status' => 1,
            'message' => 'Rol de usuario actualizado con éxito',
            'usuario' => $usuario,
        ], 200);
    }

    // Crear un clinete y/o un administrador
    public function createUser(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:usuarios',
            'password' => 'required|string|min:8|confirmed',
            'rol' => 'required|string|in:cliente,administrador',
        ]);

        $usuario = new Usuario();
        $usuario->nombre = $request->nombre;
        $usuario->email = $request->email;
        $usuario->password = Hash::make($request->password);;
        $usuario->rol = $request->rol === 'administrador' ? 1 : 0; // 1 = administrador, 0 = cliente
        $usuario->save();

        return response()->json([
            'status' => 1,
            'message' => 'Usuario creado con éxito',
            'usuario' => $usuario,
        ], 201);
    }

    public function dashboard()
    {
        // Reservaciones del dia
        $reservacionesHoy = Reservaciones::whereDate('fecha_reseva', now())->count();

        // Reservaciones pendientes
        $reservacionesPendientes = Reservaciones::where('estado', 'pendiente')->count();

        // Usuarios registrados
        $usuariosRegistrados = Usuario::count();

        // Espacios existentes
        $espacios = Espacios::count();

        // Ingresos del mes
        $ingresos = Reservaciones::whereMonth('fecha_reseva', now()->month)
            ->whereYear('fecha_reseva', now()->year)
            ->sum('total_precio');

        // Porcentaje de reservaciones del mes
        $reservacionesMes = Reservaciones::whereMonth('fecha_reseva', now()->month)
            ->whereYear('fecha_reseva', now()->year)
            ->count();
        $reservacionesTotales = Reservaciones::count();
        $porcentajeReservacionesMes = $reservacionesTotales > 0 ? ($reservacionesMes / $reservacionesTotales) * 100 : 0;

        return response()->json([
            'status' => 1,
            'data' => [
                'reservaciones_hoy' => $reservacionesHoy,
                'reservaciones_pendientes' => $reservacionesPendientes,
                'usuarios_registrados' => $usuariosRegistrados,
                'espacios_existentes' => $espacios,
                'ingresos_mes' => $ingresos,
                'porcentaje_reservaciones_mes' => $porcentajeReservacionesMes,
            ],
        ], 200);
    }

    // Ingresos por mes (ultimos 12 meses)
    public function ingresosUltimos12Meses()
    {
        $ingresosUltimos12Meses = [];
        for ($i = 0; $i < 12; $i++) {
            $mes = now()->subMonths($i)->format('m');
            $anio = now()->subMonths($i)->format('Y');
            $ingresos = Reservaciones::whereMonth('fecha_reseva', $mes)
                ->whereYear('fecha_reseva', $anio)
                ->sum('total_precio');
            $ingresosUltimos12Meses[] = [
                'mes' => now()->subMonths($i)->format('F'),
                'ingresos' => $ingresos,
            ];
        }
        return response()->json([
            'status' => 1,
            'data' => $ingresosUltimos12Meses,
        ], 200);
    }
    // Ingreso de reservaciones por mes (ultimos 12 meses)
    // Ingreso de registro de usuarios por mes (ultimos 12 meses)

}
