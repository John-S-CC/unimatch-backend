<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once "../configuracion/database.php";
require_once "../middleware/AuthMiddleware.php";
require_once "../motorPermutas.php";

$usuario = AuthMiddleware::verificar();

try {
    $db = new Database();
    $conn = $db->connect();

    if (!$conn) {
        throw new Exception("No fue posible conectar con la base de datos.");
    }

    $procesadas = MotorPermutas::procesar($conn);

    echo json_encode([
        "ok" => true,
        "mensaje" => "Motor ejecutado correctamente.",
        "procesadas" => $procesadas
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error del servidor: " . $e->getMessage()
    ]);
}