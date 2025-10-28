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
        $method = $this->getMethodName($controller, $methodName);
        $reflection = new ReflectionMethod($controller, $method);
        if (!$reflection->isPublic()) {
            $method = $this->defaultMethod;
        }
        call_user_func([$controller, $method]);
    }

    public function getControllerName($controllerName)
    {
        return $controllerName ?
            ucfirst($controllerName) . 'Controller' :
            $this->defaultController;
    }

    public function getMethodName($controller, $methodName)
    {
        if (method_exists($controller, $methodName)) {
            $reflection = new ReflectionMethod($controller, $methodName);
            if ($reflection->isPublic()) {
                return $methodName;
            }
        }
        return $this->defaultMethod;
    }
}