<?php
/**
 * @OA\Info(
 *   title="UniMatch Backend API",
 *   version="1.0.0",
 *   description="API REST para el backend de UniMatch: autenticación, materias, solicitudes, turnos y administración.",
 * )
 *
 * @OA\Server(
 *   url="http://localhost/api",
 *   description="Servidor local de desarrollo"
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT"
 * )
 *
 * @OA\Schema(
 *   schema="ErrorResponse",
 *   type="object",
 *   @OA\Property(property="ok", type="boolean", example=false),
 *   @OA\Property(property="mensaje", type="string", example="Descripción del error")
 * )
 *
 * @OA\Response(
 *   response="ErrorResponse",
 *   description="Respuesta de error genérico",
 *   @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 * )
 *
 * @OA\Schema(
 *   schema="UsuarioModel",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="nombre", type="string", example="Juan Pérez"),
 *   @OA\Property(property="correo", type="string", format="email", example="juan.perez@unimatch.edu.co"),
 *   @OA\Property(property="rol", type="string", example="estudiante"),
 *   @OA\Property(property="programa", type="string", example="Ingeniería de Sistemas"),
 *   @OA\Property(property="extension", type="string", example="Extensión Facatativá")
 * )
 *
 * @OA\Schema(
 *   schema="TurnoModel",
 *   type="object",
 *   @OA\Property(property="id_turno", type="integer", example=123),
 *   @OA\Property(property="codigo_turno", type="string", example="T-20260523-001"),
 *   @OA\Property(property="consecutivo_dia", type="integer", example=1),
 *   @OA\Property(property="nombre_estudiante", type="string", example="Juan Pérez"),
 *   @OA\Property(property="correo_estudiante", type="string", format="email", example="juan.perez@unimatch.edu.co"),
 *   @OA\Property(property="programa", type="string", example="Ingeniería de Sistemas"),
 *   @OA\Property(property="extension", type="string", example="Extensión Facatativá"),
 *   @OA\Property(property="motivo", type="string", example="Solicitud de asesoría académica"),
 *   @OA\Property(property="estado", type="string", example="pendiente"),
 *   @OA\Property(property="fecha_turno", type="string", format="date-time", example="2026-05-23 17:00:00"),
 *   @OA\Property(property="fecha_actualizacion", type="string", format="date-time", example="2026-05-23 17:10:00")
 * )
 *
 * @OA\Schema(
 *   schema="MateriaModel",
 *   type="object",
 *   @OA\Property(property="id_materia", type="integer", example=1),
 *   @OA\Property(property="materia", type="string", example="Matemáticas"),
 *   @OA\Property(property="id_grupo", type="integer", example=10),
 *   @OA\Property(property="cupos_totales", type="integer", example=30),
 *   @OA\Property(property="horario", type="string", example="Lunes 08:00-10:00 / Miércoles 08:00-10:00"),
 *   @OA\Property(property="cupos_disponibles", type="integer", example=12)
 * )
 *
 * @OA\Schema(
 *   schema="SolicitudModel",
 *   type="object",
 *   @OA\Property(property="id_solicitud", type="integer", example=55),
 *   @OA\Property(property="tipo_solicitud", type="string", example="cambio_grupo"),
 *   @OA\Property(property="grupo_origen", type="integer", example=3),
 *   @OA\Property(property="grupo_destino", type="integer", example=5),
 *   @OA\Property(property="materia_origen", type="integer", example=2),
 *   @OA\Property(property="materia_destino", type="integer", example=4),
 *   @OA\Property(property="estado", type="string", example="pendiente"),
 *   @OA\Property(property="fecha_solicitud", type="string", format="date-time", example="2026-05-23 18:00:00"),
 *   @OA\Property(property="nombre_materia_origen", type="string", example="Matemáticas"),
 *   @OA\Property(property="nombre_materia_destino", type="string", example="Física")
 * )
 *
 * @OA\Schema(
 *   schema="PerfilModel",
 *   type="object",
 *   @OA\Property(property="id_usuario", type="integer", example=1),
 *   @OA\Property(property="nombre", type="string", example="Juan Pérez"),
 *   @OA\Property(property="correo", type="string", format="email", example="juan.perez@unimatch.edu.co"),
 *   @OA\Property(property="rol", type="string", example="estudiante"),
 *   @OA\Property(property="programa", type="string", example="Ingeniería de Sistemas"),
 *   @OA\Property(property="extension", type="string", example="Extensión Facatativá"),
 *   @OA\Property(property="fecha_actual", type="string", format="date", example="2026-05-23")
 * )
 *
 * @OA\Schema(
 *   schema="ConfiguracionAcademicaModel",
 *   type="object",
 *   @OA\Property(property="fecha_sistema", type="string", format="date-time", example="2026-05-23 08:00:00"),
 *   @OA\Property(property="fecha_limite_inscripcion", type="string", format="date-time", example="2026-06-01 23:59:00"),
 *   @OA\Property(property="fecha_limite_cancelacion", type="string", format="date-time", example="2026-06-10 23:59:00"),
 *   @OA\Property(property="fecha_limite_permutas", type="string", format="date-time", example="2026-06-15 23:59:00"),
 *   @OA\Property(property="fecha_actualizacion", type="string", format="date-time", example="2026-05-23 08:00:00")
 * )
 *
 * @OA\Schema(
 *   schema="AdminResumenModel",
 *   type="object",
 *   @OA\Property(property="configuracion", ref="#/components/schemas/ConfiguracionAcademicaModel"),
 *   @OA\Property(property="rechazadas_por_vencimiento", type="integer", example=5),
 *   @OA\Property(property="total_solicitudes", type="integer", example=120),
 *   @OA\Property(property="solicitudes_por_estado", type="object", additionalProperties=@OA\Property(type="integer", example=10)),
 *   @OA\Property(property="solicitudes_por_resolucion", type="object", additionalProperties=@OA\Property(type="integer", example=3)),
 *   @OA\Property(property="total_turnos", type="integer", example=30),
 *   @OA\Property(property="turnos_pendientes", type="integer", example=15),
 *   @OA\Property(property="turnos_resueltos", type="integer", example=10),
 *   @OA\Property(property="turnos_rechazados", type="integer", example=5),
 *   @OA\Property(property="turnos", type="array", @OA\Items(ref="#/components/schemas/TurnoModel"))
 * )
 */
class SwaggerDocsDefinitions {}

/**
 * @OA\Post(
 *   path="/api/login.php",
 *   summary="Iniciar sesión",
 *   tags={"Auth"},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"correo","password"},
 *       @OA\Property(property="correo", type="string", format="email", example="usuario@unimatch.edu.co"),
 *       @OA\Property(property="password", type="string", format="password", example="Password123")
 *     )
 *   ),
 *   @OA\Response(
 *     response=200,
 *     description="Inicio de sesión exitoso",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
 *       @OA\Property(property="usuario", ref="#/components/schemas/UsuarioModel")
 *     )
 *   ),
 *   @OA\Response(response=401, ref="#/components/responses/ErrorResponse"),
 *   @OA\Response(response=403, ref="#/components/responses/ErrorResponse"),
 *   @OA\Response(response=429, ref="#/components/responses/ErrorResponse")
 * )
 */
function swaggerLogin() {}

/**
 * @OA\Get(
 *   path="/api/listar_materias.php",
 *   summary="Listar materias y grupos disponibles",
 *   tags={"Materias"},
 *   security={{"bearerAuth": {}}},
 *   @OA\Response(
 *     response=200,
 *     description="Listado de materias obtenidas correctamente",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="materias", type="array", @OA\Items(ref="#/components/schemas/MateriaModel"))
 *     )
 *   ),
 *   @OA\Response(response=401, ref="#/components/responses/ErrorResponse")
 * )
 */
function swaggerListarMaterias() {}

/**
 * @OA\Post(
 *   path="/api/crear_solicitud.php",
 *   summary="Crear solicitud de cancelación, cambio o nueva inscripción",
 *   tags={"Solicitudes"},
 *   security={{"bearerAuth": {}}},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"tipo_solicitud"},
 *       @OA\Property(property="tipo_solicitud", type="string", enum={"cancelacion","cambio_grupo","cambio_materia","nueva_inscripcion"}),
 *       @OA\Property(property="grupo_origen", type="integer", example=1),
 *       @OA\Property(property="grupo_destino", type="integer", example=2),
 *       @OA\Property(property="materia_origen", type="integer", example=10),
 *       @OA\Property(property="materia_destino", type="integer", example=11)
 *     )
 *   ),
 *   @OA\Response(response=200, description="Solicitud creada o validación exitosa",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="mensaje", type="string", example="Solicitud registrada correctamente.")
 *     )
 *   ),
 *   @OA\Response(response=422, ref="#/components/responses/ErrorResponse"),
 *   @OA\Response(response=409, ref="#/components/responses/ErrorResponse"),
 *   @OA\Response(response=401, ref="#/components/responses/ErrorResponse")
 * )
 */
function swaggerCrearSolicitud() {}

/**
 * @OA\Post(
 *   path="/api/crear_turno.php",
 *   summary="Crear un nuevo turno para atención académica",
 *   tags={"Turnos"},
 *   security={{"bearerAuth": {}}},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"motivo"},
 *       @OA\Property(property="motivo", type="string", example="Necesito asesoría sobre la inscripción de materias.")
 *     )
 *   ),
 *   @OA\Response(response=200, description="Turno generado correctamente",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="mensaje", type="string", example="Turno generado correctamente."),
 *       @OA\Property(property="turno", ref="#/components/schemas/TurnoModel")
 *     )
 *   ),
 *   @OA\Response(response=422, ref="#/components/responses/ErrorResponse"),
 *   @OA\Response(response=401, ref="#/components/responses/ErrorResponse")
 * )
 */
function swaggerCrearTurno() {}

/**
 * @OA\Get(
 *   path="/api/detalle_turno.php",
 *   summary="Obtener información detallada de un turno",
 *   tags={"Turnos"},
 *   security={{"bearerAuth": {}}},
 *   @OA\Parameter(name="id_turno", in="query", required=true, @OA\Schema(type="integer", example=1)),
 *   @OA\Response(response=200, description="Detalles del turno",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="turno", ref="#/components/schemas/TurnoModel")
 *     )
 *   ),
 *   @OA\Response(response=404, ref="#/components/responses/ErrorResponse"),
 *   @OA\Response(response=422, ref="#/components/responses/ErrorResponse")
 * )
 */
function swaggerDetalleTurno() {}

/**
 * @OA\Get(
 *   path="/api/listar_solicitudes.php",
 *   summary="Listar solicitudes del usuario",
 *   tags={"Solicitudes"},
 *   security={{"bearerAuth": {}}},
 *   @OA\Response(response=200, description="Solicitudes obtenidas",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="mensaje", type="string", example="Solicitudes cargadas correctamente."),
 *       @OA\Property(property="solicitudes", type="array", @OA\Items(ref="#/components/schemas/SolicitudModel")),
 *       @OA\Property(property="data", type="object", @OA\Property(property="total", type="integer", example=3))
 *     )
 *   )
 * )
 */
function swaggerListarSolicitudes() {}

/**
 * @OA\Get(
 *   path="/api/listar_turnos.php",
 *   summary="Listar turnos del usuario o administradores",
 *   tags={"Turnos"},
 *   security={{"bearerAuth": {}}},
 *   @OA\Response(response=200, description="Turnos obtenidos",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="turnos", type="array", @OA\Items(ref="#/components/schemas/TurnoModel")),
 *       @OA\Property(property="data", type="object",
 *         @OA\Property(property="total", type="integer", example=10),
 *         @OA\Property(property="estados", type="object", additionalProperties=@OA\Property(type="integer", example=4)),
 *         @OA\Property(property="scope", type="string", example="student")
 *       )
 *     )
 *   )
 * )
 */
function swaggerListarTurnos() {}

/**
 * @OA\Post(
 *   path="/api/matricular_materia.php",
 *   summary="Matricular materia directamente",
 *   tags={"Materias"},
 *   security={{"bearerAuth": {}}},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"grupo_id"},
 *       @OA\Property(property="grupo_id", type="integer", example=7)
 *     )
 *   ),
 *   @OA\Response(response=200, description="Matrícula completada",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="mensaje", type="string", example="Matrícula realizada correctamente."),
 *       @OA\Property(property="data", type="object",
 *         @OA\Property(property="grupo_id", type="integer", example=7),
 *         @OA\Property(property="materia", type="string", example="Física"),
 *         @OA\Property(property="fecha_sistema", type="string", format="date-time", example="2026-05-23 08:00:00")
 *       )
 *     )
 *   ),
 *   @OA\Response(response=409, ref="#/components/responses/ErrorResponse"),
 *   @OA\Response(response=422, ref="#/components/responses/ErrorResponse")
 * )
 */
function swaggerMatricularMateria() {}

/**
 * @OA\Get(
 *   path="/api/mis_materias.php",
 *   summary="Listar materias activas del usuario",
 *   tags={"Materias"},
 *   security={{"bearerAuth": {}}},
 *   @OA\Response(response=200, description="Materias activas cargadas",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="mensaje", type="string", example="Materias cargadas correctamente."),
 *       @OA\Property(property="materias", type="array", @OA\Items(ref="#/components/schemas/MateriaModel")),
 *       @OA\Property(property="data", type="object", @OA\Property(property="total", type="integer", example=5))
 *     )
 *   )
 * )
 */
function swaggerMisMaterias() {}

/**
 * @OA\Get(
 *   path="/api/opciones_solicitud.php",
 *   summary="Obtener opciones de solicitudes disponibles para el usuario",
 *   tags={"Solicitudes"},
 *   security={{"bearerAuth": {}}},
 *   @OA\Response(response=200, description="Opciones cargadas",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="inscritas", type="array", @OA\Items(type="object", @OA\Property(property="id_matricula", type="integer"), @OA\Property(property="id_materia", type="integer"), @OA\Property(property="materia", type="string"), @OA\Property(property="id_grupo", type="integer"), @OA\Property(property="etiqueta", type="string"))),
 *       @OA\Property(property="disponibles", type="array", @OA\Items(type="object", @OA\Property(property="id_materia", type="integer"), @OA\Property(property="materia", type="string"), @OA\Property(property="id_grupo", type="integer"), @OA\Property(property="etiqueta", type="string"), @OA\Property(property="cupos_disponibles", type="integer"))),
 *       @OA\Property(property="data", type="object", @OA\Property(property="inscritas_total", type="integer", example=2), @OA\Property(property="disponibles_total", type="integer", example=10))
 *     )
 *   )
 * )
 */
function swaggerOpcionesSolicitud() {}

/**
 * @OA\Get(
 *   path="/api/perfil_usuario.php",
 *   summary="Obtener perfil del usuario autenticado",
 *   tags={"Auth"},
 *   security={{"bearerAuth": {}}},
 *   @OA\Response(response=200, description="Perfil cargado",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="perfil", ref="#/components/schemas/PerfilModel")
 *     )
 *   )
 * )
 */
function swaggerPerfilUsuario() {}

/**
 * @OA\Post(
 *   path="/api/procesar_permutas.php",
 *   summary="Ejecutar el motor de permutas (solo administrador)",
 *   tags={"Administración"},
 *   security={{"bearerAuth": {}}},
 *   @OA\Response(response=200, description="Motor ejecutado correctamente",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="mensaje", type="string", example="Motor ejecutado correctamente."),
 *       @OA\Property(property="resultado", type="object", description="Resultado del procesamiento de permutas")
 *     )
 *   ),
 *   @OA\Response(response=403, ref="#/components/responses/ErrorResponse")
 * )
 */
function swaggerProcesarPermutas() {}

/**
 * @OA\Post(
 *   path="/api/reset_password.php",
 *   summary="Restablecer contraseña usando token de recuperación",
 *   tags={"Auth"},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"token","password","confirmacion"},
 *       @OA\Property(property="token", type="string", example="abcdef123456..."),
 *       @OA\Property(property="password", type="string", format="password", example="NuevaPassword123"),
 *       @OA\Property(property="confirmacion", type="string", format="password", example="NuevaPassword123")
 *     )
 *   ),
 *   @OA\Response(response=200, description="Contraseña restablecida",
 *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *   ),
 *   @OA\Response(response=422, ref="#/components/responses/ErrorResponse"),
 *   @OA\Response(response=400, ref="#/components/responses/ErrorResponse")
 * )
 */
function swaggerResetPassword() {}

/**
 * @OA\Post(
 *   path="/api/solicitar_recuperacion.php",
 *   summary="Solicitar recuperación de contraseña",
 *   tags={"Auth"},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"correo"},
 *       @OA\Property(property="correo", type="string", format="email", example="usuario@unimatch.edu.co")
 *     )
 *   ),
 *   @OA\Response(response=200, description="Solicitud enviada",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="mensaje", type="string", example="Si el correo está registrado, enviaremos un enlace de recuperación.")
 *     )
 *   ),
 *   @OA\Response(response=422, ref="#/components/responses/ErrorResponse")
 * )
 */
function swaggerSolicitarRecuperacion() {}

/**
 * @OA\Get(
 *   path="/api/admin_configuracion_academica.php",
 *   summary="Obtener configuración académica (administrador)",
 *   tags={"Administración"},
 *   security={{"bearerAuth": {}}},
 *   @OA\Response(response=200, description="Configuración cargada",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="configuracion", ref="#/components/schemas/ConfiguracionAcademicaModel")
 *     )
 *   ),
 *   @OA\Response(response=403, ref="#/components/responses/ErrorResponse")
 * )
 */
function swaggerAdminConfiguracionGet() {}

/**
 * @OA\Post(
 *   path="/api/admin_configuracion_academica.php",
 *   summary="Actualizar configuración académica (administrador)",
 *   tags={"Administración"},
 *   security={{"bearerAuth": {}}},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       @OA\Property(property="fecha_sistema", type="string", format="date-time", example="2026-05-23 08:00:00"),
 *       @OA\Property(property="fecha_limite_inscripcion", type="string", format="date-time", example="2026-06-01 23:59:00"),
 *       @OA\Property(property="fecha_limite_cancelacion", type="string", format="date-time", example="2026-06-10 23:59:00"),
 *       @OA\Property(property="fecha_limite_permutas", type="string", format="date-time", example="2026-06-15 23:59:00")
 *     )
 *   ),
 *   @OA\Response(response=200, description="Configuración actualizada",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="mensaje", type="string", example="Configuración académica actualizada correctamente."),
 *       @OA\Property(property="configuracion", ref="#/components/schemas/ConfiguracionAcademicaModel")
 *     )
 *   ),
 *   @OA\Response(response=403, ref="#/components/responses/ErrorResponse"),
 *   @OA\Response(response=422, ref="#/components/responses/ErrorResponse")
 * )
 */
function swaggerAdminConfiguracionPost() {}

/**
 * @OA\Get(
 *   path="/api/admin_resumen.php",
 *   summary="Resumen administrativo de solicitudes y turnos",
 *   tags={"Administración"},
 *   security={{"bearerAuth": {}}},
 *   @OA\Response(response=200, description="Resumen administrativo",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="resumen", ref="#/components/schemas/AdminResumenModel")
 *     )
 *   )
 * )
 */
function swaggerAdminResumen() {}

/**
 * @OA\Get(
 *   path="/api/admin_solicitudes.php",
 *   summary="Listar todas las solicitudes del sistema (administrador)",
 *   tags={"Administración"},
 *   security={{"bearerAuth": {}}},
 *   @OA\Response(response=200, description="Solicitudes administrativas",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="solicitudes", type="array", @OA\Items(ref="#/components/schemas/SolicitudModel")),
 *       @OA\Property(property="data", type="object", @OA\Property(property="total", type="integer", example=50))
 *     )
 *   )
 * )
 */
function swaggerAdminSolicitudes() {}

/**
 * @OA\Post(
 *   path="/api/cancelar_materia.php",
 *   summary="Cancelar materia activa del usuario",
 *   tags={"Materias"},
 *   security={{"bearerAuth": {}}},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"grupo_id"},
 *       @OA\Property(property="grupo_id", type="integer", example=7)
 *     )
 *   ),
 *   @OA\Response(response=200, description="Cancelación de materia exitosa",
 *     @OA\JsonContent(
 *       @OA\Property(property="ok", type="boolean", example=true),
 *       @OA\Property(property="mensaje", type="string", example="Matrícula cancelada correctamente."),
 *       @OA\Property(property="data", type="object", @OA\Property(property="grupo_id", type="integer", example=7), @OA\Property(property="materia", type="string", example="Física"))
 *     )
 *   ),
 *   @OA\Response(response=422, ref="#/components/responses/ErrorResponse"),
 *   @OA\Response(response=409, ref="#/components/responses/ErrorResponse")
 * )
 */
function swaggerCancelarMateria() {}

/**
 * @OA\Post(
 *   path="/api/actualizar_turno.php",
 *   summary="Actualizar estado de un turno (administrador)",
 *   tags={"Turnos"},
 *   security={{"bearerAuth": {}}},
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"id_turno","estado"},
 *       @OA\Property(property="id_turno", type="integer", example=1),
 *       @OA\Property(property="estado", type="string", enum={"pendiente","resuelta","rechazada"})
 *     )
 *   ),
 *   @OA\Response(response=200, description="Estado del turno actualizado",
 *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *   ),
 *   @OA\Response(response=403, ref="#/components/responses/ErrorResponse"),
 *   @OA\Response(response=422, ref="#/components/responses/ErrorResponse")
 * )
 */
function swaggerActualizarTurno() {}

/**
 * @OA\Get(
 *   path="/api/test_db.php",
 *   summary="Verificar conectividad con la base de datos",
 *   tags={"Sistema"},
 *   @OA\Response(response=200, description="Base de datos accesible", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
function swaggerTestDb() {}
