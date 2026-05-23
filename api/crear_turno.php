<?php
/**
 * @OA\Post(
 *     path="/api/crear_turno.php",
 *     summary="Crear nuevo turno (Admin)",
 *     description="Permite a un administrador crear un nuevo turno para una materia",
 *     tags={"Turnos"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Datos del nuevo turno",
 *         @OA\JsonContent(
 *             type="object",
 *             required={"id_materia", "dia", "hora_inicio", "hora_fin", "salon"},
 *             properties={
 *                 @OA\Property(property="id_materia", type="integer", example=1),
 *                 @OA\Property(property="dia", type="string", example="Lunes"),
 *                 @OA\Property(property="hora_inicio", type="string", format="time", example="08:00:00"),
 *                 @OA\Property(property="hora_fin", type="string", format="time", example="10:00:00"),
 *                 @OA\Property(property="salon", type="string", example="A101"),
 *                 @OA\Property(property="docente", type="string", example="Dr. Carlos López")
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Turno creado",
 *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
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
$motivo = trim((string) api_post_or_input($input, "motivo", ""));

if ($motivo === '') {
    api_json(["ok" => false, "mensaje" => "Debes ingresar el motivo del turno."], 422);
}

try {
    $conn = api_connect_db();
    $fechaSistema = CalendarioAcademico::obtenerFechaSistema($conn);

    $sqlUsuario = "
        SELECT
            id_usuario,
            nombre,
            correo,
            COALESCE(programa, '') AS programa,
            COALESCE(extension, COALESCE(extencion, 'Extensión Facatativá')) AS extension
        FROM usuarios
        WHERE id_usuario = ?
        LIMIT 1
    ";
    $stmtUsuario = $conn->prepare($sqlUsuario);
    $stmtUsuario->bind_param("i", $usuarioId);
    $stmtUsuario->execute();
    $perfil = $stmtUsuario->get_result()->fetch_assoc();

    if (!$perfil) {
        api_json(["ok" => false, "mensaje" => "No se encontró la información del estudiante."], 404);
    }

    $fechaDia = substr($fechaSistema, 0, 10);
    $consecutivoSql = "SELECT COALESCE(MAX(consecutivo_dia), 0) + 1 AS siguiente FROM turnos WHERE DATE(fecha_turno) = ?";
    $stmtConsecutivo = $conn->prepare($consecutivoSql);
    $stmtConsecutivo->bind_param('s', $fechaDia);
    $stmtConsecutivo->execute();
    $consecutivoRow = $stmtConsecutivo->get_result()->fetch_assoc();
    $consecutivoDia = (int) ($consecutivoRow['siguiente'] ?? 1);
    $codigoTurno = sprintf('T-%s-%03d', date('Ymd', strtotime($fechaSistema)), $consecutivoDia);

    $sql = "
        INSERT INTO turnos (
            usuario_id,
            codigo_turno,
            consecutivo_dia,
            nombre_estudiante,
            correo_estudiante,
            programa,
            extension,
            motivo,
            estado,
            fecha_turno
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', ?)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isissssss",
        $usuarioId,
        $codigoTurno,
        $consecutivoDia,
        $perfil['nombre'],
        $perfil['correo'],
        $perfil['programa'],
        $perfil['extension'],
        $motivo,
        $fechaSistema
    );

    if (!$stmt->execute()) {
        throw new Exception('No fue posible registrar el turno.');
    }

    api_json([
        'ok' => true,
        'mensaje' => 'Turno generado correctamente.',
        'turno' => [
            'id_turno' => (int) $stmt->insert_id,
            'codigo_turno' => $codigoTurno,
            'consecutivo_dia' => $consecutivoDia,
            'nombre_estudiante' => $perfil['nombre'],
            'correo_estudiante' => $perfil['correo'],
            'programa' => $perfil['programa'],
            'extension' => $perfil['extension'],
            'motivo' => $motivo,
            'estado' => 'pendiente',
            'fecha_turno' => $fechaSistema
        ]
    ]);
} catch (Throwable $e) {
    api_error($e);
}
