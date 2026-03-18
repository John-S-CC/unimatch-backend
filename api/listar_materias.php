<?php

header("Content-Type: application/json");

require_once "../configuracion/database.php";
$db = new Database();
$conn = $db->connect();

$sql="
SELECT
m.nombre AS materia,
g.id_grupo,

GROUP_CONCAT(
CONCAT(h.dia,' ',h.hora_inicio,'-',h.hora_fin)
SEPARATOR ' / '
) AS horario,

(g.cupos - COUNT(DISTINCT mat.id_matricula)) AS cupos_disponibles

FROM grupos g

JOIN materias m
ON g.id_materia = m.id_materia

JOIN horarios h
ON h.id_grupo = g.id_grupo

LEFT JOIN matriculas mat
ON mat.id_grupo = g.id_grupo

GROUP BY g.id_grupo
";

$result=$conn->query($sql);

$materias=[];

while($row=$result->fetch_assoc()){
$materias[]=$row;
}

echo json_encode([
"ok"=>true,
"materias"=>$materias
]);