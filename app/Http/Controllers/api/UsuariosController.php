<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\api\FuncionesController as Email;

class UsuariosController extends Controller
{
    // Registro
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:usuarios,email',
            'password' => 'required|string|min:8',
        ]);
        $usuario = new Usuario();
        $usuario->name = $request->name;
        $usuario->email = $request->email;
        $usuario->password = Hash::make($request->password);
        // Asignar rol por defecto, ya que es un tinyint
        // 0 = usuario, 1 = administrador
        $usuario->rol = 0;
        $usuario->save();
        $token = $usuario->createToken('auth_token')->plainTextToken;
        $email = $this->getEmailsToNotify($usuario->id);
        // Enviar correo de bienvenida al usuario
        Email::sendEmail( //? Enviar correo a los administradores y al cliente
            $email,
            'Bienvenido a nuestra plataforma',
            'Hola ' . $usuario->name . ',Gracias por registrarte en nuestra plataforma. Tu cuenta ha sido creada con éxito.',
            'Bienvenido'
        );
        return response()->json([
            'status' => 1,
            'message' => 'Usuario registrado con éxito',
            'usuario' => $usuario,
            'token' => $token,
        ], 201);
    }

    // Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:8',
        ]);
        $usuario = Usuario::where('email', $request->email)->first();
        if (isset($usuario->id)) {
            if (Hash::check($request->password, $usuario->password)) {
                $token = $usuario->createToken('auth_token')->plainTextToken;
                return response()->json([
                    'status' => 1,
                    'message' => 'Usuario logueado con éxito',
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'email' => $usuario->email,
                    'token' => $token,
                    'rol' => $usuario->rol,
                ], 200);
            } else {
                return response()->json([
                    'status' => 0,
                    'message' => 'Contraseña incorrecta',
                ], 401);
            }
        } else {
            return response()->json([
                'status' => 0,
                'message' => 'Usuario no encontrado',
            ], 404);
        }
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => 1,
            'message' => 'Usuario deslogueado con éxito',
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
}
