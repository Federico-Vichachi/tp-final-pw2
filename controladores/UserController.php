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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarValidacion();
        }

        // Pasar el email desde GET al template
        $email = $_GET['email'] ?? '';
        $codigo = $_GET['codigo'] ?? '';

        $this->renderer->render("validar", [
            'email' => $email,
            'codigo' => $codigo
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

        if (isset($_SESSION['usuario']['rol']) && $_SESSION['usuario']['rol'] === 'editor') {
            header('Location: /editor/index');
            exit();
        }
        $usuario = $this->model->getUsuarioById($_SESSION['usuario']['id']);
        $this->renderer->render("lobby",[
            'usuario' => $usuario
        ]);
    }

    public function sugerirNuevaPregunta()
    {
        $this->redirectNotAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarSugerenciaPregunta();
        }

        $this->renderer->render("sugerirPregunta", []);
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

        if ($usuario['rol'] === 'editor') {
            header('Location: /editor/index');
        } else {
            header('Location: /user/lobby');
        }
        exit();

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
            'latitud' => $_POST['latitud'] ?? '',
            'longitud' => $_POST['longitud'] ?? '',
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
        $email = urlencode($datos['email']);
        $this->redirectTo("validar?email=$email");
    }

    private function procesarValidacion()
    {
        // Obtener email de POST o GET (si viene por URL)
        $codigo = $_POST['codigo'] ?? $_GET['codigo'] ?? '';
        $email = $_POST['email'] ?? $_GET['email'] ?? '';

        if (empty($email)) {
            $this->renderer->render("validar", [
                'error' => "Error en la validación."
            ]);
            exit();
        }

        $validacionExitosa = $this->model->validarUsuario($codigo, $email);

        if (!$validacionExitosa) {
            $this->renderer->render("validar", [
                'error' => "Código de validación incorrecto. Intenta nuevamente.",
                'email' => $email
            ]);
            exit();
        }

        $_SESSION['mensaje_exito'] = 'Cuenta validada exitosamente. Ahora puedes ingresar.';
        $this->redirectTo('ingresar');
    }

    private function procesarSugerenciaPregunta()
    {
        $datosSugerenciaPregunta = [
            'usuario_id' => $_SESSION['usuario']['id'],
            'categoria' => $_POST['categoria'] ?? '',
            'pregunta' => $_POST['pregunta'] ?? '',
            'opcion1' => $_POST['opcion1'] ?? '',
            'opcion2' => $_POST['opcion2'] ?? '',
            'opcion3' => $_POST['opcion3'] ?? '',
            'opcion4' => $_POST['opcion4'] ?? '',
            'respuesta_correcta' => $_POST['respuesta_correcta'] ?? ''
        ];

        $sugerenciaExitosa = $this->model->guardarSugerenciaPregunta($datosSugerenciaPregunta);

        if (!$sugerenciaExitosa) {
            $this->renderer->render("sugerirPregunta", [
                'error' => "Error al enviar la sugerencia. Intenta nuevamente.",
                'datos' => $datosSugerenciaPregunta
            ]);
            exit();
        }
        $_SESSION['mensaje_exito'] = 'Sugerencia de pregunta enviada exitosamente. ¡Gracias por contribuir!';
        $this->redirectTo('lobby');
    }

    public function perfil()
    {
        $this->redirectNotAuthenticated();
        $usuario = $this->model->getUsuarioById($_SESSION['usuario']['id']);
        $this->renderer->render("perfil",[
            'usuario' => $usuario
        ]);
    }

    private function aceptarInvitacion()
    {//Manejar en el lobby desde invitaciones

    }

    private function rechazarInvitacion()
    {//Manejar en el lobby desde invitaciones

    }

    private function desafiar()
    {//Debe poder llamarse desde el perfil

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
            if (isset($_SESSION['usuario']['rol']) && $_SESSION['usuario']['rol'] === 'editor') {
                header('Location: /editor/index');
                exit();
            }

            header('Location: /user/lobby');
            exit();
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
        header("Location: /user/$method");
        exit();
    }

    public function verificarEmail()
    {
        header('Content-type: application/json');
        $email = $_GET['email'] ?? '';

        if ($email === '') {
            echo json_encode(['existe' => false]);
            return;
        }

        $emailExistente = $this->model->emailExiste($email);
        echo json_encode(['existe' => $emailExistente]);
    }
}