<?php
/**
 * @OA\Post(
 *     path="/api/procesar_permutas.php",
 *     summary="Procesar permuta entre estudiantes",
 *     description="Procesa una solicitud de permuta de materias/turnos entre dos estudiantes",
 *     tags={"Permutas"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Datos de la permuta",
 *         @OA\JsonContent(
 *             type="object",
 *             required={"id_solicitud_1", "id_solicitud_2"},
 *             properties={
 *                 @OA\Property(property="id_solicitud_1", type="integer", example=10),
 *                 @OA\Property(property="id_solicitud_2", type="integer", example=11)
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Permuta procesada exitosamente",
 *         @OA\JsonContent(
 *             type="object",
 *             properties={
 *                 @OA\Property(property="ok", type="boolean", example=true),
 *                 @OA\Property(property="mensaje", type="string", example="Permuta realizada")
 *             }
 *         )
 *     )
 * )
 */
require_once __DIR__ . "/_common.php";
require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../middleware/AuthMiddleware.php";
require_once __DIR__ . "/../servicios/MotorPermutas.php";

api_set_common_headers("POST, OPTIONS");
api_handle_preflight();
api_require_method("POST");

$usuario = AuthMiddleware::verificar();
if (!api_user_is_admin($usuario)) {
    api_json(['ok' => false, 'mensaje' => 'No autorizado.'], 403);
}

try {
    $conn = api_connect_db();
    $resultado = MotorPermutas::procesar($conn);

    api_json([
        'ok' => true,
        'mensaje' => 'Motor ejecutado correctamente.',
        'resultado' => $resultado
    ]);
} catch (Throwable $e) {
    api_error($e, 'No fue posible ejecutar el motor de permutas.');
}
