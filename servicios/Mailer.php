<?php

class Mailer {
    public static function enviar(string $destinatario, string $asunto, string $html, ?string $textoPlano = null): bool {
        $from = getenv("UNIMATCH_MAIL_FROM") ?: "no-reply@unimatch.edu.co";
        $fromName = getenv("UNIMATCH_MAIL_FROM_NAME") ?: "UniMatch";
        $testingRedirectTo = trim((string) (getenv("UNIMATCH_MAIL_TEST_REDIRECT_TO") ?: ""));
        $originalRecipient = $destinatario;

        // Modo de prueba: conserva la validación institucional @unimatch.edu.co,
        // pero redirige el envío real a un correo existente para pruebas.
        if ($testingRedirectTo !== "") {
            $destinatario = $testingRedirectTo;
            $html = "<p><strong>Modo prueba UniMatch:</strong> este correo fue solicitado para "
                . htmlspecialchars($originalRecipient, ENT_QUOTES, "UTF-8")
                . ", pero fue redirigido automáticamente a este buzón de pruebas.</p><hr>" . $html;
        }

        $textoPlano = $textoPlano ?: strip_tags(str_replace(["<br>", "<br/>", "<br />"], "
", $html));

        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "From: " . self::encodeHeader($fromName) . " <{$from}>";
        $headers[] = "Reply-To: {$from}";
        $headers[] = "X-Mailer: PHP/" . phpversion();

        return @mail($destinatario, self::encodeHeader($asunto), $html, implode("
", $headers));
    }

    private static function encodeHeader(string $value): string {
        return "=?UTF-8?B?" . base64_encode($value) . "?=";
    }
}
