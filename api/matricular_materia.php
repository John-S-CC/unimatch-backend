<?php
require_once __DIR__ . "/_common.php";
api_set_common_headers("POST, OPTIONS");
api_handle_preflight();
api_require_method("POST");

require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../middleware/AuthMiddleware.php";
require_once __DIR__ . "/../servicios/validadores/ValidadorCupos.php";
require_once __DIR__ . "/../servicios/validadores/ValidadorHorarios.php";

$usuario = AuthMiddleware::verificar();
$usuarioId = (int) ($usuario->id ?? 0);
$input = api_read_input();
$grupoId = api_int_value($input, "grupo_id");

if ($grupoId <= 0) {
    api_json([
        "ok" => false,
        "mensaje" => "Debes indicar un grupo válido."
    ], 422);
}

try {
    $conn = api_connect_db();
    $conn->begin_transaction();

    $sqlGrupo = "
        SELECT g.id_grupo, g.id_materia, ma.nombre AS materia
        FROM grupos g
        INNER JOIN materias ma ON ma.id_materia = g.id_materia
        WHERE g.id_grupo = ?
        LIMIT 1
    ";
    $stmtGrupo = $conn->prepare($sqlGrupo);
    $stmtGrupo->bind_param("i", $grupoId);
    $stmtGrupo->execute();
    $grupo = $stmtGrupo->get_result()->fetch_assoc();

    if (!$grupo) {
        api_json([
            "ok" => false,
            "mensaje" => "El grupo seleccionado no existe."
        ], 404);
    }

    $sqlYaEnGrupo = "
        SELECT id_matricula
        FROM matriculas
        WHERE usuario_id = ?
          AND grupo_id = ?
          AND estado = 'activa'
        LIMIT 1
    ";
    $stmtYaEnGrupo = $conn->prepare($sqlYaEnGrupo);
    $stmtYaEnGrupo->bind_param("ii", $usuarioId, $grupoId);
    $stmtYaEnGrupo->execute();

    if ($stmtYaEnGrupo->get_result()->num_rows > 0) {
        api_json([
            "ok" => false,
            "mensaje" => "Ya estás matriculado en este grupo."
        ], 409);
    }

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
    $stmtMismaMateria->bind_param("ii", $usuarioId, $grupo["id_materia"]);
    $stmtMismaMateria->execute();

    if ($stmtMismaMateria->get_result()->num_rows > 0) {
        api_json([
            "ok" => false,
            "mensaje" => "Ya tienes una matrícula activa en esta materia."
        ], 409);
    }

    if (!ValidadorCupos::hayCupo($conn, $grupoId)) {
        api_json([
            "ok" => false,
            "mensaje" => "No hay cupos disponibles para este grupo."
        ], 409);
    }

    if (ValidadorHorarios::tieneConflicto($conn, $usuarioId, $grupoId)) {
        api_json([
            "ok" => false,
            "mensaje" => "Existe un cruce de horario con otra materia activa."
        ], 409);
    }

    $sqlInsert = "
        INSERT INTO matriculas (usuario_id, grupo_id, fecha_matricula, estado)
        VALUES (?, ?, NOW(), 'activa')
    ";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("ii", $usuarioId, $grupoId);

    if (!$stmtInsert->execute()) {
        throw new Exception("No se pudo registrar la matrícula.");
    }

    $conn->commit();

    $evento = null;
    if (file_exists(__DIR__ . "/../eventos/MotorEventos.php")) {
        require_once __DIR__ . "/../eventos/MotorEventos.php";
        if (class_exists("MotorEventos") && method_exists("MotorEventos", "procesarEvento")) {
            try {
                $evento = MotorEventos::procesarEvento($conn, "matricula_nueva", [
                    "usuario_id" => $usuarioId,
                    "grupo_id" => $grupoId
                ]);
            } catch (Throwable $eventoError) {
                $evento = [
                    "ok" => false,
                    "mensaje" => $eventoError->getMessage()
                ];
            }
        }
    }

    api_json([
        "ok" => true,
        "mensaje" => "Matrícula realizada correctamente.",
        "data" => [
            "grupo_id" => $grupoId,
            "materia" => $grupo["materia"] ?? null
        ],
        "evento" => $evento
    ]);
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli && $conn->errno === 0) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
    } elseif (isset($conn) && $conn instanceof mysqli) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
    }

    api_json([
        "ok" => false,
        "mensaje" => "Error del servidor: " . $e->getMessage()
    ], 500);
}
