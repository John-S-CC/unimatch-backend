<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . "/../vendor/autoload.php";

class JWTConfig {
    private static function getSecretKey() {
        return getenv('UNIMATCH_JWT_SECRET') ?: 'uN1mAtCh_2026_S3cr3t_K3y_Muy_Larga_Y_Segura_Para_JWT';
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
