<?php

header("Content-Type: application/json");

require_once "../configuracion/database.php";
require_once "../eventos/MotorEventos.php";

$db = new Database();
$conn = $db->connect();

$usuario = $_POST['usuario_id'] ?? null;
$grupo = $_POST['grupo_id'] ?? null;

if(!$usuario || !$grupo){

    echo json_encode([
        "ok"=>false,
        "mensaje"=>"Datos incompletos"
    ]);

    exit;
}

$sql = "
DELETE FROM matriculas
WHERE usuario_id=? AND grupo_id=?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
"ii",
$usuario,
$grupo
);

if($stmt->execute()){

    MotorEventos::procesarEvento(
        $conn,
        "cancelacion_materia",
        [
            "usuario"=>$usuario,
            "grupo"=>$grupo
        ]
    );

    echo json_encode([
        "ok"=>true,
        "mensaje"=>"Materia cancelada"
    ]);

}else{

    echo json_encode([
        "ok"=>false,
        "mensaje"=>"No se pudo cancelar"
    ]);

}