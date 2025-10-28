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
        $this->redirectToIngresar();
    }

    public function registrar() {
        $this->redirectAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarRegistro();
        }

        $this->renderer->render("registrar", []);
    }

    public function validar() {
        $this->redirectAuthenticated();

        $mensaje = $_SESSION['mensaje_exito'] ?? null;
        unset($_SESSION['mensaje_exito']);

        $this->renderer->render("validar", [
            'mensaje' => $mensaje
        ]);
    }

    public function ingresar() {
        $this->redirectAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarLogin();
        }

        $this->renderer->render("ingresar", []);
    }

    public function lobby()
    {
        $this->redirectNotAuthenticated();
        if (isset($_GET['action']) && $_GET['action'] === 'salir') {
            $this->salir();
        }
        $this->renderer->render("lobby",[
            'usuario' => $_SESSION['usuario']
        ]);
    }

    private function salir()
    {
        session_destroy();
        $this->redirectToIndex();
    }

    private function procesarLogin()
    {
        $usuario = $this->model->getUsuario($_POST['user'], $_POST['password']);
        if (empty($usuario)) {
            $this->renderer->render("ingresar", [
                'error' => "Usuario o clave incorrecta"
            ]);
            exit();
        }
        $_SESSION['usuario'] = $usuario;
        $this->redirectTo('lobby');
    }

    private function procesarRegistro()
    {
        $datos = [
            'nombre_completo' => $_POST['nombre_completo'] ?? '',
            'anio_nacimiento' => $_POST['anio_nacimiento'] ?? '',
            'sexo' => $_POST['sexo'] ?? 'Prefiero no cargarlo',
            'pais' => $_POST['pais'] ?? '',
            'ciudad' => $_POST['ciudad'] ?? '',
            'email' => $_POST['email'] ?? '',
            'username' => $_POST['username'] ?? '',
            'password' => $_POST['password'] ?? ''
        ];

        $file = $_FILES['file'] ?? null;

        $resultado = $this->model->registrarUsuario($datos, $file);

        if (!$resultado['ok']) {
            $this->renderer->render("registrar", [
                'error' => "Error en el registro:",
                'errores' => $resultado['errores'],
                'datos' => $datos
            ]);
            exit();
        }

        $_SESSION['mensaje_exito'] = 'Registro exitoso. ¡Revisa tu correo para validar tu cuenta!';
        $this->redirectTo('validar');
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

    private function redirectToIngresar(){
        header("Location: /user/ingresar");
        exit();
    }

    private function redirectTo($method)
    {
        header("Location: $method");
        exit();
    }
}