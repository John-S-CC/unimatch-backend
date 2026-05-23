<?php
require_once 'vendor/autoload.php';

use OpenApi\Generator;

$openapi = Generator::scan([__DIR__ . '/api/swagger.php', __DIR__ . '/api', __DIR__ . '/controladores']);

// Guardar como JSON
file_put_contents(__DIR__ . '/openapi.json', $openapi->toJson());

// También guardar como YAML (opcional)
file_put_contents(__DIR__ . '/openapi.yaml', $openapi->toYaml());

echo "✅ openapi.json generado correctamente en: " . __DIR__ . '/openapi.json' . PHP_EOL;
echo "✅ openapi.yaml generado correctamente en: " . __DIR__ . '/openapi.yaml' . PHP_EOL;
?>
