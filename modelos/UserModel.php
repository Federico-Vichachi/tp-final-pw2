<?php

class UserModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function getUsuario($user, $password)
    {
        $sql = "SELECT * FROM usuario WHERE username = '$user' AND password = '$password'";
        $result = $this->conexion->query($sql);
        return $result;
    }
}