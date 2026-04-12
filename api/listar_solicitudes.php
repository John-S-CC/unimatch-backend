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
            s.id_solicitud,
            s.tipo_solicitud,
            s.grupo_origen,
            s.grupo_destino,
            s.materia_origen,
            s.materia_destino,
            s.estado,
            DATE_FORMAT(s.fecha_solicitud, '%Y-%m-%d %H:%i:%s') AS fecha_solicitud,
            mo.nombre AS nombre_materia_origen,
            md.nombre AS nombre_materia_destino
        FROM solicitudes s
        LEFT JOIN materias mo ON mo.id_materia = s.materia_origen
        LEFT JOIN materias md ON md.id_materia = s.materia_destino
        WHERE s.usuario_id = ?
        ORDER BY s.fecha_solicitud DESC, s.id_solicitud DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();

    $result = $stmt->get_result();
    $solicitudes = [];

    while ($row = $result->fetch_assoc()) {
        $solicitudes[] = $row;
    }

    api_json([
        "ok" => true,
        "mensaje" => count($solicitudes) ? "Solicitudes cargadas correctamente." : "No tienes solicitudes registradas.",
        "solicitudes" => $solicitudes,
        "data" => ["total" => count($solicitudes)]
    ]);
} catch (Throwable $e) {
    api_json([
        "ok" => false,
        "mensaje" => "Error del servidor: " . $e->getMessage()
    ], 500);
}
