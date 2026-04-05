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
$usuarioId = (int) $usuario->id;

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Método no permitido."
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();

    if (!$conn) {
        throw new Exception("No fue posible conectar con la base de datos.");
    }

    $sql = "SELECT 
                id_solicitud,
                tipo_solicitud,
                grupo_origen,
                grupo_destino,
                materia_origen,
                materia_destino,
                estado,
                fecha_solicitud
            FROM solicitudes
            WHERE usuario_id = ?
            ORDER BY fecha_solicitud DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();

    $result = $stmt->get_result();
    $solicitudes = [];

    while ($row = $result->fetch_assoc()) {
        $solicitudes[] = $row;
    }

    echo json_encode([
        "ok" => true,
        "solicitudes" => $solicitudes
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error del servidor: " . $e->getMessage()
    ]);
}