<?php
// Verificación de que openapi.json es accesible
header('Content-Type: application/json');

$openapi_path = __DIR__ . '/openapi.json';
if (file_exists($openapi_path)) {
    $size = filesize($openapi_path);
    $json_content = file_get_contents($openapi_path);
    $is_valid = json_decode($json_content) !== null;
    
    echo json_encode([
        'ok' => true,
        'message' => 'openapi.json is accessible',
        'file_size' => $size,
        'is_valid_json' => $is_valid,
        'swagger_url' => 'https://unimatch-backend-fid5.onrender.com/docs/index.html',
        'openapi_json_path' => './openapi.json'
    ], JSON_PRETTY_PRINT);
} else {
    http_response_code(404);
    echo json_encode([
        'ok' => false,
        'error' => 'openapi.json file not found',
        'expected_path' => $openapi_path
    ], JSON_PRETTY_PRINT);
}
?>
