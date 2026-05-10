<?php
require_once __DIR__ . "/_common.php";
api_set_common_headers("POST, OPTIONS");
api_handle_preflight();
api_require_method("POST");

require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../middleware/AuthMiddleware.php";
require_once __DIR__ . "/../servicios/CalendarioAcademico.php";

$usuario = AuthMiddleware::verificar();
$usuarioId = (int) ($usuario->id ?? 0);
$input = api_read_input();
$grupoId = api_int_value($input, "grupo_id");

if ($grupoId <= 0) {
    api_json([
        "ok" => false,
        "mensaje" => "El grupo es obligatorio."
    ], 422);
}

try {
    $conn = api_connect_db();
    CalendarioAcademico::sincronizarSolicitudesVencidas($conn);
    $validacion = CalendarioAcademico::validarOperacion($conn, 'cancelacion');
    if (!$validacion['ok']) {
        api_json(['ok' => false, 'mensaje' => $validacion['mensaje']], 409);
    }
    $fechaSistema = CalendarioAcademico::obtenerFechaSistema($conn);
    $conn->begin_transaction();

    $sqlExiste = "
        SELECT m.id_matricula, ma.nombre AS materia
        FROM matriculas m
        INNER JOIN grupos g ON g.id_grupo = m.grupo_id
        INNER JOIN materias ma ON ma.id_materia = g.id_materia
        WHERE m.usuario_id = ?
          AND m.grupo_id = ?
          AND m.estado = 'activa'
        LIMIT 1
    ";
    $stmtExiste = $conn->prepare($sqlExiste);
    $stmtExiste->bind_param("ii", $usuarioId, $grupoId);
    $stmtExiste->execute();
    $matricula = $stmtExiste->get_result()->fetch_assoc();

    if (!$matricula) {
        api_json([
            "ok" => false,
            "mensaje" => "No se encontró una matrícula activa para cancelar."
        ], 404);
    }

    $sql = "
        UPDATE matriculas
        SET estado = 'cancelada'
        WHERE usuario_id = ?
          AND grupo_id = ?
          AND estado = 'activa'
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $usuarioId, $grupoId);
    $stmt->execute();

    if ($stmt->affected_rows <= 0) {
        throw new Exception("No fue posible actualizar la matrícula.");
    }

    $conn->commit();

    api_json([
        "ok" => true,
        "mensaje" => "Matrícula cancelada correctamente.",
        "data" => [
            "grupo_id" => $grupoId,
            "materia" => $matricula["materia"] ?? null,
            "fecha_sistema" => $fechaSistema
        ]
    ]);
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
    }

    api_json([
        "ok" => false,
        "mensaje" => "No fue posible procesar la solicitud."
    ], 500);
}
