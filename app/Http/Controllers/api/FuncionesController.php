<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class FuncionesController extends Controller
{
    // Enviar correo de confirmación
    public static function sendEmail($to, $subject, $content)
    {
        if (!is_array($to)) { // Verifica si $to es un array
            // Si no es un array, lo convierte a uno
            $to = [$to];
        }

        Mail::raw($content, function ($message) use ($to, $subject) { // Usa la variable $to
            // Configura el remitente
            $message->to($to)->subject($subject);
        });
    }
}
