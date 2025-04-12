<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Espacios;

class EspaciosController extends Controller
{
    // Crear espacio
    public function create(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'capacidad' => 'required|integer|min:1',
            'precio_hora' => 'required|numeric|min:1',
             //0 = no disponible, 1 = disponible
            'disponibilidad' => 'required|boolean',
        ]);
        $espacio = new Espacios();
        $espacio->nombre = $request->nombre;
        $espacio->capacidad = $request->capacidad;
        $espacio->precio_hora = $request->precio_hora;
        $espacio->disponibilidad = $request->disponibilidad;
        $espacio->save();
        return response()->json([
            'status' => 1,
            'message' => 'Espacio creado con éxito',
            'espacio' => $espacio,
        ], 201);
    }

    // Listar espacios
    public function index()
    {
        // Obtener todos los espacios
        $espacios = Espacios::all();

        return response()->json([
            'status' => 1,
            'espacios' => $espacios,
        ], 200);
    }

    // Actualizar espacio
    public function update(Request $request, $id)
    {
        $espacio = Espacios::find($id);
        if (!$espacio) {
            return response()->json([
                'status' => 0,
                'message' => 'Espacio no encontrado',
            ], 404);
        }
        $request->validate([
            'nombre' => 'required|string|max:255',
            'capacidad' => 'required|integer|min:1',
            'precio_hora' => 'required|numeric|min:1',
            'disponibilidad' => 'required|boolean',
        ]);
        $espacio->nombre = $request->nombre;
        $espacio->capacidad = $request->capacidad;
        $espacio->precio_hora = $request->precio_hora;
        $espacio->disponibilidad = $request->disponibilidad;
        $espacio->save();
        return response()->json([
            'status' => 1,
            'message' => 'Espacio actualizado con éxito',
            'espacio' => $espacio,
        ], 200);
    }

    // Eliminar espacio
    public function delete($id)
    {
        $espacio = Espacios::find($id);
        if ($espacio) {
            $espacio->delete();
            return response()->json([
                'status' => 1,
                'message' => 'Espacio eliminado',
            ], 200);
        } else {
            return response()->json([
                'status' => 0,
                'message' => 'Espacio no encontrado',
            ], 404);
        }
    }

    // Obtener espacio por ID
    public function show($id)
    {
        $espacio = Espacios::find($id);
        if (!$espacio) {
            return response()->json([
                'status' => 0,
                'message' => 'Espacio no encontrado',
            ], 404);
        }
        return response()->json([
            'status' => 1,
            'espacio' => $espacio,
        ], 200);
    }
}
