<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . "/env.php";
require_once __DIR__ . "/../vendor/autoload.php";

class JWTConfig {
    private static function getSecretKey() {
        $secret = getenv('UNIMATCH_JWT_SECRET') ?: getenv('JWT_SECRET');
        if (!$secret || strlen($secret) < 32 || str_contains($secret, 'cambia_esta_clave')) {
            throw new Exception('JWT_SECRET no configurado o demasiado corto.');
        }
        return $secret;
    }

    private static $algorithm = 'HS256';

    public static function generarToken($usuario) {
        $payload = [
            'iss' => 'unimatch',
            'iat' => time(),
            'exp' => time() + (60 * 60),
            'data' => [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'rol' => $usuario['rol']
            ]
        ];

        return JWT::encode($payload, self::getSecretKey(), self::$algorithm);
    }

    public static function validarToken($token) {
        try {
            return JWT::decode($token, new Key(self::getSecretKey(), self::$algorithm));
        } catch (Throwable $e) {
            return null;
        }
    }
}
