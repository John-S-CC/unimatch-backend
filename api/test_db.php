<?php

require_once __DIR__ . "/../configuracion/database.php";

header("Content-Type: application/json");

try {

    $db = new Database();
    $conn = $db->connect();

    echo json_encode([
        "ok" => true,
        "mensaje" => "Conexión exitosa a la base de datos"
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "error" => $e->getMessage()
    ]);
}