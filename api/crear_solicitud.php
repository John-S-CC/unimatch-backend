<?php

header("Content-Type: application/json");

require_once "../configuracion/database.php";
require_once "../eventos/MotorEventos.php";
$db = new Database();
$conn = $db->connect();
$usuario = $_POST['usuario_id'] ?? null;
$tipo = $_POST['tipo_solicitud'] ?? null;

$grupoOrigen = $_POST['grupo_origen'] ?? null;
$grupoDestino = $_POST['grupo_destino'] ?? null;

$materiaOrigen = $_POST['materia_origen'] ?? null;
$materiaDestino = $_POST['materia_destino'] ?? null;

if(!$usuario || !$tipo){

    echo json_encode([
        "ok"=>false,
        "mensaje"=>"Datos incompletos"
    ]);

    exit;
}

$sql = "
INSERT INTO solicitudes
(
usuario_id,
tipo_solicitud,
grupo_origen,
grupo_destino,
materia_origen,
materia_destino,
estado,
fecha
)
VALUES
(?,?,?,?,?,?, 'pendiente', NOW())
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
"isssii",
$usuario,
$tipo,
$grupoOrigen,
$grupoDestino,
$materiaOrigen,
$materiaDestino
);

if($stmt->execute()){

    MotorEventos::procesarEvento(
        $conn,
        "nueva_solicitud",
        [
            "usuario"=>$usuario
        ]
    );

    echo json_encode([
        "ok"=>true,
        "mensaje"=>"Solicitud registrada"
    ]);

}else{

    echo json_encode([
        "ok"=>false,
        "mensaje"=>"Error al registrar solicitud"
    ]);

}