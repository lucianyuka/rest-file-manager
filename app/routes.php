<?php
//declare (strict_types = 1);

use App\Auth;
use App\Response;
use Bramus\Router\Router;

$response = new Response();
$auth = new Auth;

// Create Router instance
$router = new Router();
$router->setNamespace('\App');

$router->before('GET|POST|PUT|DELETE', '/.*', function () use ($auth, $response) {
    if (!$auth->validateToken()) {
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
$router->get('/info/{path}', 'Main@showInfo');

$router->post('/upload', 'Main@upload');

$router->post('/add-folder', 'Main@addFolder');

$router->put('/rename', 'Main@rename');

$router->post('/copy', 'Main@copy');

$router->post('/copy-folder', 'Main@copyFolder');

$router->delete('/delete', 'Main@delete');

$router->delete('/force-delete', 'Main@forceDelete');

$router->post('/add-user', 'Main@addUser');

$router->get('/user/{username} ', 'Main@userInfo');

$router->get('/users', 'Main@listUsers');

$router->put('/update-user/{username}', 'Main@updateUser');

$router->delete('/delete-user/{username}', 'Main@deleteUser');

// Run the router
$router->run();
