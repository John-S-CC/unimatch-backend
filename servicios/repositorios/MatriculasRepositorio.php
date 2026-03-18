<?php

class MatriculasRepositorio {

    public static function intercambiarGrupos($conn,$u1,$u2,$g1,$g2){

        $sql="UPDATE matriculas SET grupo_id=? WHERE usuario_id=?";

        $stmt=$conn->prepare($sql);
        $stmt->bind_param("ii",$g1,$u1);
        $stmt->execute();

        $stmt=$conn->prepare($sql);
        $stmt->bind_param("ii",$g2,$u2);
        $stmt->execute();
    }

    public static function intercambiarMaterias($conn,$sol1,$sol2){

        self::actualizarGrupo(
            $conn,
            $sol1['usuario_id'],
            $sol1['grupo_destino']
        );

        self::actualizarGrupo(
            $conn,
            $sol2['usuario_id'],
            $sol2['grupo_destino']
        );
    }

    public static function actualizarGrupo($conn,$usuario,$grupo){

        $sql="
        UPDATE matriculas
        SET grupo_id=?
        WHERE usuario_id=?
        ";

        $stmt=$conn->prepare($sql);

        $stmt->bind_param("ii",$grupo,$usuario);

        $stmt->execute();
    }
}