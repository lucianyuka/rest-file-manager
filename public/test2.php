<?php
date_default_timezone_set('Europe/Bucharest');
require_once dirname(__DIR__) . '/vendor/autoload.php';
use \Firebase\JWT\JWT;
$response = new App\Response();
$user = new App\User;

if (isset($_SERVER["HTTP_AUTHORIZATION"])) {
    list($type, $data) = explode(" ", $_SERVER["HTTP_AUTHORIZATION"], 2);
    if (strcasecmp($type, "Bearer") == 0) {
        try {
            $decoded = JWT::decode($data, $_ENV['APP_KEY'], array('HS256'));
            $username = $decoded->data->username;

            if ($user->isRegistredUser($username)){

                $response->setStatus(200);
                $response->setContent($username);
    $response->finish();
            } else {
                $response->setStatus(401);
                $response->setContent('Missing1 or invalid API Key.');
                $response->finish();
            }

        } catch (Exception $e) {
            $response->setStatus('401');
                $response->setContent(array(
                    "message" => "Access denied.",
                    "error" => $e->getMessage(),
                ));
                $response->finish();
        }
    } else {
        $response->setStatus(401);
        $response->setContent('Missing2 or invalid API Key.');
        $response->finish();
    }
} else {
    $response->setStatus(401);
    $response->setContent('Missing3 or invalid API Key.');
    $response->finish();
}