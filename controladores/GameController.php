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
        $partida = $this->model->getPartidaAleatoria();
        $this->renderer->render("partida", [
            'partida' => $partida
        ]);
    }

}