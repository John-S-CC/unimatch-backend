<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

 require_once __DIR__ . '/../vendor/autoload.php';

class Mailer {
    public static function enviar(string $destinatario, string $asunto, string $html, ?string $textoPlano = null): bool {
        $from = getenv("UNIMATCH_MAIL_FROM") ?: "caroj254@gmail.com"; 
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

        // Inicializamos PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Configuración del Servidor SMTP
            $mail->isSMTP();
            $mail->Host       = getenv("SMTP_HOST") ?: 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv("SMTP_USER") ?: 'caroj254@gmail.com';
            $mail->Password   = getenv("SMTP_PASS") ?: 'prnxemvcmdpijdih';
            
            // CAMBIO AQUÍ: Forzamos cifrado SSL en el puerto 465 (Suele saltarse mejor los bloqueos de Docker)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
            $mail->Port       = 465; 
            
            $mail->CharSet    = 'UTF-8';

            // Opciones avanzadas para evitar caídas de certificados locales en Docker
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                ]
            ];

            // Destinatarios
            $mail->setFrom($mail->Username, $fromName);
            $mail->addAddress($destinatario);
            $mail->addReplyTo($mail->Username, $fromName);

            // Contenido del Correo
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $html;
            $mail->AltBody = $textoPlano;

            $mail->send();
            return true;
        } catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        @$conn->rollback();
    }
    // CAMBIA ESTA LÍNEA: Vamos a mandar el mensaje real del error al frontend
    api_json([
        "ok" => false, 
        "mensaje" => $e->getMessage(),
        "linea" => $e->getLine(),
        "archivo" => $e->getFile()
    ], 500);
}
}
    }
