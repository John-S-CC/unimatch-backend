<?php
/**
 * @OA\Get(
 *     path="/api/listar_turnos.php",
 *     summary="Listar todos los turnos",
 *     description="Obtiene el listado completo de turnos disponibles con información de horarios y salones",
 *     tags={"Turnos"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Lista de turnos obtenida correctamente",
 *         @OA\JsonContent(
 *             type="object",
 *             properties={
 *                 @OA\Property(property="ok", type="boolean", example=true),
 *                 @OA\Property(property="turnos", type="array", items={"$ref"="#/components/schemas/Turno"})
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="No autorizado",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
require_once __DIR__ . "/_common.php";
api_set_common_headers("GET, OPTIONS");
api_handle_preflight();
api_require_method("GET");

require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../middleware/AuthMiddleware.php";

$usuario = AuthMiddleware::verificar();
$usuarioId = (int) ($usuario->id ?? 0);
$esAdmin = api_user_is_admin($usuario);

try {
    $conn = api_connect_db();

    if ($esAdmin) {
        $sql = "
            SELECT
                t.id_turno,
                t.codigo_turno,
                t.consecutivo_dia,
                t.nombre_estudiante,
                t.correo_estudiante,
                t.programa,
                t.extension,
                t.motivo,
                t.estado,
                DATE_FORMAT(t.fecha_turno, '%Y-%m-%d %H:%i:%s') AS fecha_turno,
                DATE_FORMAT(t.fecha_actualizacion, '%Y-%m-%d %H:%i:%s') AS fecha_actualizacion
            FROM turnos t
            ORDER BY t.fecha_turno ASC, t.id_turno ASC
        ";
        $stmt = $conn->prepare($sql);
    } else {
        $sql = "
            SELECT
                t.id_turno,
                t.codigo_turno,
                t.consecutivo_dia,
                t.nombre_estudiante,
                t.correo_estudiante,
                t.programa,
                t.extension,
                t.motivo,
                t.estado,
                DATE_FORMAT(t.fecha_turno, '%Y-%m-%d %H:%i:%s') AS fecha_turno,
                DATE_FORMAT(t.fecha_actualizacion, '%Y-%m-%d %H:%i:%s') AS fecha_actualizacion
            FROM turnos t
            WHERE t.usuario_id = ?
            ORDER BY t.fecha_turno DESC, t.id_turno DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuarioId);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $turnos = [];
    $estados = [
        'pendiente' => 0,
        'resuelta' => 0,
        'rechazada' => 0
    ];

    while ($row = $result->fetch_assoc()) {
        $turnos[] = $row;
        $estado = strtolower((string) ($row['estado'] ?? ''));
        if (isset($estados[$estado])) {
            $estados[$estado] += 1;
        }
    }

    api_json([
        'ok' => true,
        'turnos' => $turnos,
        'data' => [
            'total' => count($turnos),
            'estados' => $estados,
            'scope' => $esAdmin ? 'admin' : 'student'
        ]
    ]);
} catch (Throwable $e) {
    api_error($e);
}
