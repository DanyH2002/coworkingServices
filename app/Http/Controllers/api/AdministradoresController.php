<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Espacios;
use App\Models\Reservaciones;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon as Carbon;

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


    // Crear un clinete y/o un administrador
    public function createUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:usuarios',
            'password' => 'required|string|min:8',
            'rol' => 'required|boolean', // 1 = administrador, 0 = cliente
        ]);

        $usuario = new Usuario();
        $usuario->name = $request->name;
        $usuario->email = $request->email;
        $usuario->password = Hash::make($request->password);
        $usuario->rol = $request->rol;
        $usuario->save();

        return response()->json([
            'status' => 1,
            'message' => 'Usuario creado con éxito',
            'usuario' => $usuario,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::find($id);
        if (!$usuario) {
            return response()->json([
                'status' => 0,
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:usuarios,email,' . $id,
            'password' => 'nullable|string|min:8',
            'rol' => 'required|boolean',
        ]);

        $usuario->name = $request->name;
        $usuario->email = $request->email;
        if ($request->password) {
            $usuario->password = Hash::make($request->password);
        }
        // Si el rol es 1, se asigna el rol de administrador, si no, se asigna el rol de cliente
        // 1 = administrador, 0 = cliente
        $usuario->rol = $request->rol;
        $usuario->save();

        return response()->json([
            'status' => 1,
            'message' => 'Usuario actualizado con éxito',
            'usuario' => $usuario,
        ], 200);
    }

    public function showUser($id)
    {
        $usuario = Usuario::find($id);
        if (!$usuario) {
            return response()->json([
                'status' => 0,
                'message' => 'Usuario no encontrado',
            ], 404);
        }
        return response()->json([
            'status' => 1,
            'usuario' => $usuario,
        ], 200);
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
            ->where('estado', 'confirmada')
            ->whereYear('fecha_reseva', now()->year)
            ->sum('total_precio');

        // Porcentaje de reservaciones del mes
        $reservacionesMes = Reservaciones::whereMonth('fecha_reseva', now()->month)
            ->where('estado', 'confirmada')
            ->whereYear('fecha_reseva', now()->year)
            ->count();
        $reservacionesTotales = Reservaciones::count();
        $porcentajeReservacionesMes = $reservacionesTotales > 0 ? ($reservacionesMes / $reservacionesTotales) * 100 : 0;

        // Incluir las funciones de los ultimos 12 meses
        $ingresosUltimos12Meses = $this->ingresosUltimos12Meses();
        $reservacionesUltimos12Meses = $this->ReservacionesUltimos12Meses();
        $registroUsuariosUltimos12Meses = $this->RegistroUsuariosUltimos12Meses();

        return response()->json([
            'status' => 1,
            'data' => [
                'reservaciones_hoy' => $reservacionesHoy,
                'reservaciones_pendientes' => $reservacionesPendientes,
                'usuarios_registrados' => $usuariosRegistrados,
                'espacios_existentes' => $espacios,
                'ingresos_mes' => $ingresos,
                'porcentaje_reservaciones_mes' => $porcentajeReservacionesMes,
                'ingresos_ultimos_12_meses' => $ingresosUltimos12Meses,
                'reservaciones_ultimos_12_meses' => $reservacionesUltimos12Meses,
                'registro_usuarios_ultimos_12_meses' => $registroUsuariosUltimos12Meses,
            ],
        ], 200);
    }

    // Ingresos por mes (ultimos 12 meses)
    public function ingresosUltimos12Meses()
    {
        $data = Reservaciones::select(
            DB::raw('DATE_FORMAT(fecha_reseva, "%Y-%m") as mes'),
            DB::raw('SUM(total_precio) as total')
        )
            ->where('estado', 'confirmada')
            ->whereBetween('fecha_reseva', [now()->subMonths(11)->startOfMonth(), now()->endOfMonth()])
            ->groupBy('mes')
            ->orderBy('mes', 'asc')
            ->get();
        return response()->json([
            'status' => 1,
            'data' => $this->formatMonthlyData($data),
        ], 200);
    }
    // Ingreso de reservaciones por mes (ultimos 12 meses)
    public function ReservacionesUltimos12Meses() {
        $data = Reservaciones::select(
            DB::raw('DATE_FORMAT(fecha_reseva, "%Y-%m") as mes'),
            DB::raw('COUNT(*) as total')
        )
            ->where('estado', 'confirmada')
            ->whereBetween('fecha_reseva', [now()->subMonths(11)->startOfMonth(), now()->endOfMonth()])
            ->groupBy('mes')
            ->orderBy('mes', 'asc')
            ->get();
        return response()->json([
            'status' => 1,
            'data' => $this->formatMonthlyData($data),
        ], 200);
    }
    // Ingreso de registro de usuarios por mes (ultimos 12 meses)
    public function RegistroUsuariosUltimos12Meses() {
        $data = Usuario::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mes'),
            DB::raw('COUNT(*) as total')
        )
            ->whereBetween('created_at', [now()->subMonths(11)->startOfMonth(), now()->endOfMonth()])
            ->groupBy('mes')
            ->orderBy('mes', 'asc')
            ->get();
        return response()->json([
            'status' => 1,
            'data' => $this->formatMonthlyData($data),
        ], 200);
    }

    // Función auxiliar para rellenar meses vacíos
private function formatMonthlyData($data)
{
    $meses = [];
    $actual = Carbon::now()->startOfMonth()->subMonths(11);
    for ($i = 0; $i < 12; $i++) {
        $label = $actual->format('Y-m');
        $meses[$label] = 0;
        $actual->addMonth();
    }

    foreach ($data as $item) {
        $meses[$item->mes] = $item->total;
    }

    return [
        'labels' => array_keys($meses),
        'data' => array_values($meses),
    ];
    }

}
