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

    $sqlInscritas = "
        SELECT
            m.id_matricula,
            ma.id_materia,
            ma.nombre AS materia,
            g.id_grupo,
            CONCAT(
                ma.nombre, ' - Grupo ', g.id_grupo, ' - ',
                GROUP_CONCAT(CONCAT(h.dia, ' ', h.hora_inicio, '-', h.hora_fin) SEPARATOR ' / ')
            ) AS etiqueta
        FROM matriculas m
        INNER JOIN grupos g ON g.id_grupo = m.grupo_id
        INNER JOIN materias ma ON ma.id_materia = g.id_materia
        LEFT JOIN horarios h ON h.id_grupo = g.id_grupo
        WHERE m.usuario_id = ?
          AND m.estado = 'activa'
        GROUP BY m.id_matricula, ma.id_materia, ma.nombre, g.id_grupo
        ORDER BY ma.nombre, g.id_grupo
    ";

    $stmtInscritas = $conn->prepare($sqlInscritas);
    $stmtInscritas->bind_param("i", $usuarioId);
    $stmtInscritas->execute();
    $resInscritas = $stmtInscritas->get_result();

    $inscritas = [];
    while ($row = $resInscritas->fetch_assoc()) {
        $inscritas[] = $row;
    }

    $sqlDisponibles = "
        SELECT
            ma.id_materia,
            ma.nombre AS materia,
            g.id_grupo,
            CONCAT(
                ma.nombre, ' - Grupo ', g.id_grupo, ' - ',
                GROUP_CONCAT(CONCAT(h.dia, ' ', h.hora_inicio, '-', h.hora_fin) SEPARATOR ' / '),
                ' - Cupos: ', 
                (g.cupos - COUNT(DISTINCT mt.id_matricula))
            ) AS etiqueta,
            (g.cupos - COUNT(DISTINCT mt.id_matricula)) AS cupos_disponibles
        FROM grupos g
        INNER JOIN materias ma ON ma.id_materia = g.id_materia
        LEFT JOIN horarios h ON h.id_grupo = g.id_grupo
        LEFT JOIN matriculas mt 
            ON mt.grupo_id = g.id_grupo
           AND mt.estado = 'activa'
        GROUP BY ma.id_materia, ma.nombre, g.id_grupo, g.cupos
        HAVING cupos_disponibles > 0
        ORDER BY ma.nombre, g.id_grupo
    ";

    $resDisponibles = $conn->query($sqlDisponibles);

    $disponibles = [];
    while ($row = $resDisponibles->fetch_assoc()) {
        $disponibles[] = $row;
    }

    echo json_encode([
        "ok" => true,
        "inscritas" => $inscritas,
        "disponibles" => $disponibles
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error del servidor: " . $e->getMessage()
    ]);
}