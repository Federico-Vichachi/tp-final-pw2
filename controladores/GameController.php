<?php

class GameController
{
    private $renderer;
    private $model;


    public function __construct($GameModel,$renderer)
    {
        $this->model = $GameModel;
        $this->renderer = $renderer;
    }

    public function base()
    {
        $this->redirectToIndex();
    }

    public function jugarPartida()
    {
        $this->verificarRolJugador();
        $this->redirectNotAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarPartida();
        }

        if (!$this->partidaEstaIniciada()) {
            $this->iniciarNuevaPartida();
        }

        $partida = $this->obtenerPreguntaActual();
        $this->mostrarVistaPartida($partida);
    }

    public function reportarPregunta()
    {
        $this->redirectNotAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $preguntaId = $_POST['pregunta_id'] ?? null;
            $motivo = $_POST['motivo'] ?? '';
            $usuarioId = $_SESSION['usuario']['id'];

            if ($preguntaId && !empty($motivo)) {
                $this->model->guardarReporte($preguntaId, $usuarioId, $motivo);
            }
        }

        $this->redirectToLobby();
    }

    private function verificarRolJugador()
    {
        if (isset($_SESSION['usuario'])) {
            if ($_SESSION['usuario']['rol'] === 'editor') {
                header('Location: /editor/index');
                exit();
            }
            if ($_SESSION['usuario']['rol'] !== 'usuario') {
                header('Location: /');
                exit();
            }
        }
    }

    public function resumenPartida()
    {
        $this->redirectNotAuthenticated();

        list($puntajeFinal, $mensaje, $puntosGanados) = $this->calcularResultadoPartida();

        if (isset($_SESSION["usuario"]["id"]) && $this->huboPartidaReal()) {
            $this->model->actualizarPuntosUsuario($_SESSION["usuario"]["id"], $puntosGanados);
        }

        $data = $this->prepararDatosResumen($puntajeFinal, $mensaje, $puntosGanados);
        $data['historial_partida'] = $_SESSION["historial_partida"] ?? [];

        $data['hubo_partida'] = $this->huboPartidaReal();

        if ($data['hubo_partida']) {
            $this->finalizarPartidaEnBD($puntajeFinal);
        }

        if (isset($_SESSION['usuario'])) {
            $data['usuario'] = $this->model->getUsuarioById($_SESSION['usuario']['id']);
        }

        $this->limpiarPartidaActual();
        $this->renderer->render("resumenPartida", $data);
    }

    public function jugador()
    {
        $this->redirectNotAuthenticated();

        if (!isset($_GET["id"])) {
            $this->redirectTo('ranking');
        }

        $usuarioId = $_GET["id"];
        $perfil = $this->model->getUsuarioById($usuarioId);
        $estadisticas = $this->model->getEstadisticasJugador($usuarioId);

        $data = [
            'perfil' => $perfil,
            'estadisticas' => $estadisticas
        ];

        if (isset($_SESSION['usuario'])) {
            $data['usuario'] = $this->model->getUsuarioById($_SESSION['usuario']['id']);
        }

        $this->renderer->render("jugador", $data);
    }

    public function ranking()
    {
        $this->redirectNotAuthenticated();

        $ranking = $_GET['ranking'] ?? 'partidas';
        $data = [];

        if ($ranking === 'jugadores') {
            $jugadores = $this->model->getRankingJugadores();
            $data['jugadores'] = $this->agregarPosiciones($jugadores);
            $data['vista_activa'] = 'jugadores';
        } else {
            $partidas = $this->model->getRankingPartidas();
            $data['partidas'] = $this->agregarPosiciones($partidas);
            $data['vista_activa'] = 'partidas';
        }

        if (isset($_SESSION['usuario'])) {
            $data['usuario'] = $this->model->getUsuarioById($_SESSION['usuario']['id']);
        }

        $this->renderer->render("ranking", $data);
    }

    private function calcularResultadoPartida()
    {
        if (!$this->huboPartidaReal()) {
            return [0, "No se inició ninguna partida válida.", 0];
        }

        if (!isset($_SESSION["puntaje"])) {
            return [0, "No se registró un puntaje válido en la partida.", 0];
        }

        $puntajeFinal = $_SESSION["puntaje"] ?? 0;

        $nivelUsuario = $_SESSION["nivel_usuario"] ?? 1;
        $puntosGanados = $puntajeFinal * $nivelUsuario;

        return [$puntajeFinal, "¡Partida finalizada!", $puntosGanados];
    }

    private function huboPartidaReal()
    {
        return !empty($_SESSION["historial_partida"]) ||
            (isset($_SESSION["partida_iniciada"]) && $_SESSION["partida_iniciada"] === true) ||
            (isset($_SESSION["puntaje"]) && $_SESSION["puntaje"] > 0);
    }

    private function registrarRespuestaEnHistorial($preguntaFallada, $tiempoRespuesta, $nivelPregunta)
    {
        if (!isset($_SESSION["usuario"]) || !isset($_SESSION["pregunta_actual"]) || !isset($_SESSION["partida_id"])) {
            return;
        }

        $partidaId = $_SESSION["partida_id"];
        $preguntaId = $_SESSION["pregunta_actual"]["pregunta"]["pregunta_id"];

        $this->model->registrarRespuesta($partidaId, $preguntaId, $preguntaFallada, $tiempoRespuesta, $nivelPregunta);
    }

    private function agregarPosiciones($array)
    {
        if (!is_array($array)) return [];
        $posicion = 1;
        foreach ($array as &$item) {
            $item['posicion'] = $posicion++;
        }
        return $array;
    }

    private function agregarLetras($array)
    {
        if (!is_array($array)) return [];

        $posicion = 0;
        foreach ($array as &$item) {
            $item['posicion'] = chr(65 + $posicion);
            $posicion++;
        }
        return $array;
    }

    private function prepararDatosResumen($puntajeFinal, $mensaje, $puntosGanados)
    {
        $preguntaActual = $_SESSION["pregunta_actual"] ?? [];

        $datosPregunta = [];
        if (!empty($preguntaActual) && isset($preguntaActual['pregunta'])) {
            $datosPregunta = [
                "categoria" => $preguntaActual['pregunta']['categoria'] ?? 'Desconocida',
                "pregunta" => $preguntaActual['pregunta']['pregunta'] ?? 'No disponible',
                "respuestaCorrecta" => $preguntaActual['respuesta_correcta'] ?? 'No disponible',
            ];
        }

        $usuarioActualizado = [];
        if (isset($_SESSION["usuario"]["id"])) {
            $usuarioActualizado = $this->model->getUsuarioById($_SESSION["usuario"]["id"]);
        }

        return array_merge($datosPregunta, [
            "puntajeFinal" => $puntajeFinal,
            "puntosGanados" => $puntosGanados,
            "mensaje" => $mensaje,
            "codigoPartida" => $_SESSION["partida_codigo"] ?? 'N/A',
            "usuario" => $usuarioActualizado
        ]);
    }

    private function limpiarPartidaActual()
    {
        $variablesPartida = [
            "puntaje",
            "preguntas_vistas",
            "partida_iniciada",
            "pregunta_actual",
            "pregunta_respondida",
            "partida_desafio",
            "tiempo_inicio_pregunta",
            "partida_id",
            "partida_codigo" ,
            "historial_partida"
        ];

        foreach ($variablesPartida as $variable) {
            if (isset($_SESSION[$variable])) {
                unset($_SESSION[$variable]);
            }
        }
    }

    private function mostrarVistaPartida($partida)
    {
        if (!$this->partidaEsValida($partida)) {
            $this->generarNuevaPregunta();
            $partida = $_SESSION["pregunta_actual"] ?? [];
        }

        if (isset($partida['respuestas'])) {
            $partida['respuestas'] = $this->agregarLetras($partida['respuestas']);
        }

        $data = [
            "partida" => $partida,
            "puntaje" => $_SESSION["puntaje"] ?? 0,
            "nivel_usuario" => $_SESSION["nivel_usuario"] ?? 1,
            "tiempo_limite" => $this->validarTiempoRestante(),
            "codigo_partida" => $_SESSION["partida_codigo"] ?? 'N/A'
        ];

        if (isset($_SESSION['usuario'])) {
            $data['usuario'] = $this->model->getUsuarioById($_SESSION['usuario']['id']);
        }

        $this->renderer->render("partida", $data);
    }

    private function obtenerPreguntaActual()
    {
        if (isset($_SESSION["pregunta_actual"]) && !empty($_SESSION["pregunta_actual"])) {
            return $_SESSION["pregunta_actual"];
        }

        $this->generarNuevaPregunta();

        if (!isset($_SESSION["pregunta_actual"])) {
            $this->redirectTo("resumenPartida");
        }

        return $_SESSION["pregunta_actual"];
    }

    private function generarNuevaPregunta()
    {
        $preguntasVistas = $_SESSION["preguntas_vistas"] ?? [];
        $usuarioId = isset($_SESSION["usuario"]) ? $_SESSION["usuario"]["id"] : null;

        $nuevaPartida = $this->model->getPreguntaAleatoria($preguntasVistas, $usuarioId);

        if (empty($nuevaPartida)) {
            $this->finalizarPartida();
        }

        $this->guardarPreguntaEnSesion($nuevaPartida);
    }

    private function finalizarPartida()
    {
        unset($_SESSION["partida_iniciada"]);
        $this->redirectTo('resumenPartida');
    }

    private function guardarPreguntaEnSesion($partida)
    {
        $_SESSION["pregunta_actual"] = $partida;
        $_SESSION["preguntas_vistas"][] = $partida["pregunta"]["pregunta_id"];
        $_SESSION["pregunta_respondida"] = false;
        $_SESSION["tiempo_inicio_pregunta"] = time();
        $_SESSION["historial_partida"][] = $partida["pregunta"];
    }

    private function iniciarNuevaPartida()
    {
        $this->limpiarPartidaActual();
        $usuarioId = $_SESSION["usuario"]["id"];
        $partida = $this->model->iniciarPartida($usuarioId);

        if ($partida) {
            $_SESSION["partida_id"] = $partida["id"];
            $_SESSION["partida_codigo"] = $partida["codigo_partida"];
            $_SESSION["nivel_usuario"] = $partida["nivel_usuario"];
        }

        $_SESSION["puntaje"] = 0;
        $_SESSION["preguntas_vistas"] = [];
        $_SESSION["historial_partida"] = [];
        $_SESSION["partida_iniciada"] = true;
        $_SESSION["partida_desafio"] = false;
        unset($_SESSION["pregunta_actual"]);
        $this->redirectTo("jugarPartida");
    }

    private function procesarPartida()
    {
        $idRespuesta = $_POST["idRespuesta"] ?? null;

        if ($this->preguntaYaFueRespondida()) {
            $this->redirectTo("jugarPartida");
        }

        if ($this->tiempoExpirado()) {
            $this->procesarTiempoExpirado();
        }

        $tiempoRespuesta = $this->calcularTiempoRespuesta();
        $respuestaCorrecta = $this->model->verificarRespuesta($idRespuesta);
        $nivelPregunta = $_SESSION["pregunta_actual"]["pregunta"]["nivel"] ?? 1;
        $this->registrarRespuestaEnHistorial(!$respuestaCorrecta, $tiempoRespuesta, $nivelPregunta);

        if ($respuestaCorrecta) {
            $this->procesarRespuestaCorrecta();
        } else {
            $this->procesarRespuestaIncorrecta();
        }
    }

    private function calcularTiempoRespuesta()
    {
        if (!isset($_SESSION["tiempo_inicio_pregunta"])) {
            return 0;
        }

        return time() - $_SESSION["tiempo_inicio_pregunta"];
    }

    private function finalizarPartidaEnBD($puntajeFinal)
    {
        if (isset($_SESSION["partida_id"])) {
            $this->model->finalizarPartida($_SESSION["partida_id"], $puntajeFinal);
        }
    }

    private function tiempoExpirado()
    {
        if (!isset($_SESSION["tiempo_inicio_pregunta"])) {
            return true;
        }

        $tiempoTranscurrido = time() - $_SESSION["tiempo_inicio_pregunta"];
        return $tiempoTranscurrido > 10;
    }

    private function procesarTiempoExpirado()
    {
        $tiempoRespuesta = $this->calcularTiempoRespuesta();
        $nivelPregunta = $_SESSION["pregunta_actual"]["pregunta"]["nivel"] ?? 1;
        $this->registrarRespuestaEnHistorial(true, $tiempoRespuesta, $nivelPregunta);
        $_SESSION["pregunta_respondida"] = true;
        $this->redirectTo("resumenPartida");
    }

    private function preguntaYaFueRespondida()
    {
        return isset($_SESSION["pregunta_respondida"]) &&
            $_SESSION["pregunta_respondida"] === true;
    }

    private function procesarRespuestaCorrecta()
    {
        $_SESSION["puntaje"]++;
        $_SESSION["pregunta_respondida"] = true;
        $this->generarNuevaPregunta();
        $this->redirectTo("jugarPartida");
    }

    private function procesarRespuestaIncorrecta()
    {
        $this->redirectTo("resumenPartida");
    }

    private function partidaEsValida($partida)
    {
        return isset($partida["pregunta"]) && isset($partida["respuestas"]);
    }

    private function partidaEstaIniciada()
    {
        return isset($_SESSION["partida_iniciada"]) && $_SESSION["partida_iniciada"] === true;
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

    private function redirectToIndex()
    {
        $this->redirectToLobby();
    }

    private function redirectToLobby()
    {
        header("Location: /user/lobby");
        exit();
    }

    private function redirectTo($method)
    {
        header("Location: /game/$method");
        exit();
    }

    private function validarTiempoRestante()
    {
        $tiempoLimite = 10;

        if (!isset($_SESSION["tiempo_inicio_pregunta"])) {
            $_SESSION["tiempo_inicio_pregunta"] = time();
        }

        $tiempoTranscurrido = time() - $_SESSION["tiempo_inicio_pregunta"];
        $tiempoRestante = $tiempoLimite - $tiempoTranscurrido;

        if ($tiempoRestante <= 0) {
            $this->procesarTiempoExpirado();
            exit();
        }
        return $tiempoRestante;
    }
}