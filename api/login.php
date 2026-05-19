<?php

error_reporting(0);
ini_set('display_errors', 0);
 require_once __DIR__ . "/_common.php";
 require_once __DIR__ . "/../configuracion/database.php";
 //require_once __DIR__ . "/../configuracion/jwt.php";



api_set_common_headers("POST, OPTIONS");
api_handle_preflight();
api_require_method("POST");

function login_rate_file(string $key): string {
    $dir = __DIR__ . "/../logs/login_attempts";
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir . "/" . hash("sha256", $key) . ".json";
}

function login_is_locked(string $key): bool {
    $file = login_rate_file($key);
    if (!is_readable($file)) {
        return false;
    }
    $data = json_decode((string) file_get_contents($file), true) ?: [];
    $lockUntil = (int) ($data["lock_until"] ?? 0);
    return $lockUntil > time();
}

function login_register_failure(string $key): void {
    $file = login_rate_file($key);
    $data = is_readable($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
    $attempts = (int) ($data["attempts"] ?? 0) + 1;
    $max = (int) (getenv("UNIMATCH_LOGIN_MAX_ATTEMPTS") ?: 5);
    $lockSeconds = (int) (getenv("UNIMATCH_LOGIN_LOCK_SECONDS") ?: 900);

    $data = [
        "attempts" => $attempts,
        "updated_at" => time(),
        "lock_until" => $attempts >= $max ? time() + $lockSeconds : 0
    ];
    @file_put_contents($file, json_encode($data));
}

function login_clear_failures(string $key): void {
    $file = login_rate_file($key);
    if (is_file($file)) {
        @unlink($file);
    }
}

try {
    $data = api_read_input();
    $correo = trim((string) ($data["correo"] ?? ""));
    $password = (string) ($data["password"] ?? "");

    if ($correo === "" || $password === "") {
        api_json(["ok" => false, "mensaje" => "Credenciales inválidas."], 401);
    }

    if (!api_email_institutional($correo)) {
        api_json(["ok" => false, "mensaje" => "Solo se permite el acceso con correos institucionales @unimatch.edu.co."], 403);
    }

    $rateKey = strtolower($correo) . "|" . SecurityConfig::clientIp();
    if (login_is_locked($rateKey)) {
        api_json(["ok" => false, "mensaje" => "Demasiados intentos fallidos. Intente nuevamente más tarde."], 429);
    }

    $conn = api_connect_db();
    $stmt = $conn->prepare("SELECT id_usuario, nombre, correo, password, rol, programa, extension, extencion FROM usuarios WHERE correo = ? LIMIT 1");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $claveValida = false;
    if ($user && isset($user["password"])) {
        if (password_get_info($user["password"])["algo"] !== null) {
            $claveValida = password_verify($password, $user["password"]);
        } else {
            // Compatibilidad temporal con datos antiguos. Recomendado migrar a password_hash().
            $claveValida = hash_equals((string) $user["password"], $password);
        }
    }

    if (!$user || !$claveValida) {
        login_register_failure($rateKey);
        api_json(["ok" => false, "mensaje" => "Credenciales inválidas."], 401);
    }

    login_clear_failures($rateKey);

    $token = JWTConfig::generarToken([
        "id" => $user["id_usuario"],
        "nombre" => $user["nombre"],
        "rol" => $user["rol"]
    ]);

    $extension = $user["extension"] ?? ($user["extencion"] ?? "Extensión Facatativá");
    $programa = $user["programa"] ?? "";

    api_json([
        "ok" => true,
        "token" => $token,
        "usuario" => [
            "id" => (int) $user["id_usuario"],
            "nombre" => $user["nombre"],
            "correo" => $user["correo"] ?? $correo,
            "rol" => $user["rol"],
            "programa" => $programa,
            "extension" => $extension
        ]
    ]);
} catch (Throwable $e) {
    api_error($e, "No fue posible iniciar sesión.");
}
