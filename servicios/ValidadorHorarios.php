<?php

class ValidadorHorarios {

    public static function tieneConflicto($conn, $usuario, $grupoDestino){

        // horarios del grupo destino
        $sql = "
        SELECT dia, hora_inicio, hora_fin
        FROM horarios
        WHERE grupo_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $grupoDestino);
        $stmt->execute();

        $horariosDestino = $stmt->get_result();

        // horarios de materias actuales del estudiante
        $sql = "
        SELECT h.dia, h.hora_inicio, h.hora_fin
        FROM matriculas m
        JOIN horarios h ON m.grupo_id = h.grupo_id
        WHERE m.usuario_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario);
        $stmt->execute();

        $horariosActuales = $stmt->get_result();

        $actuales = [];

        while($row = $horariosActuales->fetch_assoc()){
            $actuales[] = $row;
        }

        // comparar horarios
        while($destino = $horariosDestino->fetch_assoc()){

            foreach($actuales as $actual){

                if($destino['dia'] == $actual['dia']){

                    if(
                        $destino['hora_inicio'] < $actual['hora_fin']
                        &&
                        $destino['hora_fin'] > $actual['hora_inicio']
                    ){
                        return true; // conflicto
                    }

                }

            }

        }

        return false; // no hay conflicto

    }

}