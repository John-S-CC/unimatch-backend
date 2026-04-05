<?php
error_reporting(0);
ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../vendor/autoload.php";

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$correo = trim($data["correo"] ?? "");
$password = (string) ($data["password"] ?? "");

$db = new Database();
$conn = $db->connect();

$sql = "SELECT * FROM usuarios WHERE correo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["ok" => false, "mensaje" => "Usuario no encontrado"]);
    exit;
}

$user = $result->fetch_assoc();
$claveValida = false;

if (isset($user["password"])) {
    if (password_get_info($user["password"])["algo"] !== null) {
        $claveValida = password_verify($password, $user["password"]);
    } else {
        $claveValida = hash_equals((string) $user["password"], $password);
    }
}

if (!$claveValida) {
    echo json_encode(["ok" => false, "mensaje" => "Contraseña incorrecta"]);
    exit;
}

require_once __DIR__ . "/../configuracion/jwt.php";
$token = JWTConfig::generarToken([
    "id" => $user["id_usuario"],
    "nombre" => $user["nombre"],
    "rol" => $user["rol"]
]);

echo json_encode([
    "ok" => true,
    "token" => $token,
    "usuario" => [
        "id" => $user["id_usuario"],
        "nombre" => $user["nombre"],
        "rol" => $user["rol"]
    ]
]);
