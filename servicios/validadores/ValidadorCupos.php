<?php

class ValidadorCupos {

    public static function hayCupo($conn, $grupoId) {

        $sql = "
            SELECT 
                g.cupos - COUNT(m.id_matricula) AS disponibles
            FROM grupos g
            LEFT JOIN matriculas m 
                ON m.grupo_id = g.id_grupo
               AND m.estado = 'activa'
            WHERE g.id_grupo = ?
            GROUP BY g.id_grupo, g.cupos
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $grupoId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result && (int)$result["disponibles"] > 0;
    }
}