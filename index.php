<?php
session_start();

include("helpers/ConfigFactory.php");

$configFactory = new ConfigFactory();
$router = $configFactory->get("router");

$router->executeController($_GET["controller"], $_GET["method"] ?? "");