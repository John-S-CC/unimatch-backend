<?php

require_once __DIR__ . '/../Repositorios/SolicitudesRepositorio.php';
require_once __DIR__ . '/../Repositorios/MatriculasRepositorio.php';

class CicloPermutasServicio {

    public static function intentar($conn, $solicitud){

        $ciclo = self::buscarCiclo(
            $conn,
            $solicitud['materia_origen'],
            $solicitud['materia_destino'],
            []
        );

        if(!$ciclo){
            return false;
        }

        foreach($ciclo as $sol){

            MatriculasRepositorio::actualizarGrupo(
                $conn,
                $sol['usuario_id'],
                $sol['grupo_destino']
            );

        }

        foreach($ciclo as $sol){

            SolicitudesRepositorio::marcarCompletada(
                $conn,
                $sol['id_solicitud']
            );

        }

        return true;
    }

    private static function buscarCiclo($conn,$origen,$actual,$visitados){

        $visitados[]=$actual;

        $solicitudes = SolicitudesRepositorio::buscarPorOrigen($conn,$actual);

        foreach($solicitudes as $sol){

            $destino = $sol['materia_destino'];

            if($destino == $origen){
                $visitados[]=$sol;
                return $visitados;
            }

            if(!in_array($destino,$visitados)){

                $ciclo = self::buscarCiclo(
                    $conn,
                    $origen,
                    $destino,
                    $visitados
                );

                if($ciclo){
                    return $ciclo;
                }

            }

        }

        return null;
    }

}