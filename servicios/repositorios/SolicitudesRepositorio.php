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

        // En cambio de materia, la coincidencia espejo se cruza por materias:
        // Usuario A: materia X -> materia Y
        // Usuario B: materia Y -> materia X
        // El intercambio real usa los grupos que cada estudiante libera actualmente.
        $sql = "
            SELECT *
            FROM solicitudes
            WHERE tipo_solicitud = 'cambio_materia'
              AND estado = 'pendiente'
              AND id_solicitud <> ?
              AND usuario_id <> ?
              AND materia_origen = ?
              AND materia_destino = ?
            ORDER BY fecha_solicitud ASC, id_solicitud ASC
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "iiii",
            $sol['id_solicitud'],
            $sol['usuario_id'],
            $sol['materia_destino'],
            $sol['materia_origen']
        );

        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public static function actualizarDestinosPermutaMateria($conn, int $idSolicitud1, int $grupoDestino1, int $idSolicitud2, int $grupoDestino2): bool {
        $sql = "
            UPDATE solicitudes
            SET grupo_destino = CASE
                    WHEN id_solicitud = ? THEN ?
                    WHEN id_solicitud = ? THEN ?
                    ELSE grupo_destino
                END,
                detalle_estado = 'Solicitud cruzada por materia; el grupo destino fue ajustado al grupo liberado por el otro estudiante.'
            WHERE id_solicitud IN (?, ?)
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiiii", $idSolicitud1, $grupoDestino1, $idSolicitud2, $grupoDestino2, $idSolicitud1, $idSolicitud2);

        return $stmt->execute();
    }

    public static function marcarPermuta($conn, $id1, $id2, ?string $fechaResolucion = null) {

        $fecha = $fechaResolucion ?: date('Y-m-d H:i:s');
        $sql = "
            UPDATE solicitudes
            SET estado = 'permuta',
                canal_resolucion = 'permuta',
                fecha_resolucion = ?,
                detalle_estado = 'Solicitud atendida por permuta entre estudiantes.'
            WHERE id_solicitud IN (?, ?)
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $fecha, $id1, $id2);

        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->affected_rows === 2;
    }

    public static function marcarCompletada($conn, $id, ?string $fechaResolucion = null) {

        $fecha = $fechaResolucion ?: date('Y-m-d H:i:s');
        $sql = "
            UPDATE solicitudes
            SET estado = 'permuta',
                canal_resolucion = 'permuta',
                fecha_resolucion = ?,
                detalle_estado = 'Solicitud completada por el motor de permutas.'
            WHERE id_solicitud = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $fecha, $id);

        return $stmt->execute();
    }

    public static function marcarSolicitudComoPermuta($conn, $id, ?string $fechaResolucion = null) {

        $fecha = $fechaResolucion ?: date('Y-m-d H:i:s');
        $sql = "
            UPDATE solicitudes
            SET estado = 'permuta',
                canal_resolucion = 'permuta',
                fecha_resolucion = ?,
                detalle_estado = 'Solicitud completada por ciclo de permutas.'
            WHERE id_solicitud = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $fecha, $id);

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

    public static function marcarAprobada($conn, $idSolicitud, string $canal = 'directa', ?string $fechaResolucion = null, ?string $detalle = null) {
        $fecha = $fechaResolucion ?: date('Y-m-d H:i:s');
        $detalleFinal = $detalle ?: ($canal === 'directa'
            ? 'Solicitud resuelta directamente por validaciones automáticas.'
            : 'Solicitud aprobada por el sistema.');

        $sql = "
            UPDATE solicitudes
            SET estado = 'aprobada',
                canal_resolucion = ?,
                fecha_resolucion = ?,
                detalle_estado = ?
            WHERE id_solicitud = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $canal, $fecha, $detalleFinal, $idSolicitud);

        return $stmt->execute();
    }
}
