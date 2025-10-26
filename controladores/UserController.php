<?php

class UserController
{
    private $renderer;
    private $model;

    public function __construct($UserModel,$renderer)
    {
        $this->model = $UserModel;
        $this->renderer = $renderer;
    }

    public function base()
    {
        $this->ingresar();
    }

    public function registrar() {
        $this->redirectAuthenticated();
        $this->renderer->render("registrar", []);
    }

    public function validar() {
        $this->redirectAuthenticated();
        $this->renderer->render("validar", []);
    }

    public function ingresar() {
        $this->redirectAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarLogin();
        }

        $this->renderer->render("ingresar", []);
    }

    public function salir()
    {
        session_destroy();
        $this->redirectToIndex();
    }

    public function lobby()
    {
        $this->redirectNotAuthenticated();
        $this->renderer->render("lobby",[]);
    }

    private function procesarLogin()
    {
        $usuario = $this->model->getUsuario($_POST['user'], $_POST['password']);
        if (empty($usuario)) {
            $this->renderer->render("ingresar", [
                'error' => "Usuario o clave incorrecta"
            ]);
            exit;
        }
        $_SESSION['usuario'] = $usuario;
        $this->redirectTo('lobby');
    }

    private function isAuthenticated()
    {
        return isset($_SESSION["usuario"]);
    }

    private function redirectNotAuthenticated()
    {
        if (!$this->isAuthenticated())
            $this->redirectToIndex();
    }

    private function redirectAuthenticated()
    {
        if ($this->isAuthenticated()) {
            $this->redirectTo('lobby');
        }
    }

    private function redirectToIndex()
    {
        header("Location: /");
        exit();
    }

    private function redirectTo($method)
    {
        header("Location: user/$method");
        exit;
    }
}