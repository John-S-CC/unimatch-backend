<?php
/**
 * @OA\Get(
 *     path="/api/listar_materias.php",
 *     summary="Listar todas las materias",
 *     description="Obtiene el listado completo de materias disponibles",
 *     tags={"Materias"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Lista de materias obtenida correctamente",
 *         @OA\JsonContent(
 *             type="object",
 *             properties={
 *                 @OA\Property(property="ok", type="boolean", example=true),
 *                 @OA\Property(property="materias", type="array", items={"$ref"="#/components/schemas/Materia"})
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
require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../middleware/AuthMiddleware.php";

api_set_common_headers("GET, OPTIONS");
api_handle_preflight();
api_require_method("GET");

AuthMiddleware::verificar();

try {
    $db = new Database();
    $conn = $db->connect();

    $sql = "
        SELECT
            m.id_materia,
            m.nombre AS materia,
            g.id_grupo,
            COALESCE(g.cupos, 0) AS cupos_totales,
            GROUP_CONCAT(
                CONCAT(h.dia, ' ', TIME_FORMAT(h.hora_inicio, '%H:%i'), '-', TIME_FORMAT(h.hora_fin, '%H:%i'))
                ORDER BY h.dia, h.hora_inicio
                SEPARATOR ' / '
            ) AS horario,
            GREATEST(
                COALESCE(g.cupos, 0) - COUNT(DISTINCT CASE WHEN mat.estado = 'activa' THEN mat.id_matricula END),
                0
            ) AS cupos_disponibles
        FROM grupos g
        INNER JOIN materias m ON m.id_materia = g.id_materia
        LEFT JOIN horarios h ON h.id_grupo = g.id_grupo
        LEFT JOIN matriculas mat ON mat.grupo_id = g.id_grupo
        GROUP BY m.id_materia, m.nombre, g.id_grupo, g.cupos
        ORDER BY m.nombre, g.id_grupo
    ";

    $result = $conn->query($sql);
    $materias = [];

    while ($row = $result->fetch_assoc()) {
        $materias[] = $row;
    }

    echo json_encode([
        'ok' => true,
        'materias' => $materias
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'No fue posible procesar la solicitud.'
    ]);
}
