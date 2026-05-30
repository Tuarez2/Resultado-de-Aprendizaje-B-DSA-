<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Docentes;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException;

class DocentesApiController extends Controller
{
    // 200 OK
    public function index(Request $request): JsonResponse
    {
        $query = Docentes::query();

        if ($search = $request->query('q')) {
            $query->where('nombres',      'like', "%{$search}%")
                  ->orWhere('apellidos',    'like', "%{$search}%")
                  ->orWhere('especialidad', 'like', "%{$search}%")
                  ->orWhere('correo',       'like', "%{$search}%")
                  ->orWhere('telefono',     'like', "%{$search}%");
        }

        return response()->json([
            'status'  => 200,
            'message' => 'OK',
            'data'    => $query->get(),
        ], 200);
    }

    // 201 Created | 422 Unprocessable | 409 Conflict
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombres'      => 'required|string|max:255',
            'apellidos'    => 'required|string|max:255',
            'especialidad' => 'required|string|max:255',
            'correo'       => 'required|email|unique:docentes,correo',
            'telefono'     => 'required|string|max:50',
        ]);

        try {
            $docente = Docentes::create($data);

            return response()->json([
                'status'  => 201,
                'message' => 'Docente creado correctamente',
                'data'    => $docente,
            ], 201);

        } catch (QueryException $e) {
            return response()->json([
                'status'  => 409,
                'message' => 'Conflicto: ya existe un registro con esos datos',
                'error'   => $e->getMessage(),
            ], 409);
        }
    }

    // 200 OK | 404 Not Found
    public function show(Docentes $docente): JsonResponse
    {
        return response()->json([
            'status'  => 200,
            'message' => 'OK',
            'data'    => $docente,
        ], 200);
    }

    // 200 OK | 404 Not Found | 422 Unprocessable
    public function update(Request $request, Docentes $docente): JsonResponse
    {
        $data = $request->validate([
            'nombres'      => 'sometimes|required|string|max:255',
            'apellidos'    => 'sometimes|required|string|max:255',
            'especialidad' => 'sometimes|required|string|max:255',
            'correo'       => 'sometimes|required|email|unique:docentes,correo,' . $docente->id,
            'telefono'     => 'sometimes|required|string|max:50',
        ]);

        $docente->update($data);

        return response()->json([
            'status'  => 200,
            'message' => 'Docente actualizado correctamente',
            'data'    => $docente,
        ], 200);
    }

    // 200 OK | 404 Not Found
    public function destroy(Docentes $docente): JsonResponse
    {
        $docente->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Docente eliminado correctamente',
        ], 200);
    }
}
