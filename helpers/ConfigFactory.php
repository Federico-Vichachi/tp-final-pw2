<?php
include_once("helpers/MyConexion.php");
include_once("helpers/NewRouter.php");
include_once("controladores/UserController.php");
include_once("modelos/UserModel.php");
include_once('vendor/mustache/src/Mustache/Autoloader.php');
include_once ("helpers/MustacheRenderer.php");

class ConfigFactory
{
    private $config;
    private $objetos;

    private $conexion;
    private $renderer;

    public function __construct()
    {
        $this->config = parse_ini_file("config/config.ini");

        $this->conexion= new MyConexion(
            $this->config["server"],
            $this->config["user"],
            $this->config["pass"],
            $this->config["database"]
        );

        $this->renderer = new MustacheRenderer("vistas");
        $this->objetos["router"] = new NewRouter($this, "UserController", "base");
        $this->objetos["UserController"] = new UserController(new UserModel($this->conexion), $this->renderer);

    }

    public function get($objectName)
    {
        return $this->objetos[$objectName];
    }
}