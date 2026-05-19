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
            id_usuario,
            nombre,
            correo,
            rol,
            COALESCE(programa, '') AS programa,
            COALESCE(extension, COALESCE(extension, 'Extensión Facatativá')) AS extension,
            DATE_FORMAT(NOW(), '%Y-%m-%d') AS fecha_actual
        FROM usuarios
        WHERE id_usuario = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $perfil = $stmt->get_result()->fetch_assoc();

    if (!$perfil) {
        api_json(["ok" => false, "mensaje" => "No fue posible cargar el perfil del usuario."], 404);
    }

    api_json([
        "ok" => true,
        "perfil" => $perfil
    ]);
} catch (Throwable $e) {
    api_error($e);
}
