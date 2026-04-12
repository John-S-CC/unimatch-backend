<?php

class ValidadorHorarios {

    public static function tieneConflicto($conn, $usuarioId, $grupoDestino, $grupoExcluir = null) {

        $sqlDestino = "
            SELECT h.dia, h.hora_inicio, h.hora_fin
            FROM horarios h
            WHERE h.id_grupo = ?
        ";

        $stmtDestino = $conn->prepare($sqlDestino);
        $stmtDestino->bind_param("i", $grupoDestino);
        $stmtDestino->execute();
        $resultDestino = $stmtDestino->get_result();

        $horariosDestino = [];
        while ($fila = $resultDestino->fetch_assoc()) {
            $horariosDestino[] = $fila;
        }

        $sqlActuales = "
            SELECT h.dia, h.hora_inicio, h.hora_fin, m.grupo_id
            FROM horarios h
            INNER JOIN matriculas m ON m.grupo_id = h.id_grupo
            WHERE m.usuario_id = ?
              AND m.estado = 'activa'
        ";

        if ($grupoExcluir !== null) {
            $sqlActuales .= " AND m.grupo_id <> ? ";
        }

        $stmtActuales = $conn->prepare($sqlActuales);

        if ($grupoExcluir !== null) {
            $stmtActuales->bind_param("ii", $usuarioId, $grupoExcluir);
        } else {
            $stmtActuales->bind_param("i", $usuarioId);
        }

        $stmtActuales->execute();
        $resultActuales = $stmtActuales->get_result();

        $horariosActuales = [];
        while ($fila = $resultActuales->fetch_assoc()) {
            $horariosActuales[] = $fila;
        }

        foreach ($horariosDestino as $dest) {
            foreach ($horariosActuales as $act) {
                if (
                    $dest['dia'] === $act['dia'] &&
                    $dest['hora_inicio'] < $act['hora_fin'] &&
                    $dest['hora_fin'] > $act['hora_inicio']
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}