<?php
require_once __DIR__ . '/env.php';

class SecurityConfig {
    public static function isProduction(): bool {
        return strtolower((string) (getenv('UNIMATCH_ENV') ?: 'local')) === 'production';
    }

    public static function logError(Throwable $e): void {
        $dir = __DIR__ . '/../logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = '[' . date('Y-m-d H:i:s') . '] ' . get_class($e) . ': ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
        @error_log($line, 3, $dir . '/app.log');
    }

    public static function clientIp(): string {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
