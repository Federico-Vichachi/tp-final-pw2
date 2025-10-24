<?php

class GameController
{
    private $conexion;
    private $renderer;
    private $model;

    public function __construct($GameModel, $renderer)
    {
        $this->model = $GameModel;
        $this->renderer = $renderer;
    }

    public function base()
    {
        $this->partida();
    }

    public function partida()
    {
        $preguntas = $this->model->getPregunta("historia"); // Falta hacer random
        $preguntaRandom = $preguntas[array_rand($preguntas)];
        $idPreguntaRandom = $preguntaRandom['pregunta_id'];
        $respuestas = $this->model->getRespuestas($idPreguntaRandom);
        $this->renderer->render("partida", [
            'pregunta' => $preguntaRandom,
            'respuestas' => $respuestas
        ]);
    }

}