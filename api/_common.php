<?php
if (!function_exists("api_set_common_headers")) {
    function api_set_common_headers(string $methods = "GET, OPTIONS"): void {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Allow-Methods: {$methods}");
        header("Content-Type: application/json; charset=UTF-8");
    }

    function api_handle_preflight(): void {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "OPTIONS") {
            http_response_code(200);
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
