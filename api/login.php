<?php
header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
header("Content-Type: application/json");
require_once "../configuracion/database.php";

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}


$data = json_decode(file_get_contents("php://input"), true);

$correo = $data["correo"] ?? "";
$password = $data["password"] ?? "";

$db = new Database();
$conn = $db->connect();

$sql = "SELECT * FROM usuarios WHERE correo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["ok"=>false, "mensaje"=>"Usuario no encontrado"]);
    exit;
}

$user = $result->fetch_assoc();

// TEMPORAL sin hash
if ($password !== $user["password"]) {
    echo json_encode(["ok"=>false, "mensaje"=>"Contraseña incorrecta"]);
    exit;
}

echo json_encode([
    "ok" => true,
    "usuario" => [
        "id" => $user["id_usuario"],
        "nombre" => $user["nombre"],
        "correo" => $user["correo"],
        "rol" => $user["rol"]
    ]
]);