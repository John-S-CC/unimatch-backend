<?php

class ValidadorHorarios {

    public static function tieneConflicto($conn,$usuario,$grupoDestino){

        $sql="
        SELECT h.dia,h.hora_inicio,h.hora_fin
        FROM horarios h
        WHERE h.grupo_id=?
        ";

        $stmt=$conn->prepare($sql);
        $stmt->bind_param("i",$grupoDestino);
        $stmt->execute();
        $horariosDestino=$stmt->get_result();

        $sql="
        SELECT h.dia,h.hora_inicio,h.hora_fin
        FROM horarios h
        JOIN matriculas m ON m.grupo_id=h.grupo_id
        WHERE m.usuario_id=?
        ";

        $stmt=$conn->prepare($sql);
        $stmt->bind_param("i",$usuario);
        $stmt->execute();
        $horariosActuales=$stmt->get_result();

        while($dest=$horariosDestino->fetch_assoc()){

            while($act=$horariosActuales->fetch_assoc()){

                if(
                    $dest['dia']==$act['dia'] &&
                    $dest['hora_inicio']<$act['hora_fin'] &&
                    $dest['hora_fin']>$act['hora_inicio']
                ){
                    return true;
                }

            }

        }

        return false;
    }
}