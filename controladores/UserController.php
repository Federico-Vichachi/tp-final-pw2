<?php

class UserController
{
    private $conexion;
    private $renderer;
    private $model;

    public function __construct($pokemonModel,$renderer)
    {
        $this->model = $pokemonModel;
        $this->renderer = $renderer;
    }

    public function base()
    {
        $this->registrar();
    }

    public function redirectToIndex()
    {
        header("Location: /");
        exit;
    }

    public function registrar() {
        $this->renderer->render("registrar", []);
    }

    public function validar() {
        $this->renderer->render("validar", []);
    }

    public function ingresar() {
        $this->renderer->render("ingresar", []);
    }

    public function salir()
    {
        session_destroy();
        $this->redirectToIndex();
    }
}