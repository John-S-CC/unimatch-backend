<?php

// 1. Forzamos la carga del autoloader general por si acaso
require_once __DIR__ . '/../vendor/autoload.php';

// 2. CARGA MANUAL DEFENSIVA: Forzamos a PHP a leer el archivo de inicialización de Resend
if (file_exists(__DIR__ . '/../vendor/resend/resend-php/src/Resend.php')) {
    require_once __DIR__ . '/../vendor/resend/resend-php/src/Resend.php';
}

class Mailer {
    public static function enviar(string $destinatario, string $asunto, string $html, ?string $textoPlano = null): bool {
        $fromName = getenv("UNIMATCH_MAIL_FROM_NAME") ?: "UniMatch";
        $testingRedirectTo = trim((string) (getenv("UNIMATCH_MAIL_TEST_REDIRECT_TO") ?: "johncaro07@outlook.com"));
        $originalRecipient = $destinatario;

        // Conservamos tu Modo de Prueba intacto
        if ($testingRedirectTo !== "") {
            $destinatario = $testingRedirectTo;
            $html = "<p><strong>Modo prueba UniMatch:</strong> este correo fue solicitado para "
                . htmlspecialchars($originalRecipient, ENT_QUOTES, "UTF-8")
                . ", pero fue redirigido automáticamente a este buzón de pruebas.</p><hr>" . $html;
        }

        $textoPlano = $textoPlano ?: strip_tags(str_replace(["<br>", "<br/>", "<br />"], "\n", $html));

        // Obtenemos la API Key desde las variables de entorno de Render
        $apiKey = getenv("RESEND_API_KEY");
        if (!$apiKey) {
            error_log("Error: RESEND_API_KEY no está configurada.");
            return false;
        }

        try {
            // Invoca la clase usando su espacio de nombres completo estructurado
            $resend = \Resend::client($apiKey);

            $resend->emails->send([
                'from' => "{$fromName} <onboarding@resend.dev>",
                'to' => [$destinatario],
                'subject' => $asunto,
                'html' => $html,
                'text' => $textoPlano,
            ]);

            return true;
        } catch (\Throwable $e) {
            if (isset($conn) && $conn instanceof mysqli) {
                @$conn->rollback();
            }
            
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_with_escape([
                "ok" => false, 
                "mensaje" => "Error en API Resend: " . $e->getMessage(),
                "linea" => $e->getLine(),
                "archivo" => $e->getFile()
            ]);
            exit;
        }
    }
}

if (!function_exists('json_with_escape')) {
    function json_with_escape($data) {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}