<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once "../configuracion/database.php";
require_once "../middleware/AuthMiddleware.php";

$usuario = AuthMiddleware::verificar();
$usuarioId = (int)$usuario->id;

try {
    $db = new Database();
    $conn = $db->connect();

    if (!$conn) {
        throw new Exception("No fue posible conectar con la base de datos.");
    }

    $sql = "
        SELECT
            m.id_matricula,
            m.fecha_matricula,
            m.estado,
            ma.id_materia,
            ma.nombre AS materia,
            g.id_grupo,
            g.cupos,
            GROUP_CONCAT(
                CONCAT(h.dia, ' ', TIME_FORMAT(h.hora_inicio, '%H:%i'), '-', TIME_FORMAT(h.hora_fin, '%H:%i'))
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

    echo json_encode([
        "ok" => true,
        "materias" => $materias
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error del servidor: " . $e->getMessage()
    ]);
}