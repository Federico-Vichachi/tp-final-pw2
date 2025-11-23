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

        $rol = $_SESSION['usuario']['rol'] ?? 'usuario';

        if ($rol === 'administrador') {
            header('Location: /admin/panel');
            exit();
        }
        if ($rol === 'editor') {
            header('Location: /editor/index');
            exit();
        }

        $this->renderer->render("lobby",[
            'usuario' => $_SESSION['usuario']
        ]);
    }

    public function sugerirNuevaPregunta()
    {
        $this->redirectNotAuthenticated();

        $categorias = $this->model->getCategorias();

        $data = ['categorias' => $categorias];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarSugerenciaPregunta();
        }

        // Pasar datos del usuario a la vista
        if (isset($_SESSION['usuario'])) {
            $data['usuario'] = $this->model->getUsuarioById($_SESSION['usuario']['id']);
        }

        $this->renderer->render("sugerirPregunta", $data);
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

        if ($usuario['rol'] === 'administrador') {
            header('Location: /admin/panel');
        }
        else if ($usuario['rol'] === 'editor') {
            header('Location: /editor/index');
        }
        else {
            header('Location: /user/lobby');
        }
        exit();
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
            $data = [
                'error' => "Error al enviar la sugerencia. Intenta nuevamente.",
                'datos' => $datosSugerenciaPregunta
            ];

            // Pasar datos del usuario a la vista en caso de error
            if (isset($_SESSION['usuario'])) {
                $data['usuario'] = $this->model->getUsuarioById($_SESSION['usuario']['id']);
            }

            $this->renderer->render("sugerirPregunta", $data);
            exit();
        }
        $_SESSION['mensaje_exito'] = 'Sugerencia de pregunta enviada exitosamente. ¡Gracias por contribuir!';
        $this->redirectTo('lobby');
    }

    public function perfil()
    {
        $this->redirectNotAuthenticated();
        $usuarioId = $_SESSION['usuario']['id'];
        $usuario = $this->model->getUsuarioById($usuarioId);

        // Usar URL base dinámica
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
        $qrUrl = $baseUrl . '/game/jugador?id=' . $usuarioId;

        require_once 'modelos/QrModel.php';
        $qrModel = new QrModel();

        // Definir rutas
        $qrDir = __DIR__ . '/../public/uploads/qr/';
        $filePath = $qrDir . 'qr_' . $usuarioId . '.png';

        // Crear directorio si no existe
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0755, true);
        }

        // Verificar permisos de escritura
        if (!is_writable($qrDir)) {
            error_log("Directorio QR no tiene permisos de escritura: " . $qrDir);
            // Usar una ruta alternativa o mostrar error
            $qrPublicPath = '/public/imagenes/qr-error.png';
        } else {
            try {
                $qrModel->generateQr($qrUrl, $filePath);
                $qrPublicPath = '/public/uploads/qr/qr_' . $usuarioId . '.png';

                // Verificar que el archivo se creó
                if (!file_exists($filePath)) {
                    error_log("Archivo QR no se generó: " . $filePath);
                    $qrPublicPath = '/public/imagenes/qr-error.png';
                }
            } catch (Exception $e) {
                error_log("Error generando QR: " . $e->getMessage());
                $qrPublicPath = '/public/imagenes/qr-error.png';
            }
        }

        $data = [
            'usuario' => $usuario,
            'qr_path' => $qrPublicPath
        ];
        $this->renderer->render("perfil", $data);
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
            $rol = $_SESSION['usuario']['rol'] ?? 'usuario';

            if ($rol === 'administrador') {
                header('Location: /admin/panel');
            }
            else if ($rol === 'editor') {
                header('Location: /editor/index');
            }
            else {
                header('Location: /user/lobby');
            }
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