<?php

require_once __DIR__ . '/../repositorios/SolicitudesRepositorio.php';
require_once __DIR__ . '/../repositorios/MatriculasRepositorio.php';

class CicloPermutasServicio {

    public static function intentar($conn, $solicitud){

        if($solicitud['tipo_solicitud'] !== 'cambio_materia'){
            return false;
        }

        $ciclo = self::buscarCiclo(
            $conn,
            $solicitud['materia_origen'],
            $solicitud,
            [],
            []
        );

        if(!$ciclo || count($ciclo) < 2){
            return false;
        }

        foreach($ciclo as $sol){
            MatriculasRepositorio::actualizarGrupoActivo(
                $conn,
                $sol['usuario_id'],
                $sol['grupo_origen'],
                $sol['grupo_destino']
            );
        }

        foreach($ciclo as $sol){
            SolicitudesRepositorio::marcarSolicitudComoPermuta(
                $conn,
                $sol['id_solicitud']
            );
        }

        return true;
    }

    private static function buscarCiclo($conn, $materiaInicial, $solicitudActual, $camino, $visitadas){

        $camino[] = $solicitudActual;
        $visitadas[] = $solicitudActual['id_solicitud'];

        $destinoActual = (int)$solicitudActual['materia_destino'];

        if($destinoActual === (int)$materiaInicial && count($camino) > 1){
            return $camino;
        }

        $siguientes = SolicitudesRepositorio::buscarPorOrigen($conn, $destinoActual);

        foreach($siguientes as $sig){

            if(in_array($sig['id_solicitud'], $visitadas)){
                continue;
            }

            $resultado = self::buscarCiclo(
                $conn,
                $materiaInicial,
                $sig,
                $camino,
                $visitadas
            );

            if($resultado){
                return $resultado;
            }
        }

        return null;
    }
}