<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservaciones;
use App\Models\Espacios;
use App\Models\Usuario;
use Illuminate\Support\Carbon as Carbon; //? Libreria para manejar fechas
use App\Http\Controllers\api\FuncionesController as Email; //? Libreria para enviar correos
use Illuminate\Support\Facades\Auth;

class ReservacionesController extends Controller
{
    // Crear reservación
    public function create(Request $request)
    {
        $request->validate([
            'id_user' => 'required|integer',
            'id_espacio' => 'required|integer',
            'fecha_reseva' => 'required|date',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio'
        ]);

        $usuario = Usuario::find($request->id_user);
        if (!$usuario) {
            return response()->json([
                'status' => 0,
                'message' => 'El usuario no existe',
            ], 404);
        }

        $estado = Espacios::find($request->id_espacio); //? Obtener el espacio para verificar su disponibilidad
        if (!$estado || $estado->disponibilidad === 0) {
            return response()->json([
                'status' => 0,
                'message' => 'El espacio no está disponible',
            ], 400);
        }

        $time = Reservaciones::where('id_espacio', $request->id_espacio) // ? Verificar si el espacio ya está reservado
            ->where('fecha_reseva', $request->fecha_reseva)
            ->where('estado', '!=', 'cancelada')
            ->where(function ($query) use ($request) {
                $query->whereBetween('hora_inicio', [$request->hora_inicio, $request->hora_fin])
                    ->orWhereBetween('hora_fin', [$request->hora_inicio, $request->hora_fin]);
            })
            ->exists();

        if ($time) { //? Si ya existe una reservación en ese horario
            return response()->json([
                'status' => 0,
                'message' => 'El espacio ya está reservado en ese horario',
            ], 400);
        }

        $horaInicio = Carbon::createFromFormat('H:i', $request->hora_inicio);
        $horaFin = Carbon::createFromFormat('H:i', $request->hora_fin);
        $horasReservadas = $horaInicio->diffInHours($horaFin);
        $total_precio = $horasReservadas * $estado->precio_hora; //? Calcular el precio total de la reservación

        $reservacion = new Reservaciones();
        $reservacion->id_user = $request->id_user;
        $reservacion->id_espacio = $request->id_espacio;
        $reservacion->fecha_reseva = $request->fecha_reseva;
        $reservacion->hora_inicio = $request->hora_inicio;
        $reservacion->hora_fin = $request->hora_fin;
        //El estado se asigna como pendiente por defecto
        // y se cambia a confirmada al pagar
        // o a cancelada al cancelar
        $reservacion->estado = 'pendiente';
        $reservacion->total_precio = $total_precio;
        $reservacion->save();

        $emails = $this->getEmailsToNotify($request->id_user); //? Obtener los correos de los administradores y del cliente
        Email::sendEmail( //? Enviar correo a los administradores y al cliente
            $emails,
            'Reservación creada',
            "Tu reservación fue creada con éxito.\n\nDetalles:\nEspacio: {$estado->nombre}\nFecha: {$reservacion->fecha_reseva}\nHora: {$reservacion->hora_inicio} - {$reservacion->hora_fin}\nEstado: {$reservacion->estado} de pago \nTotal: $ {$total_precio}"
        );

        return response()->json([
            'status' => 1,
            'message' => 'Reservación creada con éxito',
            'reservacion' => $reservacion,
        ], 201);
    }

    // Cancelar reservación (propias para cliente, todas para admin)
    public function cancel(Request $request, $id)
    {
        // Obtener el usuario autenticado
        $usuario = Auth::user();
        if (!$usuario) {
            return response()->json([
                'status' => 0,
                'message' => 'Usuario no autenticado',
            ], 403);
        }
        if ($usuario->rol === 1) {
            $reservacion = Reservaciones::find($id);
        } else {
            $reservacion = Reservaciones::where('id_user', $usuario->id)
                ->where('id', $id)
                ->first();
        }
        if (!$reservacion) {
            return response()->json([
                'status' => 0,
                'message' => 'Reservación no encontrada',
            ], 404);
        }
        if ($reservacion) {
            $reservacion->estado = 'cancelada';
            $reservacion->save();
            $emails = $this->getEmailsToNotify($reservacion->id_user);
            Email::sendEmail(
                $emails,
                'Reservación cancelada',
                "La reservación con los siguientes detalles ha sido cancelada:\n\n ID de Reservación: {$reservacion->id}\n Espacio: {$reservacion->espacio->nombre}\n Fecha: {$reservacion->fecha_reseva}\n Hora: {$reservacion->hora_inicio} - {$reservacion->hora_fin}\n\n Estado: {$reservacion->estado}\n\n El espacio ahora está disponible para nuevas reservaciones."
            );
            return response()->json([
                'status' => 1,
                'message' => 'Reservación cancelada con éxito',
                'reservacion' => $reservacion,
            ], 200);
        } else {
            return response()->json([
                'status' => 0,
                'message' => 'No se pudo cancelar la reservación',
            ], 400);
        }
    }

    // Listar reservaciones (Propias para cliente, todas para admin)
    public function list(Request $request)
    {
        $usuario = Usuario::find($request->query('id_user'));
        if (!$usuario) {
            return response()->json([
                'status' => 0,
                'message' => 'Usuario no encontrado',
            ], 404);
        }
        if ($usuario->rol === 1) {
            $reservaciones = Reservaciones::with(['espacio', 'usuario'])->get();
        } else {
            $reservaciones = Reservaciones::with(['espacio', 'usuario'])
                ->where('id_user', $request->query('id_user'))
                ->get();
        }
        return response()->json([
            'status' => 1,
            'reservaciones' => $reservaciones,
        ], 200);
    }

    // Pagar reservación (solo admin)
    public function pay(Request $request)
    {
        $usuario = Auth::user();
        if (!$usuario || $usuario->rol !== 1) { // Verifica si el usuario es administrador
            return response()->json([
                'status' => 0,
                'message' => 'No tienes permisos para realizar esta acción',
            ], 403);
        }
        $reservacion = Reservaciones::find($request->id_reservacion);
        if (!$reservacion) {
            return response()->json([
                'status' => 0,
                'message' => 'Reservación no encontrada',
            ], 404);
        }
        $espacio = Espacios::find($reservacion->id_espacio);
        if (!$espacio) {
            return response()->json([
                'status' => 0,
                'message' => 'Espacio no encontrado',
            ], 404);
        }
        $horaInicio = Carbon::parse($reservacion->hora_inicio);
        $horaFin = Carbon::parse($reservacion->hora_fin);
        $horasReservadas = $horaInicio->diffInHours($horaFin);
        $total_precio = $horasReservadas * $espacio->precio_hora;
        $reservacion->total_precio = $total_precio;
        $reservacion->estado = 'confirmada';
        $reservacion->save();
        $emails = $this->getEmailsToNotify($reservacion->id_user);
        Email::sendEmail(
            $emails,
            'Pago de reservación confirmado',
            "Tu reservación fue confirmada.\n\nDetalles:\nEspacio: {$espacio->nombre}\nFecha: {$reservacion->fecha_reseva}\nHora: {$reservacion->hora_inicio} - {$reservacion->hora_fin}\nTotal pagado: $ {$total_precio}"
        );
        return response()->json([
            'status' => 1,
            'message' => 'Reservación pagada con éxito',
            'reservacion' => $reservacion,
        ], 200);
    }

    // Obtener los emails de los usuarios
    private function getEmailsToNotify($id_user)
    {
        $cliente = Usuario::find($id_user); // Obtener el cliente
        $administradores = Usuario::where('rol', 1)->pluck('email')->toArray(); // Obtener los administradores

        if ($cliente && $cliente->email) { // Verificar si el cliente tiene un email
            // Agregar el email del cliente a la lista de correos
            $correos = $administradores; // Iniciar la lista de correos con los administradores
            $correos[] = $cliente->email; // Agregar el email del cliente
            // Enviar el correo al cliente
            return $correos;
        }

        return $administradores; // Si no hay cliente, solo devolver los administradores
    }

    // Obtener la reservación por id
    public function show($id)
    {
        $reservacion = Reservaciones::find($id);
        if (!$reservacion) {
            return response()->json([
                'status' => 0,
                'message' => 'Reservación no encontrada',
            ], 404);
        }
        // Acceder a los datos relacionados
        $espacio = $reservacion->espacio; // Obtener el espacio relacionado
        $usuario = $reservacion->usuario; // Obtener el usuario relacionado
        return response()->json([
            'status' => 1,
            'reservacion' => $reservacion,
        ], 200);
    }
}
