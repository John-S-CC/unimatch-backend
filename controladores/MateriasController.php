<?php

require_once __DIR__ . "/../configuracion/database.php";

class MateriasController {

    public static function listar(){

        $db = new Database();
        $conn = $db->connect();

        $sql = "SELECT * FROM materias";

        $result = $conn->query($sql);

        $materias = [];

        while($row = $result->fetch_assoc()){
            $materias[] = $row;
        }

        echo json_encode($materias);

    }

}