<?php

require_once __DIR__ . '/../Repositorios/SolicitudesRepositorio.php';
require_once __DIR__ . '/../Repositorios/MatriculasRepositorio.php';
require_once __DIR__ . '/../Validadores/ValidadorHorarios.php';

class PermutaMateriaServicio {

    public static function intentar($conn, $solicitud){

        if($solicitud['tipo_solicitud'] != 'cambio_materia'){
            return false;
        }

        $permuta = SolicitudesRepositorio::buscarPermutaMateria($conn, $solicitud);

        if(!$permuta){
            return false;
        }

        if(
            ValidadorHorarios::tieneConflicto($conn, $solicitud['usuario_id'], $permuta['grupo_origen']) ||
            ValidadorHorarios::tieneConflicto($conn, $permuta['usuario_id'], $solicitud['grupo_origen'])
        ){
            return false;
        }

        MatriculasRepositorio::intercambiarMaterias(
            $conn,
            $solicitud,
            $permuta
        );

        SolicitudesRepositorio::marcarPermuta(
            $conn,
            $solicitud['id_solicitud'],
            $permuta['id_solicitud']
        );

        return true;
    }
}