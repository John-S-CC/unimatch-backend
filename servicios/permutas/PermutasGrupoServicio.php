<?php

require_once __DIR__ . '/../Repositorios/SolicitudesRepositorio.php';
require_once __DIR__ . '/../Repositorios/MatriculasRepositorio.php';
require_once __DIR__ . '/../Validadores/ValidadorHorarios.php';

class PermutaGrupoServicio {

    public static function intentar($conn, $solicitud){

        if($solicitud['tipo_solicitud'] != 'cambio_grupo'){
            return false;
        }

        $permuta = SolicitudesRepositorio::buscarPermutaGrupo($conn, $solicitud);

        if(!$permuta){
            return false;
        }

        if(
            ValidadorHorarios::tieneConflicto($conn, $solicitud['usuario_id'], $permuta['grupo_origen']) ||
            ValidadorHorarios::tieneConflicto($conn, $permuta['usuario_id'], $solicitud['grupo_origen'])
        ){
            return false;
        }

        MatriculasRepositorio::intercambiarGrupos(
            $conn,
            $solicitud['usuario_id'],
            $permuta['usuario_id'],
            $solicitud['grupo_destino'],
            $permuta['grupo_destino']
        );

        SolicitudesRepositorio::marcarPermuta(
            $conn,
            $solicitud['id_solicitud'],
            $permuta['id_solicitud']
        );

        return true;
    }
}