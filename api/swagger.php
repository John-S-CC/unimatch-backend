<?php
/**
 * @OA\OpenApi(
 *     openapi="3.0.0",
 *     @OA\Info(
 *         title="UNIMATCH Backend API",
 *         description="API Backend para la gestión académica de UNIMATCH - Universidad de Cundinamarca",
 *         version="1.0.0",
 *         @OA\Contact(
 *             name="UNIMATCH Support",
 *             email="soporte@unimatch.com"
 *         ),
 *         @OA\License(
 *             name="Proprietary"
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

// ============ ESQUEMAS GLOBALES ============

/**
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     properties={
 *         @OA\Property(property="ok", type="boolean", example=false),
 *         @OA\Property(property="error", type="string", description="Mensaje de error"),
 *         @OA\Property(property="mensaje", type="string", description="Mensaje descriptivo del error")
 *     }
 * )
 */

/**
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     properties={
 *         @OA\Property(property="ok", type="boolean", example=true),
 *         @OA\Property(property="mensaje", type="string", description="Mensaje de éxito")
 *     }
 * )
 */

/**
 * @OA\Schema(
 *     schema="Usuario",
 *     type="object",
 *     properties={
 *         @OA\Property(property="id_usuario", type="integer", example=1),
 *         @OA\Property(property="email", type="string", format="email", example="usuario@unimatch.edu.co"),
 *         @OA\Property(property="correo", type="string", format="email", example="usuario@unimatch.edu.co"),
 *         @OA\Property(property="nombre", type="string", example="Juan Pérez"),
 *         @OA\Property(property="rol", type="string", enum={"estudiante", "profesor", "administrador"}, example="estudiante"),
 *         @OA\Property(property="programa", type="string", example="Ingeniería de Sistemas"),
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
 *         @OA\Property(property="descripcion", type="string", example="Introducción al cálculo diferencial y aplicaciones"),
 *         @OA\Property(property="semestre", type="integer", example=1)
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
 *         @OA\Property(property="docente", type="string", example="Dr. Carlos López"),
 *         @OA\Property(property="cupos_disponibles", type="integer", example=35),
 *         @OA\Property(property="cupos_totales", type="integer", example=40)
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
 *         @OA\Property(property="tipo", type="string", enum={"permuta", "cambio_directo", "cancelacion", "cambio_grupo"}, example="permuta"),
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

// ============ RUTAS DE AUTENTICACIÓN ============

/**
 * @OA\Post(
 *     path="/api/login.php",
 *     summary="Autenticar usuario",
 *     description="Autentica un usuario con correo y contraseña, retorna token JWT",
 *     tags={"Autenticación"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Credenciales del usuario",
 *         @OA\JsonContent(
 *             type="object",
 *             required={"correo", "password"},
 *             properties={
 *                 @OA\Property(property="correo", type="string", format="email", example="usuario@unimatch.edu.co"),
 *                 @OA\Property(property="password", type="string", format="password", example="contraseña123")
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login exitoso",
 *         @OA\JsonContent(
 *             type="object",
 *             properties={
 *                 @OA\Property(property="ok", type="boolean", example=true),
 *                 @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
 *                 @OA\Property(property="usuario", ref="#/components/schemas/Usuario")
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Credenciales inválidas"
 *     ),
 *     @OA\Response(
 *         response=429,
 *         description="Demasiados intentos de login"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/api/solicitar_recuperacion.php",
 *     summary="Solicitar recuperación de contraseña",
 *     description="Envía un correo con enlace de recuperación de contraseña al usuario",
 *     tags={"Autenticación"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"correo"},
 *             properties={
 *                 @OA\Property(property="correo", type="string", format="email", example="usuario@unimatch.edu.co")
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Correo de recuperación enviado"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Usuario no encontrado"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/api/reset_password.php",
 *     summary="Restablecer contraseña",
 *     description="Permite restablecer la contraseña usando un token de recuperación",
 *     tags={"Autenticación"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"token", "nueva_password"},
 *             properties={
 *                 @OA\Property(property="token", type="string"),
 *                 @OA\Property(property="nueva_password", type="string", format="password")
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Contraseña restablecida"
 *     )
 * )
 */

// ============ RUTAS DE MATERIAS ============

/**
 * @OA\Get(
 *     path="/api/listar_materias.php",
 *     summary="Listar todas las materias",
 *     description="Obtiene el listado completo de materias disponibles",
 *     tags={"Materias"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Lista de materias obtenida correctamente",
 *         @OA\JsonContent(
 *             type="object",
 *             properties={
 *                 @OA\Property(property="ok", type="boolean"),
 *                 @OA\Property(property="materias", type="array", items={"$ref"="#/components/schemas/Materia"})
 *             }
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/api/mis_materias.php",
 *     summary="Obtener mis materias matriculadas",
 *     description="Obtiene el listado de materias en las que el usuario está matriculado",
 *     tags={"Materias"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Lista de materias del usuario"
 *     )
 * )
 */

// ============ RUTAS DE TURNOS ============

/**
 * @OA\Get(
 *     path="/api/listar_turnos.php",
 *     summary="Listar todos los turnos",
 *     description="Obtiene el listado completo de turnos disponibles",
 *     tags={"Turnos"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Lista de turnos"
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/api/detalle_turno.php",
 *     summary="Obtener detalles de un turno",
 *     tags={"Turnos"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id_turno",
 *         in="query",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Detalles del turno"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/api/crear_turno.php",
 *     summary="Crear nuevo turno (Admin)",
 *     tags={"Turnos"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"id_materia", "dia", "hora_inicio", "hora_fin", "salon"},
 *             properties={
 *                 @OA\Property(property="id_materia", type="integer"),
 *                 @OA\Property(property="dia", type="string"),
 *                 @OA\Property(property="hora_inicio", type="string", format="time"),
 *                 @OA\Property(property="hora_fin", type="string", format="time"),
 *                 @OA\Property(property="salon", type="string"),
 *                 @OA\Property(property="docente", type="string")
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Turno creado"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/api/actualizar_turno.php",
 *     summary="Actualizar turno de una materia",
 *     tags={"Turnos"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"id_matricula", "id_turno_nuevo"},
 *             properties={
 *                 @OA\Property(property="id_matricula", type="integer"),
 *                 @OA\Property(property="id_turno_nuevo", type="integer")
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Turno actualizado"
 *     )
 * )
 */

// ============ RUTAS DE MATRÍCULAS ============

/**
 * @OA\Post(
 *     path="/api/matricular_materia.php",
 *     summary="Matricular una materia",
 *     description="Permite a un estudiante matricularse en una materia y turno específico",
 *     tags={"Matrículas"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"id_materia", "id_turno"},
 *             properties={
 *                 @OA\Property(property="id_materia", type="integer", example=1),
 *                 @OA\Property(property="id_turno", type="integer", example=5)
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Matrícula realizada exitosamente"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/api/cancelar_materia.php",
 *     summary="Cancelar matrícula de una materia",
 *     tags={"Matrículas"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"id_matricula"},
 *             properties={
 *                 @OA\Property(property="id_matricula", type="integer")
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Matrícula cancelada"
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/api/creditos_estudiante.php",
 *     summary="Obtener créditos del estudiante",
 *     tags={"Matrículas"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Información de créditos"
 *     )
 * )
 */

// ============ RUTAS DE SOLICITUDES ============

/**
 * @OA\Post(
 *     path="/api/crear_solicitud.php",
 *     summary="Crear solicitud de cambio académico",
 *     description="Permite crear solicitudes de cancelación, cambio de grupo, cambio de materia o nueva inscripción",
 *     tags={"Solicitudes"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"tipo_solicitud"},
 *             properties={
 *                 @OA\Property(property="tipo_solicitud", type="string", enum={"cancelacion", "cambio_grupo", "cambio_materia", "nueva_inscripcion"}),
 *                 @OA\Property(property="grupo_origen", type="integer"),
 *                 @OA\Property(property="grupo_destino", type="integer"),
 *                 @OA\Property(property="materia_origen", type="integer"),
 *                 @OA\Property(property="materia_destino", type="integer")
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Solicitud creada exitosamente"
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/api/listar_solicitudes.php",
 *     summary="Listar solicitudes del usuario",
 *     tags={"Solicitudes"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Lista de solicitudes"
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/api/opciones_solicitud.php",
 *     summary="Obtener opciones disponibles para solicitud",
 *     tags={"Solicitudes"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Opciones disponibles"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/api/procesar_permutas.php",
 *     summary="Procesar permuta entre estudiantes",
 *     tags={"Solicitudes"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"id_solicitud_1", "id_solicitud_2"},
 *             properties={
 *                 @OA\Property(property="id_solicitud_1", type="integer"),
 *                 @OA\Property(property="id_solicitud_2", type="integer")
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Permuta procesada"
 *     )
 * )
 */

// ============ RUTAS DE USUARIO ============

/**
 * @OA\Get(
 *     path="/api/perfil_usuario.php",
 *     summary="Obtener perfil del usuario",
 *     tags={"Usuario"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Datos del perfil"
 *     )
 * )
 */

// ============ RUTAS ADMINISTRATIVAS ============

/**
 * @OA\Get(
 *     path="/api/admin_resumen.php",
 *     summary="Resumen administrativo (Admin)",
 *     tags={"Administrador"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Resumen administrativo"
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/api/admin_solicitudes.php",
 *     summary="Listar todas las solicitudes (Admin)",
 *     tags={"Administrador"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="estado",
 *         in="query",
 *         @OA\Schema(type="string", enum={"pendiente", "aprobada", "rechazada"})
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Lista de solicitudes"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/api/admin_configuracion_academica.php",
 *     summary="Configurar calendario académico (Admin)",
 *     tags={"Administrador"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             properties={
 *                 @OA\Property(property="periodo_actual", type="string"),
 *                 @OA\Property(property="fecha_inicio_clases", type="string", format="date"),
 *                 @OA\Property(property="fecha_fin_clases", type="string", format="date"),
 *                 @OA\Property(property="fecha_limite_inscripcion", type="string", format="date")
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Configuración actualizada"
 *     )
 * )
 */

?>
