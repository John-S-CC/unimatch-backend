<?php

class ValidadorCupos {

    public static function hayCupo($conn,$grupo){

        $sql="
        SELECT cupos_maximos -
        (SELECT COUNT(*) FROM matriculas WHERE grupo_id=?)
        AS disponibles
        FROM grupos
        WHERE id_grupo=?
        ";

        $stmt=$conn->prepare($sql);
        $stmt->bind_param("ii",$grupo,$grupo);
        $stmt->execute();

        $result=$stmt->get_result()->fetch_assoc();

        return $result['disponibles']>0;
    }
}