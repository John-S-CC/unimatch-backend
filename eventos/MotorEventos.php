<?php
require_once __DIR__ . '/../servicios/MotorPermutas.php';

class MotorEventos {
    public static function procesarEvento($conn, $tipoEvento, $datos = []) {
        switch ($tipoEvento) {
            case 'cancelacion_materia':
            case 'nueva_solicitud':
            case 'matricula_nueva':
                return MotorPermutas::procesar($conn);
            default:
                return [
                    'ok' => true,
                    'mensaje' => 'Evento sin acciones adicionales.'
                ];
        }
    }
}
