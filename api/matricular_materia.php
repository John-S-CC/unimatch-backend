<?php
header("Content-Type: application/json; charset=UTF-8");

require_once "../configuracion/database.php";
require_once "../servicios/validadores/ValidadorCupos.php";
require_once "../servicios/validadores/ValidadorHorarios.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Método no permitido."
    ]);
    exit;
}

$usuarioId = isset($_POST["usuario_id"]) ? (int) $_POST["usuario_id"] : 0;
$grupoId = isset($_POST["grupo_id"]) ? (int) $_POST["grupo_id"] : 0;

if ($usuarioId <= 0 || $grupoId <= 0) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Datos incompletos o inválidos."
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();

    if (!$conn) {
        throw new Exception("No fue posible conectar con la base de datos.");
    }

    // Verificar si el usuario existe
    $sqlUsuario = "SELECT id_usuario FROM usuarios WHERE id_usuario = ?";
    $stmtUsuario = $conn->prepare($sqlUsuario);
    $stmtUsuario->bind_param("i", $usuarioId);
    $stmtUsuario->execute();
    $resUsuario = $stmtUsuario->get_result();

    if ($resUsuario->num_rows === 0) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "El usuario no existe."
        ]);
        exit;
    }

    // Verificar si el grupo existe
    $sqlGrupo = "SELECT id_grupo FROM grupos WHERE id_grupo = ?";
    $stmtGrupo = $conn->prepare($sqlGrupo);
    $stmtGrupo->bind_param("i", $grupoId);
    $stmtGrupo->execute();
    $resGrupo = $stmtGrupo->get_result();

    if ($resGrupo->num_rows === 0) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "El grupo no existe."
        ]);
        exit;
    }

    // Verificar si ya está matriculado activamente
    $sqlDuplicado = "SELECT id_matricula 
                     FROM matriculas 
                     WHERE usuario_id = ? AND grupo_id = ? AND estado = 'activa'";
    $stmtDuplicado = $conn->prepare($sqlDuplicado);
    $stmtDuplicado->bind_param("ii", $usuarioId, $grupoId);
    $stmtDuplicado->execute();
    $resDuplicado = $stmtDuplicado->get_result();

    if ($resDuplicado->num_rows > 0) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "Ya estás matriculado en este grupo."
        ]);
        exit;
    }

    // Validar cupos
    if (class_exists("ValidadorCupos") && method_exists("ValidadorCupos", "hayCupo")) {
        if (!ValidadorCupos::hayCupo($conn, $grupoId)) {
            echo json_encode([
                "ok" => false,
                "mensaje" => "No hay cupos disponibles para este grupo."
            ]);
            exit;
        }
    }

    // Validar choque de horarios
    if (class_exists("ValidadorHorarios") && method_exists("ValidadorHorarios", "hayCruce")) {
        if (ValidadorHorarios::hayCruce($conn, $usuarioId, $grupoId)) {
            echo json_encode([
                "ok" => false,
                "mensaje" => "Existe cruce de horario con otra materia matriculada."
            ]);
            exit;
        }
    }

    $sqlInsert = "INSERT INTO matriculas (usuario_id, grupo_id, fecha_matricula, estado)
                  VALUES (?, ?, NOW(), 'activa')";
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
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error del servidor: " . $e->getMessage()
    ]);
}