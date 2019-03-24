<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$user = new App\User;

$run = new \Whoops\Run;
$handler = new \Whoops\Handler\PrettyPageHandler;
$JsonHandler = new \Whoops\Handler\JsonResponseHandler;
$run->pushHandler($JsonHandler);
$run->pushHandler($handler);
$run->register();

// Create Router instance
$router = new \Bramus\Router\Router();
$response = new App\Response();
$auth = new App\Auth;

 $router->before('GET|POST|PUT|DELETE', '/.*', function () use ($auth,$response) {
    if ($auth->validateToken()) {
        $response->setStatus(200);
        $response->setUserCred($auth->getUsernameFromToken());

    }else{
        $response->setStatus(401);
        $response->setContent('Missing1 or invalid API Key.');
        $response->finish();
    }
});

// Override the standard router 404
$router->set404(function () use ($response) {
    $response->setStatus('404');
    $response->setContent('Invalid resource');
    $response->finish();
});

// Define our routes
$router->get('/', function () use ($response) {
    $response->setContent('Welcome!');
    $response->finish();
});

// Login
$router->post('/login', function () use ($response, $user) {
    if (!isset($_SERVER['PHP_AUTH_USER']) || !$user->is_permitted_user($_SERVER['PHP_AUTH_USER'])) {
        $response->setStatus(401);
        $response->setContent('Missing or invalid API Key.');
        $response->finish();
    }
    /*  foreach ($_SERVER as $key_name => $key_value) {
print $key_name . " = " . $key_value . "<br>";
} */
});

// … (more routes here)
$router->get('/info/{path}', 'App\HomeController@info');

$router->post('/upload', 'App\HomeController@upload');

$router->post('/add-folder', 'App\HomeController@addFolder');

$router->post('/rename', 'App\HomeController@rename');

$router->post('/copy', 'App\HomeController@copy');

$router->post('/copy-folder', 'App\HomeController@copyFolder');

$router->post('/delete', 'App\HomeController@delete');

$router->post('/force-delete', 'App\HomeController@forceDelete');

$router->post('/add-user', 'App\HomeController@addUser');

$router->get('/user/{username} ', 'App\HomeController@userInfo');

$router->get('/users', 'App\HomeController@listUsers');

$router->post('/update-user/{username}', 'App\HomeController@updateUser');

$router->post('/delete-user/{username}', 'App\HomeController@deleteUser');

// Run the router
$router->run();
