<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . "/_common.php";
require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../servicios/Mailer.php";

api_set_common_headers("POST, OPTIONS");
api_handle_preflight();
api_require_method("POST");

try {
    $data = api_read_input();
    $correo = strtolower(trim((string) ($data["correo"] ?? "")));

    if (!api_email_institutional($correo)) {
        api_json([
            "ok" => false,
            "mensaje" => "El correo debe terminar en @unimatch.edu.co."
        ], 422);
    }

    $conn = api_connect_db();
    $stmt = $conn->prepare("SELECT id_usuario, nombre, correo FROM usuarios WHERE correo = ? LIMIT 1");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Respuesta genérica para no revelar si el correo existe.
    $respuesta = [
        "ok" => true,
        "mensaje" => "Si el correo está registrado, enviaremos un enlace de recuperación."
    ];

    if (!$user) {
        api_json($respuesta);
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash("sha256", $token);
    $minutos = (int) (getenv("UNIMATCH_PASSWORD_RESET_MINUTES") ?: 60);
    $expiresAt = date("Y-m-d H:i:s", time() + ($minutos * 60));

    $conn->begin_transaction();

    $delete = $conn->prepare("DELETE FROM password_resets WHERE usuario_id = ? OR expires_at < NOW() OR used_at IS NOT NULL");
    $uid = (int) $user["id_usuario"];
    $delete->bind_param("i", $uid);
    $delete->execute();

    $insert = $conn->prepare("INSERT INTO password_resets (usuario_id, correo, token_hash, expires_at, created_ip) VALUES (?, ?, ?, ?, ?)");
    $ip = SecurityConfig::clientIp();
    $insert->bind_param("issss", $uid, $correo, $tokenHash, $expiresAt, $ip);
    $insert->execute();

    $conn->commit();

    $frontUrl = rtrim((string) (getenv("UNIMATCH_FRONTEND_PUBLIC_URL") ?: "http://localhost:5500"), "/");
    $resetLink = $frontUrl . "/recuperar_password.html?token=" . urlencode($token);

    $nombre = htmlspecialchars((string) ($user["nombre"] ?? "Usuario"), ENT_QUOTES, "UTF-8");
    $linkSeguro = htmlspecialchars($resetLink, ENT_QUOTES, "UTF-8");

    $html = "<p>Hola {$nombre},</p>"
        . "<p>Recibimos una solicitud para recuperar tu contraseña de UniMatch.</p>"
        . "<p><a href=\"{$linkSeguro}\">Restablecer contraseña</a></p>"
        . "<p>Este enlace vence en {$minutos} minutos. Si no solicitaste este cambio, puedes ignorar este correo.</p>";

    Mailer::enviar($correo, "Recuperación de contraseña - UniMatch", $html);

    api_json($respuesta);
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        @$conn->rollback();
    }
    api_error($e, "No fue posible procesar la recuperación de contraseña.");
}
