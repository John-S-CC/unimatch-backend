<?php
require_once __DIR__ . "/_common.php";
api_set_common_headers("POST, OPTIONS");
api_handle_preflight();
api_require_method("POST");

require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../middleware/AuthMiddleware.php";

$usuario = AuthMiddleware::verificar();
if (!api_user_is_admin($usuario)) {
    api_json(['ok' => false, 'mensaje' => 'No tienes permisos para actualizar turnos.'], 403);
}

$input = api_read_input();
$idTurno = (int) api_post_or_input($input, 'id_turno', 0);
$estado = strtolower(trim((string) api_post_or_input($input, 'estado', '')));
$validos = ['pendiente', 'resuelta', 'rechazada'];

if ($idTurno <= 0 || !in_array($estado, $validos, true)) {
    api_json(['ok' => false, 'mensaje' => 'Los datos enviados para actualizar el turno no son válidos.'], 422);
}

try {
    $conn = api_connect_db();
    $sql = "UPDATE turnos SET estado = ?, fecha_actualizacion = NOW() WHERE id_turno = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $estado, $idTurno);
    $stmt->execute();

    if ($stmt->affected_rows < 1) {
        api_json(['ok' => false, 'mensaje' => 'No se realizaron cambios sobre el turno.'], 404);
    }

    api_json(['ok' => true, 'mensaje' => 'Estado del turno actualizado correctamente.']);
} catch (Throwable $e) {
    api_error($e);
}
