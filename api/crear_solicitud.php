<?php
/**
 * @OA\Post(
 *     path="/api/crear_solicitud.php",
 *     summary="Crear una solicitud de cambio académico",
 *     description="Permite crear solicitudes de cancelación, cambio de grupo, cambio de materia o nueva inscripción",
 *     tags={"Solicitudes"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Datos de la solicitud",
 *         @OA\JsonContent(
 *             type="object",
 *             required={"tipo_solicitud"},
 *             properties={
 *                 @OA\Property(property="tipo_solicitud", type="string", enum={"cancelacion", "cambio_grupo", "cambio_materia", "nueva_inscripcion"}, example="cambio_grupo"),
 *                 @OA\Property(property="grupo_origen", type="integer", example=1),
 *                 @OA\Property(property="grupo_destino", type="integer", example=2),
 *                 @OA\Property(property="materia_origen", type="integer", example=5),
 *                 @OA\Property(property="materia_destino", type="integer", example=6)
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Solicitud creada exitosamente",
 *         @OA\JsonContent(
 *             type="object",
 *             properties={
 *                 @OA\Property(property="ok", type="boolean", example=true),
 *                 @OA\Property(property="mensaje", type="string", example="Solicitud registrada"),
 *                 @OA\Property(property="id_solicitud", type="integer", example=42)
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Datos inválidos o conflicto académico",
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
require_once __DIR__ . "/../servicios/validadores/ValidadorCupos.php";
require_once __DIR__ . "/../servicios/validadores/ValidadorHorarios.php";
require_once __DIR__ . "/../servicios/repositorios/SolicitudesRepositorio.php";

$usuario = AuthMiddleware::verificar();
$usuarioId = (int)($usuario->id ?? 0);
$input = api_read_input();

$tipoSolicitud   = trim((string) api_post_or_input($input, "tipo_solicitud", ""));
$grupoOrigen     = api_int_value($input, "grupo_origen");
$grupoDestino    = api_int_value($input, "grupo_destino");
$materiaOrigen   = api_int_value($input, "materia_origen");
$materiaDestino  = api_int_value($input, "materia_destino");

$tiposValidos = ["cancelacion", "cambio_grupo", "cambio_materia", "nueva_inscripcion"];

if ($tipoSolicitud === "" || !in_array($tipoSolicitud, $tiposValidos, true)) {
    api_json([
        "ok" => false,
        "mensaje" => "Tipo de solicitud inválido."
    ], 422);
}

try {
    $conn = api_connect_db();

    CalendarioAcademico::sincronizarSolicitudesVencidas($conn);
    $fechaSistema = CalendarioAcademico::obtenerFechaSistema($conn);

    if ($tipoSolicitud === "nueva_inscripcion") {
        $validacion = CalendarioAcademico::validarOperacion($conn, "inscripcion_directa");
        if (!$validacion["ok"]) {
            api_json($validacion, 409);
        }
    }

    if ($tipoSolicitud === "cancelacion") {
        $validacion = CalendarioAcademico::validarOperacion($conn, "cancelacion");
        if (!$validacion["ok"]) {
            api_json($validacion, 409);
        }
    }

    if (in_array($tipoSolicitud, ["cambio_grupo", "cambio_materia"], true)) {
        $validacion = CalendarioAcademico::validarOperacion($conn, "permuta");
        if (!$validacion["ok"]) {
            api_json($validacion, 409);
        }
    }

    $conn->begin_transaction();

    $buscarGrupo = function ($grupoId) use ($conn) {
        $sql = "
            SELECT g.id_grupo, g.id_materia
            FROM grupos g
            WHERE g.id_grupo = ?
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $grupoId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    };

    $grupoOrigenExiste = null;
    $grupoDestinoExiste = null;

    if (in_array($tipoSolicitud, ["cancelacion", "cambio_grupo", "cambio_materia"], true)) {

        if ($grupoOrigen <= 0 || $materiaOrigen <= 0) {
            api_json([
                "ok" => false,
                "mensaje" => "Debes seleccionar origen válido."
            ], 422);
        }

        $grupoOrigenExiste = $buscarGrupo($grupoOrigen);

        if (!$grupoOrigenExiste || (int)$grupoOrigenExiste["id_materia"] !== $materiaOrigen) {
            api_json([
                "ok" => false,
                "mensaje" => "Grupo origen inválido."
            ], 422);
        }

        $sql = "
            SELECT id_matricula
            FROM matriculas
            WHERE usuario_id = ?
              AND grupo_id = ?
              AND estado = 'activa'
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $usuarioId, $grupoOrigen);
        $stmt->execute();

        if ($stmt->get_result()->num_rows === 0) {
            api_json([
                "ok" => false,
                "mensaje" => "No tienes matrícula activa en el grupo origen."
            ], 409);
        }
    }

    if (in_array($tipoSolicitud, ["cambio_grupo", "cambio_materia", "nueva_inscripcion"], true)) {

        if ($grupoDestino <= 0 || $materiaDestino <= 0) {
            api_json([
                "ok" => false,
                "mensaje" => "Debes seleccionar destino válido."
            ], 422);
        }

        $grupoDestinoExiste = $buscarGrupo($grupoDestino);

        if (!$grupoDestinoExiste || (int)$grupoDestinoExiste["id_materia"] !== $materiaDestino) {
            api_json([
                "ok" => false,
                "mensaje" => "Grupo destino inválido."
            ], 422);
        }
    }

    /*
    ==========================================
    VALIDACIONES EXTRA PARA PERMUTAS
    ==========================================
    */

    if (in_array($tipoSolicitud, ["cambio_grupo", "cambio_materia"], true)) {

        $sql = "
            SELECT id_matricula
            FROM matriculas
            WHERE usuario_id = ?
              AND grupo_id = ?
              AND estado = 'activa'
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $usuarioId, $grupoDestino);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            api_json([
                "ok" => false,
                "mensaje" => "Ya estás matriculado en el grupo destino."
            ], 409);
        }

        if (ValidadorHorarios::tieneConflicto($conn, $usuarioId, $grupoDestino)) {
            api_json([
                "ok" => false,
                "mensaje" => "Existe cruce de horario con otra materia."
            ], 409);
        }
    }

    if ($tipoSolicitud === "cambio_materia") {

        $sql = "
            SELECT m.id_matricula
            FROM matriculas m
            INNER JOIN grupos g ON g.id_grupo = m.grupo_id
            WHERE m.usuario_id = ?
              AND g.id_materia = ?
              AND m.estado = 'activa'
              AND m.grupo_id <> ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $usuarioId, $materiaDestino, $grupoOrigen);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            api_json([
                "ok" => false,
                "mensaje" => "Ya tienes registrada la materia destino."
            ], 409);
        }
    }

    $payloadSolicitud = [
        "usuario_id"      => $usuarioId,
        "tipo_solicitud" => $tipoSolicitud,
        "grupo_origen"   => $grupoOrigen,
        "grupo_destino"  => $grupoDestino,
        "materia_origen" => $materiaOrigen,
        "materia_destino"=> $materiaDestino
    ];

    if (
        in_array($tipoSolicitud, ["cambio_grupo", "cambio_materia"], true)
        && SolicitudesRepositorio::existePendienteSimilar($conn, $payloadSolicitud)
    ) {
        api_json([
            "ok" => false,
            "mensaje" => "Ya tienes una solicitud igual pendiente."
        ], 409);
    }

    /*
    ==========================================
    NUEVA INSCRIPCIÓN DIRECTA
    ==========================================
    */

    if ($tipoSolicitud === "nueva_inscripcion") {

        if (!ValidadorCupos::hayCupo($conn, $grupoDestino)) {
            api_json([
                "ok" => false,
                "mensaje" => "No hay cupos disponibles."
            ], 409);
        }

        if (ValidadorHorarios::tieneConflicto($conn, $usuarioId, $grupoDestino)) {
            api_json([
                "ok" => false,
                "mensaje" => "Cruce de horario detectado."
            ], 409);
        }

        $sql = "
            INSERT INTO matriculas(usuario_id, grupo_id, fecha_matricula, estado)
            VALUES (?, ?, ?, 'activa')
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $usuarioId, $grupoDestino, $fechaSistema);
        $stmt->execute();

        $conn->commit();

        api_json([
            "ok" => true,
            "mensaje" => "Inscripción realizada correctamente."
        ]);
    }

    /*
    ==========================================
    CANCELACIÓN DIRECTA
    ==========================================
    */

    if ($tipoSolicitud === "cancelacion") {

        $sql = "
            UPDATE matriculas
            SET estado='cancelada'
            WHERE usuario_id=?
              AND grupo_id=?
              AND estado='activa'
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $usuarioId, $grupoOrigen);
        $stmt->execute();

        $conn->commit();

        api_json([
            "ok" => true,
            "mensaje" => "Cancelación realizada."
        ]);
    }

    /*
    ==========================================
    REGISTRAR SOLICITUD PENDIENTE
    ==========================================
    */

    $sql = "
        INSERT INTO solicitudes(
            usuario_id,
            tipo_solicitud,
            grupo_origen,
            grupo_destino,
            materia_origen,
            materia_destino,
            estado,
            fecha_solicitud,
            detalle_estado
        )
        VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?, 'Pendiente de cruce automático.')
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isiiiis",
        $usuarioId,
        $tipoSolicitud,
        $grupoOrigen,
        $grupoDestino,
        $materiaOrigen,
        $materiaDestino,
        $fechaSistema
    );

    $stmt->execute();

    $idSolicitudCreada = (int)$stmt->insert_id;

    $conn->commit();

    $resultadoMotor = null;

    if (in_array($tipoSolicitud, ["cambio_grupo", "cambio_materia"], true)) {
        require_once __DIR__ . "/../servicios/MotorPermutas.php";
        $resultadoMotor = MotorPermutas::procesar($conn);
    }

    api_json([
        "ok" => true,
        "mensaje" => "Solicitud registrada correctamente.",
        "id_solicitud" => $idSolicitudCreada,
        "motor" => $resultadoMotor
    ]);

} catch (Throwable $e) {

    if (isset($conn)) {
        try { $conn->rollback(); } catch(Throwable $x){}
    }

    api_json([
        "ok" => false,
        "mensaje" => "No fue posible procesar la solicitud."
    ], 500);
}