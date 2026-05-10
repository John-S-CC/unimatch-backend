<?php
require_once __DIR__ . "/_common.php";
require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../middleware/AuthMiddleware.php";
require_once __DIR__ . "/../servicios/MotorPermutas.php";

api_set_common_headers("POST, OPTIONS");
api_handle_preflight();
api_require_method("POST");

$usuario = AuthMiddleware::verificar();
if (!api_user_is_admin($usuario)) {
    api_json(['ok' => false, 'mensaje' => 'No autorizado.'], 403);
}

try {
    $conn = api_connect_db();
    $resultado = MotorPermutas::procesar($conn);

    api_json([
        'ok' => true,
        'mensaje' => 'Motor ejecutado correctamente.',
        'resultado' => $resultado
    ]);
} catch (Throwable $e) {
    api_error($e, 'No fue posible ejecutar el motor de permutas.');
}
