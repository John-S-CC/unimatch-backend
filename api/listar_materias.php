<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../middleware/AuthMiddleware.php";

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
        'mensaje' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
