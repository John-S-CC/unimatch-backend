<?php

class MatriculasRepositorio {

    public static function intercambiarGrupos($conn, $sol1, $sol2) {

        $ok1 = self::actualizarGrupoActivo(
            $conn,
            $sol1['usuario_id'],
            $sol1['grupo_origen'],
            $sol1['grupo_destino']
        );

        $ok2 = self::actualizarGrupoActivo(
            $conn,
            $sol2['usuario_id'],
            $sol2['grupo_origen'],
            $sol2['grupo_destino']
        );

        if (!$ok1 || !$ok2) {
            throw new Exception('No fue posible actualizar ambas matriculas para completar la permuta de grupo.');
        }
    }

    public static function intercambiarMaterias($conn, $sol1, $sol2) {

        $ok1 = self::actualizarGrupoActivo(
            $conn,
            $sol1['usuario_id'],
            $sol1['grupo_origen'],
            $sol1['grupo_destino']
        );

        $ok2 = self::actualizarGrupoActivo(
            $conn,
            $sol2['usuario_id'],
            $sol2['grupo_origen'],
            $sol2['grupo_destino']
        );

        if (!$ok1 || !$ok2) {
            throw new Exception('No fue posible actualizar ambas matriculas para completar la permuta de materia.');
        }
    }

    public static function actualizarGrupo($conn, $usuario, $grupo) {

        $sql = "
            UPDATE matriculas
            SET grupo_id = ?
            WHERE usuario_id = ?
              AND estado = 'activa'
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $grupo, $usuario);

        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->affected_rows > 0;
    }

    public static function actualizarGrupoActivo($conn, $usuarioId, $grupoOrigen, $grupoDestino) {

        $sql = "
            UPDATE matriculas
            SET grupo_id = ?
            WHERE usuario_id = ?
              AND grupo_id = ?
              AND estado = 'activa'
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $grupoDestino, $usuarioId, $grupoOrigen);

        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->affected_rows > 0;
    }
}
