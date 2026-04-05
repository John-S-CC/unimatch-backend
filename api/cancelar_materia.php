<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../middleware/AuthMiddleware.php";

$usuario = AuthMiddleware::verificar();
$usuarioId = (int) $usuario->id;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Método no permitido."
    ]);
    exit;
}

$grupoId = isset($_POST["grupo_id"]) ? (int) $_POST["grupo_id"] : 0;

if ($grupoId <= 0) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "El grupo es obligatorio."
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();

    $sql = "UPDATE matriculas
            SET estado = 'cancelada'
            WHERE usuario_id = ? AND grupo_id = ? AND estado = 'activa'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $usuarioId, $grupoId);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "No se encontró una matrícula activa para cancelar."
        ]);
        exit;
    }

    if (file_exists(__DIR__ . "/../eventos/MotorEventos.php")) {
        require_once __DIR__ . "/../eventos/MotorEventos.php";
        if (class_exists("MotorEventos") && method_exists("MotorEventos", "procesarEvento")) {
            MotorEventos::procesarEvento($conn, "cancelacion_materia", [
                "usuario_id" => $usuarioId,
                "grupo_id" => $grupoId
            ]);
        }
    }

    echo json_encode([
        "ok" => true,
        "mensaje" => "Matrícula cancelada correctamente."
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error del servidor: " . $e->getMessage()
    ]);
}
