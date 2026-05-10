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

    $sqlInscritas = "
        SELECT
            m.id_matricula,
            ma.id_materia,
            ma.nombre AS materia,
            g.id_grupo,
            CONCAT(
                ma.nombre, ' - Grupo ', g.id_grupo, ' - ',
                COALESCE(GROUP_CONCAT(DISTINCT CONCAT(h.dia, ' ', TIME_FORMAT(h.hora_inicio, '%H:%i'), '-', TIME_FORMAT(h.hora_fin, '%H:%i')) ORDER BY h.dia, h.hora_inicio SEPARATOR ' / '), 'Sin horario')
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
                COALESCE(GROUP_CONCAT(DISTINCT CONCAT(h.dia, ' ', TIME_FORMAT(h.hora_inicio, '%H:%i'), '-', TIME_FORMAT(h.hora_fin, '%H:%i')) ORDER BY h.dia, h.hora_inicio SEPARATOR ' / '), 'Sin horario'),
                ' - Cupos: ',
                GREATEST(g.cupos - COUNT(DISTINCT mt.id_matricula), 0)
            ) AS etiqueta,
            GREATEST(g.cupos - COUNT(DISTINCT mt.id_matricula), 0) AS cupos_disponibles
        FROM grupos g
        INNER JOIN materias ma ON ma.id_materia = g.id_materia
        LEFT JOIN horarios h ON h.id_grupo = g.id_grupo
        LEFT JOIN matriculas mt
            ON mt.grupo_id = g.id_grupo
           AND mt.estado = 'activa'
        WHERE NOT EXISTS (
            SELECT 1
            FROM matriculas mu
            WHERE mu.usuario_id = ?
              AND mu.grupo_id = g.id_grupo
              AND mu.estado = 'activa'
        )
        GROUP BY ma.id_materia, ma.nombre, g.id_grupo, g.cupos
        ORDER BY ma.nombre, g.id_grupo
    ";

    $stmtDisponibles = $conn->prepare($sqlDisponibles);
    $stmtDisponibles->bind_param("i", $usuarioId);
    $stmtDisponibles->execute();
    $resDisponibles = $stmtDisponibles->get_result();

    $disponibles = [];
    while ($row = $resDisponibles->fetch_assoc()) {
        $disponibles[] = $row;
    }

    api_json([
        "ok" => true,
        "inscritas" => $inscritas,
        "disponibles" => $disponibles,
        "data" => [
            "inscritas_total" => count($inscritas),
            "disponibles_total" => count($disponibles)
        ]
    ]);
} catch (Throwable $e) {
    api_json([
        "ok" => false,
        "mensaje" => "No fue posible procesar la solicitud."
    ], 500);
}
