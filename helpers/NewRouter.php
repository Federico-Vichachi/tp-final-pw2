<?php

class NewRouter
{


    private $configFactory;
    private $defaultController;
    private $defaultMethod;

    public function __construct($configFactory, $defaultController,$defaultMethod)
    {

        $this->configFactory = $configFactory;
        $this->defaultController = $defaultController;
        $this->defaultMethod = $defaultMethod;
    }

    public function executeController($controllerParam, $methodParam)
    {
        $controller = $this->getControllerFrom($controllerParam);
        $this->executeMethodFromController($controller, $methodParam);
    }

    private function getControllerFrom($controllerName)
    {
        $controllerName = $this->getControllerName($controllerName);
        $controller = $this->configFactory->get($controllerName);

        if ($controller == null) {
            header("location: /");
            exit;
        }

        return $controller;
    }

    private function executeMethodFromController($controller, $methodName)
    {
        call_user_func(
            array(
                $controller,
                $this->getMethodName($controller, $methodName)
            )
        );
    }

    public function getControllerName($controllerName)
    {
        return $controllerName ?
            ucfirst($controllerName) . 'Controller' :
            $this->defaultController;
    }

    public function getMethodName($controller, $methodName)
    {
        return method_exists($controller, $methodName) ? $methodName : $this->defaultMethod;
    }
}