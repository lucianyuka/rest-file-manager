<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$run = new \Whoops\Run;
$handler = new \Whoops\Handler\PrettyPageHandler;
$JsonHandler = new \Whoops\Handler\JsonResponseHandler;
$run->pushHandler($JsonHandler);
$run->pushHandler($handler);
$run->register();


// Create Router instance
$router = new \Bramus\Router\Router();

// Define routes
// ...

// Run it!
$router->run();

