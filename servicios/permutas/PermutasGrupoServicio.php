<?php

require_once __DIR__ . '/../repositorios/SolicitudesRepositorio.php';
require_once __DIR__ . '/../repositorios/MatriculasRepositorio.php';
require_once __DIR__ . '/../validadores/ValidadorHorarios.php';

class PermutaGrupoServicio {

    public static function intentar($conn, $solicitud, ?string $fechaSistema = null){

        if($solicitud['tipo_solicitud'] != 'cambio_grupo'){
            return false;
        }

        $permuta = SolicitudesRepositorio::buscarPermutaGrupo($conn, $solicitud);

        if(!$permuta){
            return false;
        }

        if (
            ValidadorHorarios::tieneConflicto($conn, $solicitud['usuario_id'], $permuta['grupo_origen'], $solicitud['grupo_origen']) ||
            ValidadorHorarios::tieneConflicto($conn, $permuta['usuario_id'], $solicitud['grupo_origen'], $permuta['grupo_origen'])
        ) {
            return false;
        }

        MatriculasRepositorio::intercambiarGrupos(
            $conn,
            $solicitud,
            $permuta
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
