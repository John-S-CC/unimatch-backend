<?php

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
            // Inicializamos el cliente de Resend (Viaja por HTTP seguro, saltándose el Firewall)
            $resend = Resend::client($apiKey);

            $resend->emails->send([
                // NOTA: Resend gratis exige que el remitente sea 'onboarding@resend.dev'
                'from' => "{$fromName} <onboarding@resend.dev>",
                'to' => [$destinatario],
                'subject' => $asunto,
                'html' => $html,
                'text' => $textoPlano,
            ]);

            return true;
        } catch (\Throwable $e) {
            // Mantener tu rollback de base de datos por si falla
            if (isset($conn) && $conn instanceof mysqli) {
                @$conn->rollback();
            }
            
            // Retornamos el error real en formato JSON para inspección en el frontend
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

// Función auxiliar para formatear la respuesta igual a tu api_json
if (!function_exists('json_with_escape')) {
    function json_with_escape($data) {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}