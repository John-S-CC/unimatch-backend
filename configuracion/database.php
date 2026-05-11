<?php
require_once __DIR__ . "/env.php";

class Database {

    private $host;
    private $db;
    private $user;
    private $pass;
    private $port;
public function connect() {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn = mysqli_init(); // 1. Inicializar
        
        // 2. Configurar SSL (En Render no necesitas el archivo .pem si usas MYSQLI_CLIENT_SSL)
        // Solo indicamos que se requiere SSL
        $conn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);

        // 3. Conectar
        $conn->real_connect(
            $this->host,
            $this->user,
            $this->pass,
            $this->db,
            $this->port,
            null,
            MYSQLI_CLIENT_SSL // Flag vital para Aiven
        );

        $conn->set_charset('utf8mb4');
        return $conn;
    } catch (mysqli_sql_exception $e) {
        // Es mejor imprimir el error real para debuguear al principio
        throw new Exception('Error de conexión: ' . $e->getMessage());
    }
}

    public function __construct() {
        $this->host = getenv('UNIMATCH_DB_HOST') ?: getenv('DB_HOST');
        $this->db = getenv('UNIMATCH_DB_NAME') ?: getenv('DB_NAME');
        $this->user = getenv('UNIMATCH_DB_USER') ?: getenv('DB_USER');
        $this->pass = getenv('UNIMATCH_DB_PASS') ?: getenv('DB_PASS');
        $this->port = (int) (getenv('UNIMATCH_DB_PORT') ?: getenv('DB_PORT') ?: 3306);

        if (!$this->host || !$this->db || !$this->user) {
            throw new Exception('Configuración de base de datos incompleta. Revise las variables de entorno.');
        }
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
