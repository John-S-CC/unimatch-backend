<?php
/**
 * Carga variables desde unimatch-backend/.env cuando existen.
 * En producción se recomienda configurar variables reales en el hosting
 * y no subir el archivo .env al repositorio.
 */
class EnvLoader {
    private static bool $loaded = false;

    public static function load(): void {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        $path = __DIR__ . '/../.env';
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $value = trim($value, '"\'');
            if ($key !== '' && getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }
}
EnvLoader::load();
