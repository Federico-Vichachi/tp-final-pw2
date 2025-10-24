<?php

class UserModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function getId()
    {
        $sql = 'SELECT * FROM preguntados.usuario';
        $result = $this->conexion->query($sql);
        return $result[0];
    }

}