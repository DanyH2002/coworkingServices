<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

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

        return response()->json([
            'status' => 1,
            'message' => 'Usuario registrado con éxito',
            'usuario' => $usuario
        ], 201);
    }
    // Actualizar
    public function update(Request $request, $id)
    {
        $usuario = Usuario::where('id', $id)->first();
        if (!$usuario) {
            return response()->json([
                'status' => 0,
                'message' => 'Usuario no encontrado',
            ], 404);
        } else {
            $request->validate([
                'name' => 'required|string',
                'email' => 'required|string|email|unique:usuarios,email'.$id,
                'password' => 'nullable|string|min:8',
            ]);
            $usuario->name = $request->name;
            $usuario->email = $request->email;
            if ($request->password) {
                $usuario->password = Hash::make($request->password);
            }
            $usuario->save();

            return response()->json([
                'status' => 1,
                'message' => 'Usuario actualizado con éxito',
                'usuario' => $usuario,
            ], 200);
        }
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
                    'usuario' => $usuario,
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
}
