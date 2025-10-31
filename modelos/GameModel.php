<?php

class GameModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function getPreguntaAleatoria($preguntasVistas)
    {
        $pregunta = $this->obtenerPreguntaDeBD($preguntasVistas);

        if(empty($pregunta)) {
            return [];
        }

        $respuestas = $this->getRespuestas($pregunta['pregunta_id']);
        shuffle($respuestas);

        $respuestaCorrecta = $this->getRespuestaCorrecta($pregunta['pregunta_id']);

        return [
            "pregunta" => $pregunta,
            "respuestas" => $respuestas,
            "respuesta_correcta" => $respuestaCorrecta
        ];
    }

    public function verificarRespuesta($idRespuesta)
    {
        if ($idRespuesta == null) {
            return false;
        }

        $sql = "SELECT es_correcta FROM respuestas WHERE id = $idRespuesta";
        $resultado = $this->conexion->query($sql);

        if (empty($resultado)) {
            return false;
        }

        $esCorrecta = $resultado[0]['es_correcta'];
        return $esCorrecta == 1;
    }

    public function getRespuestaCorrecta($preguntaId)
    {
        $id = (int)$preguntaId;

        $sql = "SELECT texto 
                FROM respuestas 
                WHERE pregunta_id = $id 
                  AND es_correcta = 1 LIMIT 1";

        $resultado = $this->conexion->query($sql);
        return empty($resultado) ? 'No disponible' : $resultado[0]['texto'];
    }

    private function obtenerPreguntaDeBD($preguntasVistas)
    {
        $sql = "SELECT p.id AS pregunta_id, 
                   p.texto AS pregunta, 
                   c.nombre AS categoria
            FROM preguntas p
            JOIN categorias c ON p.categoria_id = c.id";

        if(!empty($preguntasVistas)){
            $idsExcluidos = implode(",", $preguntasVistas);
            $sql .= " WHERE p.id NOT IN ($idsExcluidos)";
        }

        $sql .= " ORDER BY RAND() LIMIT 1";

        $resultado = $this->conexion->query($sql);
        return empty($resultado) ? [] : $resultado[0];
    }

    private function getRespuestas($id_pregunta)
    {
        $sql = "SELECT id, texto 
                FROM respuestas 
                WHERE pregunta_id = '$id_pregunta'";

        $resultado = $this->conexion->query($sql);
        return $resultado;
    }
}