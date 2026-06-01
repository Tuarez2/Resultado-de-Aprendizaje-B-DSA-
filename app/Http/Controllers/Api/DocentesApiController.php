<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Docentes;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "API Resultado de Aprendizaje B - DSA",
    description: "Documentación de la API de gestión académica, incluyendo el CRUD de docentes.",
    contact: new OA\Contact(email: "leonardo.velez@email.com")
)]
#[OA\Server(
    url: "http://127.0.0.1:8000",
    description: "Servidor Local de Desarrollo"
)]
#[OA\Schema(
    schema: "Docente",
    title: "Docente",
    description: "Modelo de Docente",
    properties: [
        new OA\Property(property: "id", type: "integer", readOnly: true, example: 1),
        new OA\Property(property: "nombres", type: "string", example: "Leonardo"),
        new OA\Property(property: "apellidos", type: "string", example: "Velez"),
        new OA\Property(property: "especialidad", type: "string", example: "Base de datos"),
        new OA\Property(property: "correo", type: "string", format: "email", example: "leonardo.velez@email.com"),
        new OA\Property(property: "telefono", type: "string", example: "0999999999"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", readOnly: true, example: "2026-05-29T23:25:04.000000Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", readOnly: true, example: "2026-05-29T23:26:50.000000Z")
    ]
)]
class DocentesApiController extends Controller
{
    #[OA\Get(
        path: "/api/docentes",
        operationId: "getDocentesList",
        tags: ["Docentes"],
        summary: "Obtener lista de docentes",
        description: "Retorna una lista con todos los docentes registrados, con soporte para búsqueda mediante el parámetro 'q'.",
        parameters: [
            new OA\Parameter(
                name: "q",
                description: "Término de búsqueda opcional (nombre, apellido, especialidad, correo o teléfono)",
                required: false,
                in: "query",
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Operación exitosa",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "integer", example: 200),
                        new OA\Property(property: "message", type: "string", example: "OK"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/Docente")
                        )
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'nullable|string|min:1|max:100'
        ]);

        $query = Docentes::query();

        if ($search = $request->query('q')) {
            $query->where('nombres', 'like', "%{$search}%")
                ->orWhere('apellidos', 'like', "%{$search}%")
                ->orWhere('especialidad', 'like', "%{$search}%")
                ->orWhere('correo', 'like', "%{$search}%")
                ->orWhere('telefono', 'like', "%{$search}%");
        }

        return response()->json([
            'status' => 200,
            'message' => 'OK',
            'data' => $query->get(),
        ], 200);
    }
    #[OA\Post(
        path: "/api/docentes",
        operationId: "storeDocente",
        tags: ["Docentes"],
        summary: "Crear un nuevo docente",
        description: "Valida y crea un docente en la base de datos.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nombres", "apellidos", "especialidad", "correo", "telefono"],
                properties: [
                    new OA\Property(property: "nombres", type: "string", example: "Leonardo"),
                    new OA\Property(property: "apellidos", type: "string", example: "Velez"),
                    new OA\Property(property: "especialidad", type: "string", example: "Base de datos"),
                    new OA\Property(property: "correo", type: "string", format: "email", example: "leonardo.velez@email.com"),
                    new OA\Property(property: "telefono", type: "string", example: "0999999999")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Docente creado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "integer", example: 201),
                        new OA\Property(property: "message", type: "string", example: "Docente creado correctamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Docente")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Datos inválidos / Errores de validación",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "error"),
                        new OA\Property(property: "code", type: "integer", example: 422),
                        new OA\Property(property: "message", type: "string", example: "Los datos proporcionados no son válidos."),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: "Conflicto / Correo duplicado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "integer", example: 409),
                        new OA\Property(property: "message", type: "string", example: "Conflicto: ya existe un registro con esos datos"),
                        new OA\Property(property: "error", type: "string")
                    ]
                )
            )
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombres' => [
                'required', 'string', 'min:3', 'max:100', 'regex:/^[\pL\s]+$/u'
            ],

            'apellidos' => [
                'required', 'string', 'min:3', 'max:100', 'regex:/^[\pL\s]+$/u'
            ],

            'especialidad' => [
                'required', 'string', 'min:3', 'max:100'
            ],

            'correo' => [
                'required', 'email', 'max:255', 'unique:docentes,correo'
            ],

            'telefono' => [
                'required', 'digits:10'
            ],
        ], [
            'nombres.required' => 'El nombre es obligatorio.',
            'nombres.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombres.regex' => 'El nombre solo puede contener letras y espacios.',

            'apellidos.required' => 'El apellido es obligatorio.',
            'apellidos.min' => 'El apellido debe tener al menos 3 caracteres.',
            'apellidos.regex' => 'El apellido solo puede contener letras y espacios.',

            'especialidad.required' => 'La especialidad es obligatoria.',
            'especialidad.min' => 'La especialidad debe tener al menos 3 caracteres.',

            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'Debe ingresar un correo válido.',
            'correo.unique' => 'Este correo ya está registrado.',

            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.digits' => 'El teléfono debe contener exactamente 10 dígitos.'
        ]);

        try {
            $docente = Docentes::create($data);

            return response()->json([
                'status' => 201,
                'message' => 'Docente creado correctamente',
                'data' => $docente,
            ], 201);

        } catch (QueryException $e) {
            return response()->json([
                'status' => 409,
                'message' => 'Conflicto: ya existe un registro con esos datos'
            ], 409);
        }
    }
    #[OA\Get(
        path: "/api/docentes/{id}",
        operationId: "getDocenteById",
        tags: ["Docentes"],
        summary: "Obtener detalle de un docente",
        description: "Retorna la información detallada de un docente específico por su ID.",
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID del docente",
                required: true,
                in: "path",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Operación exitosa",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "integer", example: 200),
                        new OA\Property(property: "message", type: "string", example: "OK"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Docente")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Docente no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "error"),
                        new OA\Property(property: "code", type: "integer", example: 404),
                        new OA\Property(property: "message", type: "string", example: "Recurso no encontrado.")
                    ]
                )
            )
        ]
    )]
    public function show(Docentes $docente): JsonResponse
    {
        return response()->json([
            'status'  => 200,
            'message' => 'OK',
            'data'    => $docente,
        ], 200);
    }

    #[OA\Put(
        path: "/api/docentes/{id}",
        operationId: "updateDocente",
        tags: ["Docentes"],
        summary: "Actualizar un docente existente",
        description: "Valida y actualiza los campos de un docente específico por su ID.",
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID del docente",
                required: true,
                in: "path",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nombres", type: "string", example: "Leonardo"),
                    new OA\Property(property: "apellidos", type: "string", example: "Velez"),
                    new OA\Property(property: "especialidad", type: "string", example: "Inteligencia Artificial"),
                    new OA\Property(property: "correo", type: "string", format: "email", example: "leonardo.velez@email.com"),
                    new OA\Property(property: "telefono", type: "string", example: "0888888888")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Docente actualizado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "integer", example: 200),
                        new OA\Property(property: "message", type: "string", example: "Docente actualizado correctamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Docente")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Docente no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "error"),
                        new OA\Property(property: "code", type: "integer", example: 404),
                        new OA\Property(property: "message", type: "string", example: "Recurso no encontrado.")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Datos inválidos / Errores de validación",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "error"),
                        new OA\Property(property: "code", type: "integer", example: 422),
                        new OA\Property(property: "message", type: "string", example: "Los datos proporcionados no son válidos."),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            )
        ]
    )]
    public function update(Request $request, Docentes $docente): JsonResponse
    {
        $data = $request->validate([
            'nombres' => [
                'sometimes', 'required', 'string', 'min:3', 'max:100', 'regex:/^[\pL\s]+$/u'
            ],

            'apellidos' => [
                'sometimes', 'required', 'string', 'min:3', 'max:100', 'regex:/^[\pL\s]+$/u'
            ],

            'especialidad' => [
                'sometimes', 'required', 'string', 'min:3','max:100'
            ],

            'correo' => [
                'sometimes', 'required', 'email', 'max:255', 'unique:docentes,correo,' . $docente->id
            ],

            'telefono' => [
                'sometimes', 'required', 'digits:10'
            ],
        ], [
            'nombres.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombres.regex' => 'El nombre solo puede contener letras y espacios.',

            'apellidos.min' => 'El apellido debe tener al menos 3 caracteres.',
            'apellidos.regex' => 'El apellido solo puede contener letras y espacios.',

            'especialidad.min' => 'La especialidad debe tener al menos 3 caracteres.',

            'correo.email' => 'Debe ingresar un correo válido.',
            'correo.unique' => 'Este correo ya está registrado.',

            'telefono.digits' => 'El teléfono debe contener exactamente 10 dígitos.'
        ]);

        $docente->update($data);

        return response()->json([
            'status' => 200,
            'message' => 'Docente actualizado correctamente',
            'data' => $docente,
        ], 200);
    }
    #[OA\Delete(
        path: "/api/docentes/{id}",
        operationId: "deleteDocente",
        tags: ["Docentes"],
        summary: "Eliminar un docente",
        description: "Elimina el registro de un docente específico de la base de datos.",
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID del docente",
                required: true,
                in: "path",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Docente eliminado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "integer", example: 200),
                        new OA\Property(property: "message", type: "string", example: "Docente eliminado correctamente")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Docente no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "error"),
                        new OA\Property(property: "code", type: "integer", example: 404),
                        new OA\Property(property: "message", type: "string", example: "Recurso no encontrado.")
                    ]
                )
            )
        ]
    )]
    public function destroy(Docentes $docente): JsonResponse
    {
        $docente->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Docente eliminado correctamente',
        ], 200);
    }
}
