<?php

class GameModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function getPregunta($categoria)
    {
        $sql = "SELECT 
                p.id AS pregunta_id,
                p.texto AS pregunta,
                c.nombre AS categoria
            FROM preguntas p
            JOIN categorias c ON p.categoria_id = c.id
            WHERE c.nombre = '$categoria'";
        $resultado = $this->conexion->query($sql);
        return $resultado;
    }

    public function getRespuestas($id)
    {
        $sql = "SELECT texto, es_correcta 
            FROM respuestas 
            WHERE pregunta_id = '$id'";
        $resultado = $this->conexion->query($sql);
        return $resultado;
    }

}