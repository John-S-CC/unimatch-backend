<?php

require_once __DIR__.'/Permutas/PermutaGrupoServicio.php';
require_once __DIR__.'/Permutas/PermutaMateriaServicio.php';
require_once __DIR__.'/Permutas/CicloPermutasServicio.php';
require_once __DIR__.'/Repositorios/SolicitudesRepositorio.php';

class MotorPermutas {

    public static function procesar($conn){

        $solicitudes = SolicitudesRepositorio::pendientes($conn);

        foreach($solicitudes as $solicitud){

            if(PermutaGrupoServicio::intentar($conn,$solicitud)){
                continue;
            }

            if(PermutaMateriaServicio::intentar($conn,$solicitud)){
                continue;
            }

            if(CicloPermutasServicio::intentar($conn,$solicitud)){
                continue;
            }

        }

    }

}