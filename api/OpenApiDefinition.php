<?php
/**
 * @OA\OpenApi(
 *     openapi="3.0.0",
 *     @OA\Info(
 *         title="UNIMATCH Backend API",
 *         description="API Backend para la gestión académica de UNIMATCH",
 *         version="1.0.0",
 *         @OA\Contact(
 *             name="UNIMATCH Support",
 *             email="soporte@unimatch.com"
 *         ),
 *         @OA\License(
 *             name="Proprietary",
 *             url="https://unimatch.com/license"
 *         )
 *     ),
 *     @OA\Server(
 *         url="https://unimatch-backend-fid5.onrender.com",
 *         description="Production Server"
 *     ),
 *     @OA\Server(
 *         url="http://localhost",
 *         description="Local Development Server"
 *     )
 * )
 */

// ============ ESQUEMAS COMPARTIDOS ============

/**
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     title="Error Response",
 *     type="object",
 *     properties={
 *         @OA\Property(property="error", type="string", description="Mensaje de error"),
 *         @OA\Property(property="details", type="string", description="Detalles adicionales del error")
 *     }
 * )
 */

/**
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     title="Success Response",
 *     type="object",
 *     properties={
 *         @OA\Property(property="status", type="string", example="success"),
 *         @OA\Property(property="message", type="string")
 *     }
 * )
 */

/**
 * @OA\Schema(
 *     schema="Usuario",
 *     type="object",
 *     properties={
 *         @OA\Property(property="id_usuario", type="integer", example=1),
 *         @OA\Property(property="email", type="string", format="email", example="usuario@unimatch.com"),
 *         @OA\Property(property="nombre", type="string", example="Juan Pérez"),
 *         @OA\Property(property="rol", type="string", enum={"estudiante", "profesor", "administrador"}, example="estudiante"),
 *         @OA\Property(property="estado", type="string", enum={"activo", "inactivo", "suspendido"}, example="activo")
 *     }
 * )
 */

/**
 * @OA\Schema(
 *     schema="Materia",
 *     type="object",
 *     properties={
 *         @OA\Property(property="id_materia", type="integer", example=1),
 *         @OA\Property(property="codigo", type="string", example="MAT101"),
 *         @OA\Property(property="nombre", type="string", example="Cálculo I"),
 *         @OA\Property(property="creditos", type="integer", example=4),
 *         @OA\Property(property="descripcion", type="string", example="Introducción al cálculo diferencial")
 *     }
 * )
 */

/**
 * @OA\Schema(
 *     schema="Turno",
 *     type="object",
 *     properties={
 *         @OA\Property(property="id_turno", type="integer", example=1),
 *         @OA\Property(property="id_materia", type="integer", example=1),
 *         @OA\Property(property="dia", type="string", example="Lunes"),
 *         @OA\Property(property="hora_inicio", type="string", format="time", example="08:00:00"),
 *         @OA\Property(property="hora_fin", type="string", format="time", example="10:00:00"),
 *         @OA\Property(property="salon", type="string", example="A101"),
 *         @OA\Property(property="docente", type="string", example="Dr. Carlos López")
 *     }
 * )
 */

/**
 * @OA\Schema(
 *     schema="Solicitud",
 *     type="object",
 *     properties={
 *         @OA\Property(property="id_solicitud", type="integer", example=1),
 *         @OA\Property(property="id_usuario", type="integer", example=1),
 *         @OA\Property(property="tipo", type="string", enum={"permuta", "cambio_directo", "cancelacion"}, example="permuta"),
 *         @OA\Property(property="estado", type="string", enum={"pendiente", "aprobada", "rechazada", "cancelada"}, example="pendiente"),
 *         @OA\Property(property="fecha_creacion", type="string", format="date-time"),
 *         @OA\Property(property="descripcion", type="string", example="Solicitud de cambio de turno")
 *     }
 * )
 */

/**
 * @OA\Schema(
 *     schema="Matricula",
 *     type="object",
 *     properties={
 *         @OA\Property(property="id_matricula", type="integer", example=1),
 *         @OA\Property(property="id_usuario", type="integer", example=1),
 *         @OA\Property(property="id_materia", type="integer", example=1),
 *         @OA\Property(property="id_turno", type="integer", example=1),
 *         @OA\Property(property="estado", type="string", enum={"activa", "cancelada", "completada"}, example="activa"),
 *         @OA\Property(property="fecha_matricula", type="string", format="date-time")
 *     }
 * )
 */

/**
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Bearer token de autenticación JWT",
 *     name="bearerAuth",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

?>
