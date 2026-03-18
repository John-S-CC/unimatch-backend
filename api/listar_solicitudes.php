<?php

header("Content-Type: application/json");

require_once "../configuracion/database.php";

$db = new Database();
$conn = $db->connect();

$usuario = $_GET['usuario_id'] ?? null;

if(!$usuario){

    echo json_encode([
        "ok"=>false,
        "mensaje"=>"Usuario requerido"
    ]);

    exit;
}

$sql="
SELECT
s.id_solicitud,
s.tipo_solicitud,
s.estado,
s.fecha,
m1.nombre AS materia_origen,
m2.nombre AS materia_destino,
g1.id_grupo AS grupo_origen,
g2.id_grupo AS grupo_destino
FROM solicitudes s

LEFT JOIN materias m1
ON s.materia_origen=m1.id_materia

LEFT JOIN materias m2
ON s.materia_destino=m2.id_materia

LEFT JOIN grupos g1
ON s.grupo_origen=g1.id_grupo

LEFT JOIN grupos g2
ON s.grupo_destino=g2.id_grupo

WHERE s.usuario_id=?

ORDER BY s.fecha DESC
";

$stmt=$conn->prepare($sql);

$stmt->bind_param("i",$usuario);

$stmt->execute();

$result=$stmt->get_result();

$solicitudes=[];

while($row=$result->fetch_assoc()){

    $solicitudes[]=$row;

}

echo json_encode([
    "ok"=>true,
    "solicitudes"=>$solicitudes
]);