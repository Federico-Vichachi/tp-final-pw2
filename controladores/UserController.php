<?php

class UserController
{
    private $conexion;
    private $renderer;
    private $model;

    public function __construct($UserModel,$renderer)
    {
        $this->model = $UserModel;
        $this->renderer = $renderer;
    }

    public function base()
    {
        $this->lobby();
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

    public function lobby()
    {
        $usuario = $this->model->getId();
        $this->renderer->render("lobby",[
            'usuario' => $usuario
        ]);
    }
}