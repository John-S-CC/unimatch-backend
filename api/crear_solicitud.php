<?php
require_once __DIR__ . "/_common.php";
api_set_common_headers("POST, OPTIONS");
api_handle_preflight();
api_require_method("POST");

require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../middleware/AuthMiddleware.php";
require_once __DIR__ . "/../servicios/validadores/ValidadorCupos.php";
require_once __DIR__ . "/../servicios/validadores/ValidadorHorarios.php";
require_once __DIR__ . "/../servicios/repositorios/SolicitudesRepositorio.php";

$usuario = AuthMiddleware::verificar();
$usuarioId = (int) ($usuario->id ?? 0);
$input = api_read_input();

$tipoSolicitud = trim((string) api_post_or_input($input, "tipo_solicitud", ""));
$grupoOrigen = api_int_value($input, "grupo_origen");
$grupoDestino = api_int_value($input, "grupo_destino");
$materiaOrigen = api_int_value($input, "materia_origen");
$materiaDestino = api_int_value($input, "materia_destino");

$tiposValidos = ["cancelacion", "cambio_grupo", "cambio_materia", "nueva_inscripcion"];
if ($tipoSolicitud === "" || !in_array($tipoSolicitud, $tiposValidos, true)) {
    api_json([
        "ok" => false,
        "mensaje" => "El tipo de solicitud no es válido."
    ], 422);
}

try {
    $conn = api_connect_db();
    $conn->begin_transaction();

    $grupoOrigenExiste = null;
    $grupoDestinoExiste = null;

    $buscarGrupo = function (int $grupoId) use ($conn) {
        if ($grupoId <= 0) {
            return null;
        }

        $sql = "
            SELECT g.id_grupo, g.id_materia, ma.nombre AS materia
            FROM grupos g
            INNER JOIN materias ma ON ma.id_materia = g.id_materia
            WHERE g.id_grupo = ?
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $grupoId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    };

    $validarMatriculaOrigen = function (int $usuarioIdActual, int $grupoIdActual, int $materiaIdActual) use ($conn) {
        $sql = "
            SELECT m.id_matricula
            FROM matriculas m
            INNER JOIN grupos g ON g.id_grupo = m.grupo_id
            WHERE m.usuario_id = ?
              AND m.grupo_id = ?
              AND g.id_materia = ?
              AND m.estado = 'activa'
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $usuarioIdActual, $grupoIdActual, $materiaIdActual);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    };

    if (in_array($tipoSolicitud, ["cancelacion", "cambio_grupo", "cambio_materia"], true)) {
        if ($grupoOrigen <= 0 || $materiaOrigen <= 0) {
            api_json([
                "ok" => false,
                "mensaje" => "Debes seleccionar una materia y grupo de origen válidos."
            ], 422);
        }

        $grupoOrigenExiste = $buscarGrupo($grupoOrigen);
        if (!$grupoOrigenExiste || (int) $grupoOrigenExiste['id_materia'] !== $materiaOrigen) {
            api_json([
                "ok" => false,
                "mensaje" => "El grupo de origen no coincide con la materia seleccionada."
            ], 422);
        }

        if (!$validarMatriculaOrigen($usuarioId, $grupoOrigen, $materiaOrigen)) {
            api_json([
                "ok" => false,
                "mensaje" => "No tienes una matrícula activa que coincida con el origen seleccionado."
            ], 409);
        }
    }

    if (in_array($tipoSolicitud, ["cambio_grupo", "cambio_materia", "nueva_inscripcion"], true)) {
        if ($grupoDestino <= 0 || $materiaDestino <= 0) {
            api_json([
                "ok" => false,
                "mensaje" => "Debes seleccionar un destino válido."
            ], 422);
        }

        $grupoDestinoExiste = $buscarGrupo($grupoDestino);
        if (!$grupoDestinoExiste || (int) $grupoDestinoExiste['id_materia'] !== $materiaDestino) {
            api_json([
                "ok" => false,
                "mensaje" => "El grupo destino no coincide con la materia seleccionada."
            ], 422);
        }
    }


    $payloadSolicitud = [
        'usuario_id' => $usuarioId,
        'tipo_solicitud' => $tipoSolicitud,
        'grupo_origen' => $grupoOrigen,
        'grupo_destino' => $grupoDestino,
        'materia_origen' => $materiaOrigen,
        'materia_destino' => $materiaDestino,
    ];

    if (in_array($tipoSolicitud, ["cambio_grupo", "cambio_materia"], true) && SolicitudesRepositorio::existePendienteSimilar($conn, $payloadSolicitud)) {
        api_json([
            "ok" => false,
            "mensaje" => "Ya tienes una solicitud pendiente o en proceso con esos mismos datos."
        ], 409);
    }

    if ($tipoSolicitud === "cancelacion") {
        $sqlCancelar = "
            UPDATE matriculas
            SET estado = 'cancelada'
            WHERE usuario_id = ?
              AND grupo_id = ?
              AND estado = 'activa'
            LIMIT 1
        ";
        $stmtCancelar = $conn->prepare($sqlCancelar);
        $stmtCancelar->bind_param("ii", $usuarioId, $grupoOrigen);

        if (!$stmtCancelar->execute() || $stmtCancelar->affected_rows <= 0) {
            throw new Exception("No fue posible cancelar la matrícula.");
        }

        $sqlSolicitud = "
            INSERT INTO solicitudes (
                usuario_id,
                tipo_solicitud,
                grupo_origen,
                grupo_destino,
                materia_origen,
                materia_destino,
                estado,
                fecha_solicitud
            ) VALUES (?, 'cancelacion', ?, NULL, ?, NULL, 'aprobada', NOW())
        ";
        $stmtSolicitud = $conn->prepare($sqlSolicitud);
        $stmtSolicitud->bind_param("iii", $usuarioId, $grupoOrigen, $materiaOrigen);

        if (!$stmtSolicitud->execute()) {
            throw new Exception("No fue posible registrar la solicitud de cancelación.");
        }

        $conn->commit();

        api_json([
            "ok" => true,
            "mensaje" => "La materia fue cancelada correctamente.",
            "data" => [
                "tipo_solicitud" => $tipoSolicitud,
                "grupo_origen" => $grupoOrigen,
                "materia_origen" => $materiaOrigen
            ]
        ]);
    }

    if ($tipoSolicitud === "nueva_inscripcion") {
        $sqlMismaMateria = "
            SELECT m.id_matricula
            FROM matriculas m
            INNER JOIN grupos g ON g.id_grupo = m.grupo_id
            WHERE m.usuario_id = ?
              AND m.estado = 'activa'
              AND g.id_materia = ?
            LIMIT 1
        ";
        $stmtMismaMateria = $conn->prepare($sqlMismaMateria);
        $stmtMismaMateria->bind_param("ii", $usuarioId, $materiaDestino);
        $stmtMismaMateria->execute();

        if ($stmtMismaMateria->get_result()->num_rows > 0) {
            api_json([
                "ok" => false,
                "mensaje" => "Ya tienes una matrícula activa en la materia destino."
            ], 409);
        }

        $sqlYaEnGrupoDestino = "
            SELECT id_matricula
            FROM matriculas
            WHERE usuario_id = ?
              AND grupo_id = ?
              AND estado = 'activa'
            LIMIT 1
        ";
        $stmtYaEnGrupoDestino = $conn->prepare($sqlYaEnGrupoDestino);
        $stmtYaEnGrupoDestino->bind_param("ii", $usuarioId, $grupoDestino);
        $stmtYaEnGrupoDestino->execute();
        if ($stmtYaEnGrupoDestino->get_result()->num_rows > 0) {
            api_json([
                "ok" => false,
                "mensaje" => "Ya estás matriculado en ese grupo."
            ], 409);
        }

        if (!ValidadorCupos::hayCupo($conn, $grupoDestino)) {
            api_json([
                "ok" => false,
                "mensaje" => "El grupo destino ya no tiene cupos disponibles."
            ], 409);
        }

        if (ValidadorHorarios::tieneConflicto($conn, $usuarioId, $grupoDestino)) {
            api_json([
                "ok" => false,
                "mensaje" => "El grupo destino presenta cruce de horario con tus materias activas."
            ], 409);
        }

        $sqlInsertMatricula = "
            INSERT INTO matriculas (usuario_id, grupo_id, fecha_matricula, estado)
            VALUES (?, ?, NOW(), 'activa')
        ";
        $stmtInsertMatricula = $conn->prepare($sqlInsertMatricula);
        $stmtInsertMatricula->bind_param("ii", $usuarioId, $grupoDestino);
        if (!$stmtInsertMatricula->execute()) {
            throw new Exception("No fue posible registrar la matrícula directa.");
        }

        $sqlSolicitud = "
            INSERT INTO solicitudes (
                usuario_id,
                tipo_solicitud,
                grupo_origen,
                grupo_destino,
                materia_origen,
                materia_destino,
                estado,
                fecha_solicitud
            ) VALUES (?, 'nueva_inscripcion', NULL, ?, NULL, ?, 'aprobada', NOW())
        ";
        $stmtSolicitud = $conn->prepare($sqlSolicitud);
        $stmtSolicitud->bind_param("iii", $usuarioId, $grupoDestino, $materiaDestino);
        if (!$stmtSolicitud->execute()) {
            throw new Exception("No fue posible registrar la inscripción en historial.");
        }

        $conn->commit();

        api_json([
            "ok" => true,
            "mensaje" => "La inscripción fue realizada correctamente.",
            "data" => [
                "tipo_solicitud" => $tipoSolicitud,
                "grupo_destino" => $grupoDestino,
                "materia_destino" => $materiaDestino,
                "estado" => "aprobada"
            ]
        ]);
    }

    if ($tipoSolicitud === "cambio_grupo") {
        if ($materiaOrigen !== $materiaDestino) {
            api_json([
                "ok" => false,
                "mensaje" => "En cambio de grupo, la materia origen y destino deben ser la misma."
            ], 422);
        }

        if ($grupoOrigen === $grupoDestino) {
            api_json([
                "ok" => false,
                "mensaje" => "Debes escoger un grupo destino diferente al actual."
            ], 422);
        }
    }

    if ($tipoSolicitud === "cambio_materia") {
        if ($materiaOrigen === $materiaDestino) {
            api_json([
                "ok" => false,
                "mensaje" => "En cambio de materia, el destino debe ser diferente al origen."
            ], 422);
        }
    }

    $sql = "
        INSERT INTO solicitudes (
            usuario_id,
            tipo_solicitud,
            grupo_origen,
            grupo_destino,
            materia_origen,
            materia_destino,
            estado,
            fecha_solicitud
        ) VALUES (?, ?, ?, ?, ?, ?, 'pendiente', NOW())
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isiiii",
        $usuarioId,
        $tipoSolicitud,
        $grupoOrigen,
        $grupoDestino,
        $materiaOrigen,
        $materiaDestino
    );

    if (!$stmt->execute()) {
        throw new Exception("No fue posible registrar la solicitud.");
    }

    $solicitudId = (int) $stmt->insert_id;
    $conn->commit();

    $resultadoMotor = null;
    if (in_array($tipoSolicitud, ["cambio_grupo", "cambio_materia"], true)) {
        require_once __DIR__ . "/../servicios/MotorPermutas.php";
        try {
            $resultadoMotor = MotorPermutas::procesar($conn);
        } catch (Throwable $motorError) {
            $resultadoMotor = [
                "ok" => false,
                "mensaje" => $motorError->getMessage()
            ];
        }
    }

    api_json([
        "ok" => true,
        "mensaje" => "Solicitud creada correctamente y enviada a proceso.",
        "data" => [
            "id_solicitud" => $solicitudId,
            "tipo_solicitud" => $tipoSolicitud,
            "grupo_origen" => $grupoOrigen,
            "grupo_destino" => $grupoDestino,
            "materia_origen" => $materiaOrigen,
            "materia_destino" => $materiaDestino
        ],
        "motor" => $resultadoMotor
    ]);
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
    }

    api_json([
        "ok" => false,
        "mensaje" => "Error del servidor: " . $e->getMessage()
    ], 500);
}
