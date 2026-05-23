<?php
/**
 * @OA\Post(
 *     path="/api/reset_password.php",
 *     summary="Restablecer contraseña",
 *     description="Permite restablecer la contraseña de un usuario mediante token de recuperación",
 *     tags={"Autenticación"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Datos para restablecer contraseña",
 *         @OA\JsonContent(
 *             type="object",
 *             required={"token", "nueva_password"},
 *             properties={
 *                 @OA\Property(property="token", type="string", example="abc123def456"),
 *                 @OA\Property(property="nueva_password", type="string", format="password", example="nuevaContraseña123")
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Contraseña restablecida",
 *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Token inválido o expirado",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . "/_common.php";
require_once __DIR__ . "/../configuracion/database.php";

api_set_common_headers("POST, OPTIONS");
api_handle_preflight();
api_require_method("POST");

try {
    $data = api_read_input();
    $token = trim((string) ($data["token"] ?? ""));
    $password = (string) ($data["password"] ?? "");
    $confirmacion = (string) ($data["confirmacion"] ?? "");

    if ($token === "" || strlen($token) < 40) {
        api_json(["ok" => false, "mensaje" => "El enlace de recuperación no es válido."], 422);
    }

    if ($password !== $confirmacion) {
        api_json(["ok" => false, "mensaje" => "Las contraseñas no coinciden."], 422);
    }

    if (!api_valid_password_policy($password)) {
        api_json([
            "ok" => false,
            "mensaje" => "La contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número."
        ], 422);
    }

    $conn = api_connect_db();
    $tokenHash = hash("sha256", $token);

    $stmt = $conn->prepare("SELECT pr.id_reset, pr.usuario_id, pr.correo FROM password_resets pr INNER JOIN usuarios u ON u.id_usuario = pr.usuario_id WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at > NOW() LIMIT 1");
    $stmt->bind_param("s", $tokenHash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        api_json(["ok" => false, "mensaje" => "El enlace de recuperación expiró o ya fue utilizado."], 400);
    }

    if (!api_email_institutional((string) $row["correo"])) {
        api_json(["ok" => false, "mensaje" => "El correo asociado no es institucional."], 403);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $uid = (int) $row["usuario_id"];
    $resetId = (int) $row["id_reset"];

    $conn->begin_transaction();

    $upd = $conn->prepare("UPDATE usuarios SET password = ? WHERE id_usuario = ?");
    $upd->bind_param("si", $hash, $uid);
    $upd->execute();

    $mark = $conn->prepare("UPDATE password_resets SET used_at = NOW(), used_ip = ? WHERE id_reset = ?");
    $ip = SecurityConfig::clientIp();
    $mark->bind_param("si", $ip, $resetId);
    $mark->execute();

    $conn->commit();

    api_json(["ok" => true, "mensaje" => "Contraseña actualizada correctamente. Ya puedes iniciar sesión."]);
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        @$conn->rollback();
    }
    api_error($e, "No fue posible actualizar la contraseña.");
}
