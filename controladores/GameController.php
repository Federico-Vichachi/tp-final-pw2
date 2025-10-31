<?php

class GameController
{
    private $renderer;
    private $model;

    public function __construct($GameModel, $renderer)
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

    public function resumenPartida()
    {
        $this->redirectNotAuthenticated();

        list($puntajeFinal, $mensaje) = $this->calcularResultadoPartida();

        $data = $this->prepararDatosResumen($puntajeFinal, $mensaje);

        $this->limpiarPartidaActual();
        $this->renderer->render("resumenPartida", $data);
    }

    public function ranking()
    {
        $this->redirectNotAuthenticated();
        $this->renderer->render("ranking", []);
    }

    private function desafiar()
    {
        //Debe poder llamarse desde el ranking
    }

    private function calcularResultadoPartida()
    {
        if (!isset($_SESSION["puntaje"])) {
            return ['0', "No se registró un puntaje válido en la partida."];
        } else {
            return [$_SESSION["puntaje"], "Respuesta incorrecta"];
        }
    }

    private function prepararDatosResumen($puntajeFinal, $mensaje)
    {
        $preguntaActual = $_SESSION["pregunta_actual"] ?? [];

        return [
            "categoria" => $preguntaActual['pregunta']['categoria'] ?? 'Desconocida',
            "pregunta" => $preguntaActual['pregunta']['pregunta'] ?? 'No disponible',
            "respuestaCorrecta" => $preguntaActual['respuesta_correcta'] ?? 'No disponible',
            "puntajeFinal" => $puntajeFinal,
            "mensaje" => $mensaje
        ];
    }

    private function limpiarPartidaActual()
    {
        $variablesPartida = [
            "puntaje",
            "preguntas_vistas",
            "partida_iniciada",
            "pregunta_actual",
            "pregunta_respondida",
            "partida_desafio"
        ];

        foreach ($variablesPartida as $variable) {
            unset($_SESSION[$variable]);
        }
    }

    private function mostrarVistaPartida($partida)
    {
        if (!$this->partidaEsValida($partida)) {
            $this->generarNuevaPregunta();
            $partida = $_SESSION["pregunta_actual"] ?? [];
        }

        $data = [
            "partida" => $partida,
            "puntaje" => $_SESSION["puntaje"] ?? 0
        ];

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

        $nuevaPartida = $this->model->getPreguntaAleatoria($preguntasVistas);

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
    }

    private function iniciarNuevaPartida()
    {
        $this->limpiarPartidaActual();

        $_SESSION["puntaje"] = 0;
        $_SESSION["preguntas_vistas"] = [];

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

        if ($this->model->verificarRespuesta($idRespuesta)) {
            $this->procesarRespuestaCorrecta();
        } else {
            $this->procesarRespuestaIncorrecta();
        }
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

    private function redirectToLobby(){
        header("Location: /user/lobby");
        exit();
    }

    private function redirectTo($method)
    {
        header("Location: /game/$method");
        exit();
    }
}