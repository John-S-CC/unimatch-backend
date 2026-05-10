<?php
require_once __DIR__ . "/_common.php";
api_set_common_headers("GET, OPTIONS");
api_handle_preflight();
api_require_method("GET");

require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../middleware/AuthMiddleware.php";

$usuario = AuthMiddleware::verificar();
$usuarioId = (int) ($usuario->id ?? 0);
$esAdmin = api_user_is_admin($usuario);
$idTurno = (int) ($_GET['id_turno'] ?? 0);

if ($idTurno <= 0) {
    api_json(['ok' => false, 'mensaje' => 'El turno solicitado no es válido.'], 422);
}

try {
    $conn = api_connect_db();
    if ($esAdmin) {
        $sql = "
            SELECT
                t.*,
                DATE_FORMAT(t.fecha_turno, '%Y-%m-%d %H:%i:%s') AS fecha_turno_formateada,
                DATE_FORMAT(t.fecha_actualizacion, '%Y-%m-%d %H:%i:%s') AS fecha_actualizacion_formateada
            FROM turnos t
            WHERE t.id_turno = ?
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idTurno);
    } else {
        $sql = "
            SELECT
                t.*,
                DATE_FORMAT(t.fecha_turno, '%Y-%m-%d %H:%i:%s') AS fecha_turno_formateada,
                DATE_FORMAT(t.fecha_actualizacion, '%Y-%m-%d %H:%i:%s') AS fecha_actualizacion_formateada
            FROM turnos t
            WHERE t.id_turno = ? AND t.usuario_id = ?
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $idTurno, $usuarioId);
    }

    $stmt->execute();
    $turno = $stmt->get_result()->fetch_assoc();
    if (!$turno) {
        api_json(['ok' => false, 'mensaje' => 'No se encontró el turno solicitado.'], 404);
    }

    api_json(['ok' => true, 'turno' => $turno]);
} catch (Throwable $e) {
    api_error($e);
}
