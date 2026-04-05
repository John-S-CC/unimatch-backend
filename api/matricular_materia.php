<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once "../configuracion/database.php";
require_once "../middleware/AuthMiddleware.php";
require_once "../servicios/validadores/ValidadorCupos.php";
require_once "../servicios/validadores/ValidadorHorarios.php";

$usuario = AuthMiddleware::verificar();
$usuarioId = (int) $usuario->id;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Método no permitido."
    ]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

$grupoId = 0;

if (isset($_POST["grupo_id"])) {
    $grupoId = (int) $_POST["grupo_id"];
} elseif (is_array($input) && isset($input["grupo_id"])) {
    $grupoId = (int) $input["grupo_id"];
}

if ($grupoId <= 0) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Datos incompletos o inválidos. No llegó el grupo_id."
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();

    if (!$conn) {
        throw new Exception("No fue posible conectar con la base de datos.");
    }

    $sqlGrupo = "
        SELECT g.id_grupo, g.id_materia
        FROM grupos g
        WHERE g.id_grupo = ?
    ";
    $stmtGrupo = $conn->prepare($sqlGrupo);
    $stmtGrupo->bind_param("i", $grupoId);
    $stmtGrupo->execute();
    $grupo = $stmtGrupo->get_result()->fetch_assoc();

    if (!$grupo) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "El grupo no existe."
        ]);
        exit;
    }

    $sqlYaEnGrupo = "
        SELECT id_matricula
        FROM matriculas
        WHERE usuario_id = ? AND grupo_id = ? AND estado = 'activa'
    ";
    $stmtYaEnGrupo = $conn->prepare($sqlYaEnGrupo);
    $stmtYaEnGrupo->bind_param("ii", $usuarioId, $grupoId);
    $stmtYaEnGrupo->execute();

    if ($stmtYaEnGrupo->get_result()->num_rows > 0) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "Ya estás matriculado en este grupo."
        ]);
        exit;
    }

    $sqlMismaMateria = "
        SELECT m.id_matricula
        FROM matriculas m
        INNER JOIN grupos g ON g.id_grupo = m.grupo_id
        WHERE m.usuario_id = ?
          AND m.estado = 'activa'
          AND g.id_materia = ?
    ";
    $stmtMismaMateria = $conn->prepare($sqlMismaMateria);
    $stmtMismaMateria->bind_param("ii", $usuarioId, $grupo["id_materia"]);
    $stmtMismaMateria->execute();

    if ($stmtMismaMateria->get_result()->num_rows > 0) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "Ya tienes una matrícula activa en esta materia."
        ]);
        exit;
    }

    if (!ValidadorCupos::hayCupo($conn, $grupoId)) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "No hay cupos disponibles para este grupo."
        ]);
        exit;
    }

    if (method_exists("ValidadorHorarios", "hayCruce") && ValidadorHorarios::hayCruce($conn, $usuarioId, $grupoId)) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "Existe cruce de horario con otra materia matriculada."
        ]);
        exit;
    }

    $sqlInsert = "
        INSERT INTO matriculas (usuario_id, grupo_id, fecha_matricula, estado)
        VALUES (?, ?, NOW(), 'activa')
    ";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("ii", $usuarioId, $grupoId);

    if (!$stmtInsert->execute()) {
        throw new Exception("No se pudo registrar la matrícula.");
    }

    echo json_encode([
        "ok" => true,
        "mensaje" => "Matrícula realizada correctamente."
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error del servidor: " . $e->getMessage()
    ]);
}