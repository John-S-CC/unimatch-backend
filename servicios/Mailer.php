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

        // Estructuramos los datos exactamente como los pide la API de Resend
        $data = [
            'from' => "{$fromName} <onboarding@resend.dev>",
            'to' => [$destinatario],
            'subject' => $asunto,
            'html' => $html,
            'text' => $textoPlano
        ];

        // Iniciamos la petición cURL nativa de PHP
        $ch = curl_init('https://api.resend.com/emails');
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        // Enviamos las cabeceras de autorización HTTP requeridas
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            self::responderError("Error de conexión cURL: " . $error_msg, __LINE__);
        }
        
        curl_close($ch);

        // Si la API responde con un código exitoso (200 o 201), el correo se envió
        if ($httpCode === 200 || $httpCode === 201) {
            return true;
        }

        // Si responde otra cosa, capturamos el error de la API
        self::responderError("Error en API Resend (HTTP {$httpCode}): " . $response, __LINE__);
    }

    private static function responderError(string $mensaje, int $linea): void {
        if (isset($conn) && $conn instanceof mysqli) {
            @$conn->rollback();
        }
        
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            "ok" => false, 
            "mensaje" => $mensaje,
            "linea" => $linea,
            "archivo" => __FILE__
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}