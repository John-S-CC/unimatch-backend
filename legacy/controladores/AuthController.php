<?php

require_once __DIR__ . "/../configuracion/database.php";

class AuthController {

    public static function login(){

        $data = json_decode(file_get_contents("php://input"), true);

        $correo = $data['correo'];
        $password = $data['password'];

        $db = new Database();
        $conn = $db->connect();

        $sql = "SELECT * FROM usuarios WHERE correo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $correo);
        $stmt->execute();

        $result = $stmt->get_result();

        if($result->num_rows > 0){

            $usuario = $result->fetch_assoc();

            if($password == $usuario['password']){

                echo json_encode([
                    "success" => true,
                    "usuario" => $usuario
                ]);

            } else {

                echo json_encode([
                    "success" => false,
                    "mensaje" => "Contraseña incorrecta"
                ]);
            }

        } else {

            echo json_encode([
                "success" => false,
                "mensaje" => "Usuario no encontrado"
            ]);
        }

    }

}