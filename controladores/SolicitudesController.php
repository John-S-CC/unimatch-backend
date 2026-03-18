<?php

require_once __DIR__ . "/../servicios/MotorPermutas.php";

class SolicitudesController {

    public static function crear(){

        $data = json_decode(file_get_contents("php://input"), true);

        $usuario = $data['usuario_id'];
        $tipo = $data['tipo_solicitud'];
        $grupo_origen = $data['grupo_origen'];
        $grupo_destino = $data['grupo_destino'];

        $db = new Database();
        $conn = $db->connect();

        $sql = "INSERT INTO solicitudes 
        (usuario_id, tipo_solicitud, grupo_origen, grupo_destino, fecha_solicitud)
        VALUES (?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "isii",
            $usuario,
            $tipo,
            $grupo_origen,
            $grupo_destino
        );

        $stmt->execute();
MotorPermutas::procesar($conn);
        echo json_encode([
            "success" => true,
            "mensaje" => "Solicitud registrada"
        ]);

    }

}