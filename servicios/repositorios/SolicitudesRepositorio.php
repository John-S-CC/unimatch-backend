<?php

class SolicitudesRepositorio {

    public static function pendientes($conn) {

        $sql = "
            SELECT *
            FROM solicitudes
            WHERE estado = 'pendiente'
              AND tipo_solicitud IN ('cambio_grupo', 'cambio_materia')
            ORDER BY fecha_solicitud ASC, id_solicitud ASC
        ";

        $result = $conn->query($sql);

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function existePendienteSimilar($conn, array $payload): bool {
        $sql = "
            SELECT id_solicitud
            FROM solicitudes
            WHERE usuario_id = ?
              AND tipo_solicitud = ?
              AND estado IN ('pendiente', 'procesando')
              AND COALESCE(grupo_origen, 0) = COALESCE(?, 0)
              AND COALESCE(grupo_destino, 0) = COALESCE(?, 0)
              AND COALESCE(materia_origen, 0) = COALESCE(?, 0)
              AND COALESCE(materia_destino, 0) = COALESCE(?, 0)
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isiiii",
            $payload['usuario_id'],
            $payload['tipo_solicitud'],
            $payload['grupo_origen'],
            $payload['grupo_destino'],
            $payload['materia_origen'],
            $payload['materia_destino']
        );
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    public static function buscarPermutaGrupo($conn, $sol) {

        $sql = "
            SELECT *
            FROM solicitudes
            WHERE tipo_solicitud = 'cambio_grupo'
              AND estado = 'pendiente'
              AND id_solicitud <> ?
              AND usuario_id <> ?
              AND grupo_origen = ?
              AND grupo_destino = ?
              AND materia_origen = ?
              AND materia_destino = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "iiiiii",
            $sol['id_solicitud'],
            $sol['usuario_id'],
            $sol['grupo_destino'],
            $sol['grupo_origen'],
            $sol['materia_destino'],
            $sol['materia_origen']
        );

        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public static function buscarPermutaMateria($conn, $sol) {

        $sql = "
            SELECT *
            FROM solicitudes
            WHERE tipo_solicitud = 'cambio_materia'
              AND estado = 'pendiente'
              AND id_solicitud <> ?
              AND usuario_id <> ?
              AND materia_origen = ?
              AND materia_destino = ?
              AND grupo_origen = ?
              AND grupo_destino = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "iiiiii",
            $sol['id_solicitud'],
            $sol['usuario_id'],
            $sol['materia_destino'],
            $sol['materia_origen'],
            $sol['grupo_destino'],
            $sol['grupo_origen']
        );

        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public static function marcarPermuta($conn, $id1, $id2) {

        $sql = "
            UPDATE solicitudes
            SET estado = 'permuta'
            WHERE id_solicitud IN (?, ?)
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id1, $id2);

        return $stmt->execute();
    }

    public static function marcarCompletada($conn, $id) {

        $sql = "
            UPDATE solicitudes
            SET estado = 'permuta'
            WHERE id_solicitud = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }

    public static function marcarSolicitudComoPermuta($conn, $id) {

        $sql = "
            UPDATE solicitudes
            SET estado = 'permuta'
            WHERE id_solicitud = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }

    public static function buscarPorOrigen($conn, $materia) {

        $sql = "
            SELECT *
            FROM solicitudes
            WHERE tipo_solicitud = 'cambio_materia'
              AND materia_origen = ?
              AND estado = 'pendiente'
            ORDER BY fecha_solicitud ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $materia);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function marcarAprobada($conn, $idSolicitud) {
        $sql = "
            UPDATE solicitudes
            SET estado = 'aprobada'
            WHERE id_solicitud = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idSolicitud);

        return $stmt->execute();
    }
}
