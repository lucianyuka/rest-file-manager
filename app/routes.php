<?php
declare (strict_types = 1);

use App\Auth;
use App\Response;
use Bramus\Router\Router;

// Create Router instance
$router = new Router();

$response = new Response();
$auth = new Auth;

$router->before('GET|POST|PUT|DELETE', '/.*', function () use ($auth, $response) {
    if ($auth->validateToken()) {
        $response->setStatus(200);
        $response->setUserCred($auth->getUsernameFromToken());
    } else {
        $response->setStatus(401);
        $response->setContent('Missing or invalid API Key.');
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
    $response->setStatus('200');
    $response->setUserCred("OK");
    $response->setContent('Welcome!');
    $response->finish();
});

// … (more routes here)
$router->get('/info/{path}', 'App\HomeController@showInfo');

$router->post('/upload', 'App\HomeController@upload');

$router->post('/add-folder', 'App\HomeController@addFolder');

$router->put('/rename', 'App\HomeController@rename');

$router->post('/copy', 'App\HomeController@copy');

$router->post('/copy-folder', 'App\HomeController@copyFolder');

$router->delete('/delete', 'App\HomeController@delete');

$router->delete('/force-delete', 'App\HomeController@forceDelete');

$router->post('/add-user', 'App\HomeController@addUser');

$router->get('/user/{username} ', 'App\HomeController@userInfo');

$router->get('/users', 'App\HomeController@listUsers');

$router->put('/update-user/{username}', 'App\HomeController@updateUser');

$router->delete('/delete-user/{username}', 'App\HomeController@deleteUser');

// Run the router
$router->run();
