<?php
/**
 * @OA\Post(
 *     path="/api/cancelar_materia.php",
 *     summary="Cancelar matrícula de una materia",
 *     description="Permite a un estudiante cancelar su matrícula en una materia específica",
 *     tags={"Matrículas"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         description="ID de la matrícula a cancelar",
 *         @OA\JsonContent(
 *             type="object",
 *             required={"id_matricula"},
 *             properties={
 *                 @OA\Property(property="id_matricula", type="integer", example=123)
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Matrícula cancelada",
 *         @OA\JsonContent(
 *             type="object",
 *             properties={
 *                 @OA\Property(property="ok", type="boolean", example=true),
 *                 @OA\Property(property="mensaje", type="string", example="Matrícula cancelada")
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Cancelación no permitida",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
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
    exit;
}

try {

    $conn = api_connect_db();

    CalendarioAcademico::sincronizarSolicitudesVencidas($conn);

    $validacion = CalendarioAcademico::validarOperacion($conn, 'cancelacion');

    if (!$validacion['ok']) {
        api_json([
            "ok" => false,
            "mensaje" => $validacion['mensaje']
        ], 409);
        exit;
    }

    $fechaSistema = CalendarioAcademico::obtenerFechaSistema($conn);

    $conn->begin_transaction();

    $sqlExiste = "
        SELECT 
            m.id_matricula,
            ma.nombre AS materia
        FROM matriculas m
        INNER JOIN grupos g 
            ON g.id_grupo = m.grupo_id
        INNER JOIN materias ma 
            ON ma.id_materia = g.id_materia
        WHERE m.usuario_id = ?
          AND m.grupo_id = ?
          AND m.estado = 'activa'
        LIMIT 1
    ";

    $stmtExiste = $conn->prepare($sqlExiste);

    if (!$stmtExiste) {
        throw new Exception("Error preparando validación: " . $conn->error);
    }

    $stmtExiste->bind_param("ii", $usuarioId, $grupoId);

    if (!$stmtExiste->execute()) {
        throw new Exception("Error ejecutando validación: " . $stmtExiste->error);
    }

    $matricula = $stmtExiste->get_result()->fetch_assoc();

    if (!$matricula) {
        api_json([
            "ok" => false,
            "mensaje" => "No se encontró una matrícula activa para cancelar."
        ], 404);
        exit;
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

    if (!$stmt) {
        throw new Exception("Error preparando actualización: " . $conn->error);
    }

    $stmt->bind_param("ii", $usuarioId, $grupoId);

    if (!$stmt->execute()) {
        throw new Exception("Error ejecutando actualización: " . $stmt->error);
    }

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

    exit;

} catch (Throwable $e) {

    if (isset($conn) && $conn instanceof mysqli) {
        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
        }
    }

    api_json([
        "ok" => false,
        "mensaje" => "No fue posible procesar la solicitud.",
        "debug" => $e->getMessage(),
        "linea" => $e->getLine(),
        "archivo" => $e->getFile()
    ], 500);

    exit;
}
