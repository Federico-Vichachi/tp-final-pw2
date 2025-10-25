<?php

class GameModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function getCategorias()
    {
        $sql = "SELECT nombre FROM categorias";
        $resultado = $this->conexion->query($sql);
        return $resultado;
    }

    public function getPreguntas($categoria)
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

    public function getRespuestas($id_pregunta)
    {
        $sql = "SELECT texto, es_correcta 
            FROM respuestas 
            WHERE pregunta_id = '$id_pregunta'";
        $resultado = $this->conexion->query($sql);
        return $resultado;
    }

    public function getPartidaAleatoria()
    {
        $categorias = $this->getCategorias();
        $categoriaRandom = $categorias[array_rand($categorias)];

        $preguntas = $this->getPreguntas($categoriaRandom["nombre"]);
        $preguntaRandom = $preguntas[array_rand($preguntas)];

        $respuestas = $this->getRespuestas($preguntaRandom['pregunta_id']);
        shuffle($respuestas);

        return [
            'categoria' => $categoriaRandom,
            'pregunta' => $preguntaRandom,
            'respuestas' => $respuestas
        ];
    }

}