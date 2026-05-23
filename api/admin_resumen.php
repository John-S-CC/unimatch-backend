<?php
/**
 * @OA\Get(
 *     path="/api/admin_resumen.php",
 *     summary="Resumen administrativo",
 *     description="Obtiene un resumen de las solicitudes y cambios pendientes (Solo Admin)",
 *     tags={"Administrador"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Resumen administrativo",
 *         @OA\JsonContent(
 *             type="object",
 *             properties={
 *                 @OA\Property(property="ok", type="boolean", example=true),
 *                 @OA\Property(property="solicitudes_pendientes", type="integer", example=15),
 *                 @OA\Property(property="usuarios_activos", type="integer", example=487),
 *                 @OA\Property(property="materias_total", type="integer", example=52)
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Acceso denegado - No es administrador",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
require_once __DIR__ . "/_common.php";
api_set_common_headers("GET, OPTIONS");
api_handle_preflight();
api_require_method("GET");

require_once __DIR__ . "/../configuracion/database.php";
require_once __DIR__ . "/../middleware/AuthMiddleware.php";
require_once __DIR__ . "/../servicios/CalendarioAcademico.php";

$usuario = AuthMiddleware::verificar();
if (!api_user_is_admin($usuario)) {
    api_json(['ok' => false, 'mensaje' => 'No tienes permisos para acceder al panel administrativo.'], 403);
}

try {
    $conn = api_connect_db();
    $rechazadas = CalendarioAcademico::sincronizarSolicitudesVencidas($conn);
    $config = CalendarioAcademico::obtenerConfiguracion($conn);

    $totalSolicitudes = (int) (($conn->query("SELECT COUNT(*) AS total FROM solicitudes")->fetch_assoc()['total']) ?? 0);
    $totalesPorEstado = [];
    $rsEstados = $conn->query("SELECT estado, COUNT(*) AS total FROM solicitudes GROUP BY estado");
    while ($row = $rsEstados->fetch_assoc()) {
        $totalesPorEstado[strtolower((string) $row['estado'])] = (int) $row['total'];
    }

    $resumenResolucion = [
        'directa' => 0,
        'permuta' => 0,
        'vencimiento' => 0,
        'manual' => 0,
        'sin_definir' => 0,
    ];
    $rsResolucion = $conn->query("SELECT COALESCE(canal_resolucion, 'sin_definir') AS canal, COUNT(*) AS total FROM solicitudes GROUP BY COALESCE(canal_resolucion, 'sin_definir')");
    while ($row = $rsResolucion->fetch_assoc()) {
        $resumenResolucion[strtolower((string) $row['canal'])] = (int) $row['total'];
    }

    $turnos = [];
    $rsTurnos = $conn->query("SELECT id_turno, codigo_turno, nombre_estudiante, programa, extension, motivo, estado, DATE_FORMAT(fecha_turno, '%Y-%m-%d %H:%i:%s') AS fecha_turno FROM turnos ORDER BY fecha_turno ASC, id_turno ASC");
    while ($row = $rsTurnos->fetch_assoc()) {
        $turnos[] = $row;
    }

    api_json([
        'ok' => true,
        'resumen' => [
            'configuracion' => $config,
            'rechazadas_por_vencimiento' => $rechazadas,
            'total_solicitudes' => $totalSolicitudes,
            'solicitudes_por_estado' => $totalesPorEstado,
            'solicitudes_por_resolucion' => $resumenResolucion,
            'total_turnos' => count($turnos),
            'turnos_pendientes' => count(array_filter($turnos, fn($item) => strtolower((string) ($item['estado'] ?? '')) === 'pendiente')),
            'turnos_resueltos' => count(array_filter($turnos, fn($item) => strtolower((string) ($item['estado'] ?? '')) === 'resuelta')),
            'turnos_rechazados' => count(array_filter($turnos, fn($item) => strtolower((string) ($item['estado'] ?? '')) === 'rechazada')),
            'turnos' => $turnos
        ]
    ]);
} catch (Throwable $e) {
    api_error($e);
}
