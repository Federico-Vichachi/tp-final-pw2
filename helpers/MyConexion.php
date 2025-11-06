<?php

class MyConexion
{

    private $conexion;

    public function __construct($server, $user, $pass, $database, $charset)
    {
        $this->conexion = new mysqli($server, $user, $pass, $database);
        if ($this->conexion->error) { die("Error en la conexión: " . $this->conexion->error); }
        $this->conexion->set_charset($charset);
    }

    public function query($sql)
    {
        $result = $this->conexion->query($sql);

        if ($result && !is_bool($result) && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return $result;
    }
}