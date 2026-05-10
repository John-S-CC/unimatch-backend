<?php

class CalendarioAcademico {
    public static function obtenerConfiguracion(mysqli $conn): array {
        $sql = "
            SELECT
                id_config,
                DATE_FORMAT(fecha_sistema, '%Y-%m-%d %H:%i:%s') AS fecha_sistema,
                DATE_FORMAT(fecha_limite_inscripcion, '%Y-%m-%d %H:%i:%s') AS fecha_limite_inscripcion,
                DATE_FORMAT(fecha_limite_cancelacion, '%Y-%m-%d %H:%i:%s') AS fecha_limite_cancelacion,
                DATE_FORMAT(fecha_limite_permutas, '%Y-%m-%d %H:%i:%s') AS fecha_limite_permutas,
                DATE_FORMAT(fecha_actualizacion, '%Y-%m-%d %H:%i:%s') AS fecha_actualizacion
            FROM configuracion_academica
            ORDER BY id_config ASC
            LIMIT 1
        ";

        $result = $conn->query($sql);
        $config = $result ? ($result->fetch_assoc() ?: null) : null;

        if ($config) {
            return $config;
        }

        $ahora = date('Y-m-d H:i:s');
        return [
            'id_config' => 0,
            'fecha_sistema' => $ahora,
            'fecha_limite_inscripcion' => null,
            'fecha_limite_cancelacion' => null,
            'fecha_limite_permutas' => null,
            'fecha_actualizacion' => $ahora,
        ];
    }

    public static function obtenerFechaSistema(mysqli $conn): string {
        $config = self::obtenerConfiguracion($conn);
        return (string) ($config['fecha_sistema'] ?? date('Y-m-d H:i:s'));
    }

    public static function sincronizarSolicitudesVencidas(mysqli $conn): int {
        $config = self::obtenerConfiguracion($conn);
        $fechaSistema = (string) ($config['fecha_sistema'] ?? date('Y-m-d H:i:s'));
        $fechaLimitePermutas = $config['fecha_limite_permutas'] ?? null;

        if (!$fechaLimitePermutas || strtotime($fechaSistema) <= strtotime($fechaLimitePermutas)) {
            return 0;
        }

        $detalle = 'Solicitud rechazada automáticamente por vencimiento del periodo de permutas.';
        $sql = "
            UPDATE solicitudes
            SET estado = 'rechazada',
                canal_resolucion = 'vencimiento',
                fecha_resolucion = ?,
                detalle_estado = CASE
                    WHEN detalle_estado IS NULL OR detalle_estado = '' THEN ?
                    ELSE detalle_estado
                END
            WHERE estado = 'pendiente'
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $fechaSistema, $detalle);
        $stmt->execute();

        return max(0, (int) $stmt->affected_rows);
    }

    public static function validarOperacion(mysqli $conn, string $operacion): array {
        $config = self::obtenerConfiguracion($conn);
        $fechaSistema = (string) ($config['fecha_sistema'] ?? date('Y-m-d H:i:s'));
        $timestampSistema = strtotime($fechaSistema);

        $reglas = [
            'inscripcion_directa' => [
                'campo' => 'fecha_limite_inscripcion',
                'mensaje' => 'La fecha de inscripción directa ya cerró en la plataforma.'
            ],
            'cancelacion' => [
                'campo' => 'fecha_limite_cancelacion',
                'mensaje' => 'La fecha permitida para cancelaciones ya venció.'
            ],
            'permuta' => [
                'campo' => 'fecha_limite_permutas',
                'mensaje' => 'La fecha permitida para solicitudes de permuta ya venció.'
            ],
        ];

        if (!isset($reglas[$operacion])) {
            return ['ok' => true, 'configuracion' => $config, 'fecha_sistema' => $fechaSistema];
        }

        $campo = $reglas[$operacion]['campo'];
        $limite = $config[$campo] ?? null;
        if (!$limite) {
            return ['ok' => true, 'configuracion' => $config, 'fecha_sistema' => $fechaSistema];
        }

        if ($timestampSistema > strtotime($limite)) {
            return [
                'ok' => false,
                'mensaje' => $reglas[$operacion]['mensaje'],
                'configuracion' => $config,
                'fecha_sistema' => $fechaSistema,
            ];
        }

        return ['ok' => true, 'configuracion' => $config, 'fecha_sistema' => $fechaSistema];
    }
}
