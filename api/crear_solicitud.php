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

$tipoSolicitud = trim($input["tipo_solicitud"] ?? $_POST["tipo_solicitud"] ?? "");
$grupoOrigen = isset($input["grupo_origen"]) ? (int)$input["grupo_origen"] : (isset($_POST["grupo_origen"]) ? (int)$_POST["grupo_origen"] : 0);
$grupoDestino = isset($input["grupo_destino"]) ? (int)$input["grupo_destino"] : (isset($_POST["grupo_destino"]) ? (int)$_POST["grupo_destino"] : 0);
$materiaOrigen = isset($input["materia_origen"]) ? (int)$input["materia_origen"] : (isset($_POST["materia_origen"]) ? (int)$_POST["materia_origen"] : 0);
$materiaDestino = isset($input["materia_destino"]) ? (int)$input["materia_destino"] : (isset($_POST["materia_destino"]) ? (int)$_POST["materia_destino"] : 0);

$tiposValidos = ["cancelacion", "cambio_grupo", "cambio_materia", "nueva_inscripcion"];

if ($tipoSolicitud === "" || !in_array($tipoSolicitud, $tiposValidos, true)) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "El tipo de solicitud no es válido."
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();

    if (!$conn) {
        throw new Exception("No fue posible conectar con la base de datos.");
    }

    $conn->begin_transaction();

    if ($tipoSolicitud === "cancelacion") {

        if ($grupoOrigen <= 0 || $materiaOrigen <= 0) {
            throw new Exception("Para cancelación debes seleccionar materia y grupo de origen.");
        }

        $sqlBuscar = "
            SELECT m.id_matricula
            FROM matriculas m
            INNER JOIN grupos g ON g.id_grupo = m.grupo_id
            WHERE m.usuario_id = ?
              AND m.grupo_id = ?
              AND g.id_materia = ?
              AND m.estado = 'activa'
            LIMIT 1
        ";
        $stmtBuscar = $conn->prepare($sqlBuscar);
        $stmtBuscar->bind_param("iii", $usuarioId, $grupoOrigen, $materiaOrigen);
        $stmtBuscar->execute();
        $matricula = $stmtBuscar->get_result()->fetch_assoc();

        if (!$matricula) {
            throw new Exception("No se encontró una matrícula activa para cancelar.");
        }

        $sqlCancelar = "
            UPDATE matriculas
            SET estado = 'cancelada'
            WHERE id_matricula = ?
            LIMIT 1
        ";
        $stmtCancelar = $conn->prepare($sqlCancelar);
        $stmtCancelar->bind_param("i", $matricula["id_matricula"]);

        if (!$stmtCancelar->execute()) {
            throw new Exception("No fue posible cancelar la matrícula.");
        }

        $sqlSolicitud = "
            INSERT INTO solicitudes (
                usuario_id,
                tipo_solicitud,
                grupo_origen,
                grupo_destino,
                materia_origen,
                materia_destino,
                estado,
                fecha_solicitud
            ) VALUES (?, 'cancelacion', ?, NULL, ?, NULL, 'aprobada', NOW())
        ";
        $stmtSolicitud = $conn->prepare($sqlSolicitud);
        $stmtSolicitud->bind_param("iii", $usuarioId, $grupoOrigen, $materiaOrigen);

        if (!$stmtSolicitud->execute()) {
            throw new Exception("No fue posible registrar la solicitud de cancelación.");
        }

        $conn->commit();

        echo json_encode([
            "ok" => true,
            "mensaje" => "La materia fue cancelada correctamente."
        ]);
        exit;
    }

    if ($tipoSolicitud === "cambio_grupo") {
        if ($grupoOrigen <= 0 || $grupoDestino <= 0 || $materiaOrigen <= 0 || $materiaDestino <= 0) {
            throw new Exception("Para cambio de grupo debes seleccionar origen y destino.");
        }

        if ($materiaOrigen !== $materiaDestino) {
            throw new Exception("En cambio de grupo la materia origen y destino deben ser la misma.");
        }
    }

    if ($tipoSolicitud === "cambio_materia") {
        if ($grupoOrigen <= 0 || $grupoDestino <= 0 || $materiaOrigen <= 0 || $materiaDestino <= 0) {
            throw new Exception("Para cambio de materia debes seleccionar origen y destino.");
        }

        if ($materiaOrigen === $materiaDestino) {
            throw new Exception("En cambio de materia la materia destino debe ser diferente a la de origen.");
        }
    }

    if ($tipoSolicitud === "nueva_inscripcion") {
        if ($grupoDestino <= 0 || $materiaDestino <= 0) {
            throw new Exception("Debes seleccionar una opción de destino.");
        }
    }

    $sql = "
        INSERT INTO solicitudes (
            usuario_id,
            tipo_solicitud,
            grupo_origen,
            grupo_destino,
            materia_origen,
            materia_destino,
            estado,
            fecha_solicitud
        ) VALUES (?, ?, ?, ?, ?, ?, 'pendiente', NOW())
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isiiii",
        $usuarioId,
        $tipoSolicitud,
        $grupoOrigen,
        $grupoDestino,
        $materiaOrigen,
        $materiaDestino
    );

    if (!$stmt->execute()) {
        throw new Exception("No fue posible registrar la solicitud.");
    }

    $conn->commit();

    $resultadoMotor = null;

    if ($tipoSolicitud === "cambio_grupo" || $tipoSolicitud === "cambio_materia") {
        require_once "../servicios/MotorPermutas.php";
        $resultadoMotor = MotorPermutas::procesar($conn);
    }

    echo json_encode([
        "ok" => true,
        "mensaje" => "Solicitud creada correctamente y enviada a proceso.",
        "motor" => $resultadoMotor
    ]);

} catch (Throwable $e) {

    if (isset($conn) && $conn) {
        $conn->rollback();
    }

    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error del servidor: " . $e->getMessage()
    ]);
}