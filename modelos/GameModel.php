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
        $sql = "SELECT id, texto 
            FROM respuestas 
            WHERE pregunta_id = '$id_pregunta'";
        $resultado = $this->conexion->query($sql);
        return $resultado;
    }

    public function getPartidaAleatoria($preguntasVistas = [])
    {
        $sql = "";
        if(!empty($preguntasVistas)){
            $idsExcluidos = implode(",", $preguntasVistas);

            $sql = "SELECT p.id AS pregunta_id, p.
                    texto AS pregunta, 
                    c.nombre AS categoria
            FROM preguntas p
            JOIN categorias c ON p.categoria_id = c.id
            WHERE p.id NOT IN ($idsExcluidos)
            ORDER BY RAND()
            LIMIT 1";
        }else{
            $sql = "SELECT p.id AS pregunta_id, 
                    p.texto AS pregunta, 
                    c.nombre AS categoria
            FROM preguntas p
            JOIN categorias c ON p.categoria_id = c.id
            ORDER BY RAND()
            LIMIT 1";
        }

        $pregunta = $this->conexion->query($sql);

        if(empty($pregunta)){
            return [];
        }

        $preguntaRandom = $pregunta[0];
        $respuestas = $this->getRespuestas($preguntaRandom['pregunta_id']);
        shuffle($respuestas);

        return [
            "pregunta" => $preguntaRandom,
            "respuestas" => $respuestas
        ];
    }

    public function verificarRespuesta($idRespuesta){
        if ($idRespuesta ==null) return false;

        $sql = "SELECT es_correcta FROM respuestas WHERE id = $idRespuesta";
        $resultado = $this->conexion->query($sql);

        return !empty($resultado) && $resultado[0]['es_correcta']==1;
    }
}