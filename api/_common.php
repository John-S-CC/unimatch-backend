<?php
require_once __DIR__ . "/../configuracion/security.php";

if (!function_exists("api_set_common_headers")) {    

    function api_set_common_headers(string $methods = "GET, POST, OPTIONS"): void {

    header_remove("Access-Control-Allow-Origin");
    header_remove("Access-Control-Allow-Headers");
    header_remove("Access-Control-Allow-Methods");
    header_remove("Access-Control-Allow-Credentials");
    header_remove("Vary");

    $origin = $_SERVER["HTTP_ORIGIN"] ?? "";

    $allowedRaw = getenv("UNIMATCH_FRONTEND_ORIGINS")
        ?: "https://unimatch-frontend.onrender.com";

    $allowed = array_filter(array_map("trim", explode(",", $allowedRaw)));

    if ($origin && in_array($origin, $allowed, true)) {

        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Credentials: true");
        header("Vary: Origin");

    } elseif (!SecurityConfig::isProduction()) {

        header("Access-Control-Allow-Origin: *");
    }

    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Methods: $methods");
    header("Content-Type: application/json; charset=UTF-8");

    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("Referrer-Policy: no-referrer");
}

  function api_handle_preflight(): void {

    if (($_SERVER["REQUEST_METHOD"] ?? "") === "OPTIONS") {

        header("Access-Control-Allow-Origin: https://unimatch-frontend.onrender.com");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Credentials: true");
        header("Content-Length: 0");

        http_response_code(204);
        exit;
    }
}

    function api_require_method(string $method): void {
        if (strtoupper($_SERVER["REQUEST_METHOD"] ?? "GET") !== strtoupper($method)) {
            api_json([
                "ok" => false,
                "mensaje" => "Método no permitido."
            ], 405);
        }
    }

    function api_json(array $payload, int $status = 200): void {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    function api_error(Throwable $e, string $publicMessage = "No fue posible procesar la solicitud.", int $status = 500): void {
        SecurityConfig::logError($e);
        api_json(["ok" => false, "mensaje" => $publicMessage], $status);
    }

    function api_read_input(): array {
        $raw = file_get_contents("php://input");
        if (!$raw) {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    function api_post_or_input(array $input, string $key, $default = null) {
        if (array_key_exists($key, $_POST)) {
            return $_POST[$key];
        }

        if (array_key_exists($key, $input)) {
            return $input[$key];
        }

        return $default;
    }

    function api_int_value(array $input, string $key): int {
        return (int) (api_post_or_input($input, $key, 0) ?: 0);
    }

    function api_connect_db(): mysqli {
        $db = new Database();
        $conn = $db->connect();

        if (!$conn) {
            throw new Exception("No fue posible conectar con la base de datos.");
        }

        return $conn;
    }
}

if (!function_exists("api_user_is_admin")) {
    function api_user_is_admin($usuario): bool {
        $rol = strtolower((string) ($usuario->rol ?? ""));
        return in_array($rol, ["admin", "administrador", "root"], true);
    }
}


if (!function_exists("api_allowed_email_domain")) {
    function api_allowed_email_domain(): string {
        $domain = trim((string) (getenv("UNIMATCH_ALLOWED_EMAIL_DOMAIN") ?: "@unimatch.edu.co"));
        return str_starts_with($domain, "@") ? strtolower($domain) : "@" . strtolower($domain);
    }

    function api_email_institutional(string $correo): bool {
        $correo = strtolower(trim($correo));
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return str_ends_with($correo, api_allowed_email_domain());
    }

    function api_valid_password_policy(string $password): bool {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password);
    }
}
