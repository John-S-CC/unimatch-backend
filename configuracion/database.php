<?php

class Database {

    private $host;
    private $db;
    private $user;
    private $pass;
    private $port;

    public function __construct() {
        $this->host = getenv('UNIMATCH_DB_HOST') ?: 'localhost';
        $this->db = getenv('UNIMATCH_DB_NAME') ?: 'unimatch';
        $this->user = getenv('UNIMATCH_DB_USER') ?: 'root';
        $this->pass = getenv('UNIMATCH_DB_PASS') ?: '1234';
        $this->port = (int) (getenv('UNIMATCH_DB_PORT') ?: 3306);
    }

    public function connect() {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $conn = new mysqli(
                $this->host,
                $this->user,
                $this->pass,
                $this->db,
                $this->port
            );
            $conn->set_charset('utf8mb4');
            return $conn;
        } catch (mysqli_sql_exception $e) {
            throw new Exception('Error de conexión a la base de datos.');
        }
    }
}
