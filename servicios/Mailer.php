<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Si usas Composer, asegúrate de que vendor/autoload.php se cargue en tu proyecto
// Si no, puedes requerirlos aquí mismo:
// require_once __DIR__ . '/../vendor/autoload.php';

class Mailer {
    public static function enviar(string $destinatario, string $asunto, string $html, ?string $textoPlano = null): bool {
        $from = getenv("UNIMATCH_MAIL_FROM") ?: "caroj254@gmail.com"; 
        $fromName = getenv("UNIMATCH_MAIL_FROM_NAME") ?: "UniMatch";
        $testingRedirectTo = trim((string) (getenv("UNIMATCH_MAIL_TEST_REDIRECT_TO") ?: ""));
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
            // Configuración del Servidor SMTP (Usando variables de entorno de Render)
            $mail->isSMTP();
            $mail->Host       = getenv("SMTP_HOST") ?: 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv("SMTP_USER") ?: 'tu_correo_gmail@gmail.com'; // Tu cuenta real
            $mail->Password   = getenv("SMTP_PASS") ?: 'tu_password_de_aplicacion'; // Tus 16 letras de Google
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)(getenv("SMTP_PORT") ?: 587);
            $mail->CharSet    = 'UTF-8';

            // Destinatarios
            $mail->setFrom($mail->Username, $fromName);
            $mail->addAddress($destinatario);
            $mail->addReplyTo($mail->Username, $fromName);

            // Contenido del Correo
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $html;
            $mail->AltBody = $textoPlano; // Versión en texto plano para lectores básicos

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Si falla, escribe el porqué en los logs de Render para que puedas debuguear
            error_log("Error de PHPMailer: " . $mail->ErrorInfo);
            return false;
        }
    }
}