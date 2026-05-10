<?php

require_once __DIR__ . '/../repositorios/SolicitudesRepositorio.php';
require_once __DIR__ . '/../repositorios/MatriculasRepositorio.php';
require_once __DIR__ . '/../validadores/ValidadorHorarios.php';

class PermutaMateriaServicio {

    public static function intentar($conn, $solicitud, ?string $fechaSistema = null){

        if($solicitud['tipo_solicitud'] != 'cambio_materia'){
            return false;
        }

        $permuta = SolicitudesRepositorio::buscarPermutaMateria($conn, $solicitud);

        if(!$permuta){
            return false;
        }

        // Cada estudiante recibe el grupo que libera el otro.
        $grupoDestinoSolicitud = (int) $permuta['grupo_origen'];
        $grupoDestinoPermuta = (int) $solicitud['grupo_origen'];

        if (
            ValidadorHorarios::tieneConflicto($conn, (int) $solicitud['usuario_id'], $grupoDestinoSolicitud, (int) $solicitud['grupo_origen']) ||
            ValidadorHorarios::tieneConflicto($conn, (int) $permuta['usuario_id'], $grupoDestinoPermuta, (int) $permuta['grupo_origen'])
        ) {
            return false;
        }

        $solicitudAjustada = $solicitud;
        $permutaAjustada = $permuta;
        $solicitudAjustada['grupo_destino'] = $grupoDestinoSolicitud;
        $permutaAjustada['grupo_destino'] = $grupoDestinoPermuta;

        MatriculasRepositorio::intercambiarMaterias(
            $conn,
            $solicitudAjustada,
            $permutaAjustada
        );

        SolicitudesRepositorio::actualizarDestinosPermutaMateria(
            $conn,
            (int) $solicitud['id_solicitud'],
            $grupoDestinoSolicitud,
            (int) $permuta['id_solicitud'],
            $grupoDestinoPermuta
        );

        $marcada = SolicitudesRepositorio::marcarPermuta(
            $conn,
            $solicitud['id_solicitud'],
            $permuta['id_solicitud'],
            $fechaSistema
        );

        if (!$marcada) {
            throw new Exception('No fue posible marcar ambas solicitudes como permuta.');
        }

        return true;
    }
}
