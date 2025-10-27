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

    public function nuevaPartida(){
        $this->redirectNotAuthenticated();

        $_SESSION["puntaje"] = 0;
        $_SESSION["preguntas_vistas"] = [];
        $_SESSION["partida_iniciada"] = true;

        $this->redirectTo("jugarPartida");
    }

    public function jugarPartida(){
        $this->redirectNotAuthenticated();

        if (!isset($_SESSION["partida_iniciada"]) || $_SESSION["partida_iniciada"] !== true) {
            $this->redirectTo("nuevaPartida");
            exit();
        }

        $preguntasVistas = $_SESSION["preguntas_vistas"] ?? [];
        $partida = $this->model->getPartidaAleatoria($preguntasVistas);

        if(empty($partida)){
            unset($_SESSION["partida_iniciada"]);
            $this->resumenPartida("Termino todas las preguntas, pero tendria que ser infinito?");
            exit();
        }

        $_SESSION["preguntas_vistas"][] = $partida["pregunta"]["pregunta_id"];

        $data = [
            "partida" => $partida,
            "puntaje" => $_SESSION["puntaje"]
        ];

        $this->renderer->render("partida", $data);
    }

    public function procesarPartida(){
        $this->redirectNotAuthenticated();
        $idRespuesta = $_POST["idRespuesta"] ?? null;

        if($this->model->verificarRespuesta($idRespuesta)){
            $_SESSION["puntaje"]++;
            $this->redirectTo("jugarPartida");
        }else{
            $this->redirectTo("resumenPartida");
        }
    }

    public function resumenPartida(){
        $this->redirectNotAuthenticated();

        if (!isset($_SESSION["puntaje"])) {
            $puntajeFinal = '0';
            $mensaje = "No se registró un puntaje válido en la partida.";
        } else {
            $puntajeFinal = $_SESSION["puntaje"];
            $mensaje = "Respuesta incorrecta";
        }

        unset($_SESSION["puntaje"], $_SESSION["preguntas_vistas"], $_SESSION["partida_iniciada"]);
        $data = [
            "puntajeFinal" => $puntajeFinal,
            "mensaje" => $mensaje
        ];

        $this->renderer->render("resumenPartida", $data);
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

    public function redirectToLobby(){
        header("Location: /user/lobby");
        exit();
    }

    private function redirectTo($method)
    {
        header("Location: $method");
        exit();
    }
}