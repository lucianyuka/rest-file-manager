<?php

date_default_timezone_set('Europe/Bucharest');
require_once dirname(__DIR__) . '/vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Dotenv\Dotenv;
$dotenv = Dotenv::create(dirname(__DIR__));
$dotenv->load();

$response = new App\Response();
$user = new App\User;

if (!isset($_SERVER['PHP_AUTH_USER']) || !$user->isRegistredUser($_SERVER['PHP_AUTH_USER'])) {
    $response->setStatus(401);
    $response->setContent('Missing or invalid API Key.');
    $response->finish();
} else {
    // variables used for jwt
    $key = "example_key";
    $iss = "http://dev.local";
    $aud = "http://dev.local";
    $iat = 1356999524;
    $nbf = 1357000000;
    $token = array(
        "iss" => $_ENV['APP_ISS'],
        "aud" => $_ENV['APP_AUD'],
        "iat" => $_ENV['APP_IAT'],
        "nbf" => $_ENV['APP_NBF'],
        "exp" => time() + 3666600,
        "data" => array(
            "username" => $_SERVER['PHP_AUTH_USER'],
        ),
    );
    $jwt = JWT::encode($token, $key);

    //setcookie("token", $jwt, time() + 600);
    $response->setStatus('200');
    $response->setContent($jwt . ' -- ' . $_SERVER['PHP_AUTH_USER']);
    $response->finish();
}

