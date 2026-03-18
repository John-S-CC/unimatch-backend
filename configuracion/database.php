<?php

class Database {

    private $host = "localhost";
    private $db = "unimatch";
    private $user = "root";
    private $pass = "1234";

    public function connect(){

        $conn = new mysqli(
            $this->host,
            $this->user,
            $this->pass,
            $this->db
        );

        if($conn->connect_error){
            die("Error de conexión: " . $conn->connect_error);
        }

        return $conn;
    }

}