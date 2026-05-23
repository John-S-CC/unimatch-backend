<?php
/**
 * @OA\Post(
 *     path="/api/admin_configuracion_academica.php",
 *     summary="Configurar calendario académico (Admin)",
 *     description="Permite configurar períodos, fechas límite y parmetros académicos (Solo Admin)",
 *     tags={"Administrador"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Configuración académica",
 *         @OA\JsonContent(
 *             type="object",
 *             properties={
 *                 @OA\Property(property="periodo_actual", type="string", example="2024-1"),
 *                 @OA\Property(property="fecha_inicio_clases", type="string", format="date", example="2024-01-15"),
 *                 @OA\Property(property="fecha_fin_clases", type="string", format="date", example="2024-05-30"),
 *                 @OA\Property(property="fecha_limite_inscripcion", type="string", format="date", example="2024-01-22")
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Configuración actualizada",
 *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
 *     )
 * )
 */
require_once __DIR__ . "/_common.php";
api_set_common_headers("GET, POST, OPTIONS");
api_handle_preflight();

require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../middleware/AuthMiddleware.php";
require_once __DIR__ . "/../servicios/CalendarioAcademico.php";

$usuario = AuthMiddleware::verificar();
if (!api_user_is_admin($usuario)) {
    api_json(['ok' => false, 'mensaje' => 'No tienes permisos para gestionar la configuración académica.'], 403);
}

try {
    $conn = api_connect_db();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        CalendarioAcademico::sincronizarSolicitudesVencidas($conn);
        api_json(['ok' => true, 'configuracion' => CalendarioAcademico::obtenerConfiguracion($conn)]);
    }

    api_require_method("POST");
    $input = api_read_input();
    $fechaSistema = trim((string) api_post_or_input($input, 'fecha_sistema', ''));
    $fechaInscripcion = trim((string) api_post_or_input($input, 'fecha_limite_inscripcion', ''));
    $fechaCancelacion = trim((string) api_post_or_input($input, 'fecha_limite_cancelacion', ''));
    $fechaPermutas = trim((string) api_post_or_input($input, 'fecha_limite_permutas', ''));

    foreach ([$fechaSistema, $fechaInscripcion, $fechaCancelacion, $fechaPermutas] as $valor) {
        if ($valor !== '' && strtotime($valor) === false) {
            api_json(['ok' => false, 'mensaje' => 'Una de las fechas enviadas no tiene un formato válido.'], 422);
        }
    }

    $fechaSistema = $fechaSistema !== '' ? date('Y-m-d H:i:s', strtotime($fechaSistema)) : date('Y-m-d H:i:s');
    $fechaInscripcion = $fechaInscripcion !== '' ? date('Y-m-d H:i:s', strtotime($fechaInscripcion)) : null;
    $fechaCancelacion = $fechaCancelacion !== '' ? date('Y-m-d H:i:s', strtotime($fechaCancelacion)) : null;
    $fechaPermutas = $fechaPermutas !== '' ? date('Y-m-d H:i:s', strtotime($fechaPermutas)) : null;

    $conn->begin_transaction();
    $existe = $conn->query("SELECT id_config FROM configuracion_academica ORDER BY id_config ASC LIMIT 1")->fetch_assoc();
    if ($existe) {
        $sql = "
            UPDATE configuracion_academica
            SET fecha_sistema = ?,
                fecha_limite_inscripcion = ?,
                fecha_limite_cancelacion = ?,
                fecha_limite_permutas = ?,
                fecha_actualizacion = ?
            WHERE id_config = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssi', $fechaSistema, $fechaInscripcion, $fechaCancelacion, $fechaPermutas, $fechaSistema, $existe['id_config']);
        $stmt->execute();
    } else {
        $sql = "
            INSERT INTO configuracion_academica (
                fecha_sistema,
                fecha_limite_inscripcion,
                fecha_limite_cancelacion,
                fecha_limite_permutas,
                fecha_actualizacion
            ) VALUES (?, ?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss', $fechaSistema, $fechaInscripcion, $fechaCancelacion, $fechaPermutas, $fechaSistema);
        $stmt->execute();
    }

    $rechazadas = CalendarioAcademico::sincronizarSolicitudesVencidas($conn);
    $conn->commit();

    api_json([
        'ok' => true,
        'mensaje' => 'Configuración académica actualizada correctamente.',
        'rechazadas_por_vencimiento' => $rechazadas,
        'configuracion' => CalendarioAcademico::obtenerConfiguracion($conn)
    ]);
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
    }
    api_error($e);
}
