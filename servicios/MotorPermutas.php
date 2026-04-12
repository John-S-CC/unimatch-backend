<?php

require_once __DIR__ . '/permutas/CambioDirectoServicio.php';
require_once __DIR__ . '/permutas/PermutasGrupoServicio.php';
require_once __DIR__ . '/permutas/PermutaMateriaServicio.php';
require_once __DIR__ . '/permutas/CicloPermutasServicio.php';
require_once __DIR__ . '/repositorios/SolicitudesRepositorio.php';

class MotorPermutas {

    public static function procesar($conn) {

        $solicitudes = SolicitudesRepositorio::pendientes($conn);

        $procesadas = 0;
        $errores = [];

        foreach ($solicitudes as $solicitud) {

            try {
                $conn->begin_transaction();

                $procesada = false;

                if ($solicitud['tipo_solicitud'] === 'cambio_grupo' || $solicitud['tipo_solicitud'] === 'cambio_materia') {
                    $procesada = CambioDirectoServicio::intentar($conn, $solicitud);
                }

                if (!$procesada && $solicitud['tipo_solicitud'] === 'cambio_grupo') {
                    $procesada = PermutaGrupoServicio::intentar($conn, $solicitud);
                }

                if (!$procesada && $solicitud['tipo_solicitud'] === 'cambio_materia') {
                    $procesada = PermutaMateriaServicio::intentar($conn, $solicitud);
                }

                if (!$procesada && $solicitud['tipo_solicitud'] === 'cambio_materia') {
                    $procesada = CicloPermutasServicio::intentar($conn, $solicitud);
                }

                if ($procesada) {
                    $conn->commit();
                    $procesadas++;
                } else {
                    $conn->rollback();
                }

            } catch (Throwable $e) {
                $conn->rollback();
                $errores[] = [
                    'id_solicitud' => $solicitud['id_solicitud'],
                    'mensaje' => $e->getMessage()
                ];
            }
        }

        return [
            'ok' => true,
            'procesadas' => $procesadas,
            'errores' => $errores
        ];
    }
}