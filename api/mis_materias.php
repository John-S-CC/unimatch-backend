<?php
require_once __DIR__ . "/_common.php";
api_set_common_headers("GET, OPTIONS");
api_handle_preflight();
api_require_method("GET");

require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../middleware/AuthMiddleware.php";

$usuario = AuthMiddleware::verificar();
$usuarioId = (int) ($usuario->id ?? 0);

try {
    $conn = api_connect_db();

    $sql = "
        SELECT
            m.id_matricula,
            DATE_FORMAT(m.fecha_matricula, '%Y-%m-%d %H:%i:%s') AS fecha_matricula,
            m.estado,
            ma.id_materia,
            ma.nombre AS materia,
            g.id_grupo,
            g.cupos,
            GROUP_CONCAT(
                DISTINCT CONCAT(h.dia, ' ', TIME_FORMAT(h.hora_inicio, '%H:%i'), '-', TIME_FORMAT(h.hora_fin, '%H:%i'))
                ORDER BY h.dia, h.hora_inicio
                SEPARATOR ' / '
            ) AS horario
        FROM matriculas m
        INNER JOIN grupos g ON g.id_grupo = m.grupo_id
        INNER JOIN materias ma ON ma.id_materia = g.id_materia
        LEFT JOIN horarios h ON h.id_grupo = g.id_grupo
        WHERE m.usuario_id = ?
          AND m.estado = 'activa'
        GROUP BY
            m.id_matricula,
            m.fecha_matricula,
            m.estado,
            ma.id_materia,
            ma.nombre,
            g.id_grupo,
            g.cupos
        ORDER BY ma.nombre, g.id_grupo
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $result = $stmt->get_result();

    $materias = [];
    while ($row = $result->fetch_assoc()) {
        $materias[] = $row;
    }

    api_json([
        "ok" => true,
        "mensaje" => count($materias) ? "Materias cargadas correctamente." : "No tienes materias activas.",
        "materias" => $materias,
        "data" => ["total" => count($materias)]
    ]);
} catch (Throwable $e) {
    api_json([
        "ok" => false,
        "mensaje" => "Error del servidor: " . $e->getMessage()
    ], 500);
}
