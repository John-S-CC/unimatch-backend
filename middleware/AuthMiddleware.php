<?php
require_once __DIR__ . "/../configuracion/jwt.php";

class AuthMiddleware {

    public static function verificar() {
        $authHeader = self::obtenerAuthorizationHeader();

        if (!$authHeader) {
            self::error('Token no enviado');
        }

        if (stripos($authHeader, 'Bearer ') !== 0) {
            self::error('Formato de token inválido');
        }

        $token = trim(substr($authHeader, 7));
        $decoded = JWTConfig::validarToken($token);

        if (!$decoded || !isset($decoded->data)) {
            self::error('Token inválido o expirado');
        }

        return $decoded->data;
    }

    public static function verificarRol($rolRequerido) {
        $usuario = self::verificar();

        if (!isset($usuario->rol) || $usuario->rol !== $rolRequerido) {
            self::error('No autorizado');
        }

        return $usuario;
    }

    private static function obtenerAuthorizationHeader() {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, 'Authorization') === 0) {
                    return $value;
                }
            }
        }

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return null;
    }

    private static function error($mensaje) {
        http_response_code(401);
        echo json_encode([
            'ok' => false,
            'mensaje' => $mensaje
        ]);
        exit;
    }
}
