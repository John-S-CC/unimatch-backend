<?php
require_once __DIR__ . "/_common.php";
api_set_common_headers("GET, OPTIONS");
api_handle_preflight();
api_require_method("GET");

require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../middleware/AuthMiddleware.php";
require_once __DIR__ . "/../servicios/CalendarioAcademico.php";

$usuario = AuthMiddleware::verificar();
if (!api_user_is_admin($usuario)) {
    api_json(['ok' => false, 'mensaje' => 'No tienes permisos para consultar las solicitudes.'], 403);
}

try {
    $conn = api_connect_db();
    CalendarioAcademico::sincronizarSolicitudesVencidas($conn);

    $sql = "
        SELECT
            s.id_solicitud,
            s.tipo_solicitud,
            s.grupo_origen,
            s.grupo_destino,
            s.materia_origen,
            s.materia_destino,
            s.estado,
            COALESCE(s.canal_resolucion, 'sin_definir') AS canal_resolucion,
            COALESCE(s.detalle_estado, '') AS detalle_estado,
            DATE_FORMAT(s.fecha_solicitud, '%Y-%m-%d %H:%i:%s') AS fecha_solicitud,
            DATE_FORMAT(s.fecha_resolucion, '%Y-%m-%d %H:%i:%s') AS fecha_resolucion,
            u.nombre AS estudiante,
            u.correo,
            COALESCE(u.programa, '') AS programa,
            COALESCE(u.extension, COALESCE(u.extencion, 'Extensión Facatativá')) AS extension,
            mo.nombre AS nombre_materia_origen,
            md.nombre AS nombre_materia_destino
        FROM solicitudes s
        INNER JOIN usuarios u ON u.id_usuario = s.usuario_id
        LEFT JOIN materias mo ON mo.id_materia = s.materia_origen
        LEFT JOIN materias md ON md.id_materia = s.materia_destino
        ORDER BY s.fecha_solicitud DESC, s.id_solicitud DESC
    ";

    $result = $conn->query($sql);
    $solicitudes = [];
    while ($row = $result->fetch_assoc()) {
        $solicitudes[] = $row;
    }

    api_json(['ok' => true, 'solicitudes' => $solicitudes, 'data' => ['total' => count($solicitudes)]]);
} catch (Throwable $e) {
    api_error($e);
}
