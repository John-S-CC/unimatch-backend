<?php

require_once __DIR__ . '/../repositorios/MatriculasRepositorio.php';
require_once __DIR__ . '/../repositorios/SolicitudesRepositorio.php';
require_once __DIR__ . '/../validadores/ValidadorCupos.php';
require_once __DIR__ . '/../validadores/ValidadorHorarios.php';

class CambioDirectoServicio {

    public static function intentar($conn, $solicitud, ?string $fechaSistema = null) {

        if (!in_array($solicitud['tipo_solicitud'], ['cambio_grupo', 'cambio_materia'])) {
            return false;
        }

        $grupoDestino = (int)$solicitud['grupo_destino'];
        $grupoOrigen = (int)$solicitud['grupo_origen'];
        $usuarioId = (int)$solicitud['usuario_id'];

        if ($grupoDestino <= 0 || $grupoOrigen <= 0) {
            return false;
        }

        if (!ValidadorCupos::hayCupo($conn, $grupoDestino)) {
            return false;
        }

        if (ValidadorHorarios::tieneConflicto($conn, $usuarioId, $grupoDestino, $grupoOrigen)) {
            return false;
        }

        $actualizado = MatriculasRepositorio::actualizarGrupoActivo(
            $conn,
            $usuarioId,
            $grupoOrigen,
            $grupoDestino
        );

        if (!$actualizado) {
            return false;
        }

        SolicitudesRepositorio::marcarAprobada(
            $conn,
            $solicitud['id_solicitud'],
            'directa',
            $fechaSistema,
            'Solicitud resuelta directamente porque había cupo y no existía conflicto de horario.'
        );

        return true;
    }
}
