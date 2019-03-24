<?php
declare (strict_types = 1);
namespace App;

use App\User;
use Dotenv\Dotenv;
use Firebase\JWT\JWT;

class Auth
{
    public $response;
    public function __construct()
    {
        $dotenv = Dotenv::create(dirname(__DIR__));
        $dotenv->load();
        $this->user = new User;
    }

    public function generateToken($username)
    {
        $token = array(
            "iss" => $_ENV['APP_ISS'],
            "aud" => $_ENV['APP_AUD'],
            "iat" => $_ENV['APP_IAT'],
            "nbf" => $_ENV['APP_NBF'],
            "exp" => time() + 3666600,
            "data" => array(
                "username" => $username,
            ),
        );
        $jwt = JWT::encode($token, $_ENV['APP_KEY']);
    }

    public function validateToken()
    {
        if (isset($_SERVER["HTTP_AUTHORIZATION"])) {

            list($type, $data) = array_pad(explode(" ", $_SERVER["HTTP_AUTHORIZATION"], 2), 2, null);
            //list($type, $data) = explode(" ", $_SERVER["HTTP_AUTHORIZATION"], 2);
            if (strcasecmp($type, "Bearer") == 0) {
                try {
                    JWT::$leeway = 60; // $leeway in seconds for catching DomainException when token is incorrect
                    $decoded = JWT::decode($data, $_ENV['APP_KEY'], array('HS256'));
                } catch (\Exception $e) {
                    return false;
                }
                $username = $decoded->data->username;
                if (isset($username) and $this->user->isRegistredUser($username)) {
                    return true;
                } else {
                    return false;
                }

            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getUsernameFromToken()
    {
        if ($this->validateToken()) {
            list($type, $data) = explode(" ", $_SERVER["HTTP_AUTHORIZATION"], 2);
            $decoded = JWT::decode($data, $_ENV['APP_KEY'], array('HS256'));
            $username = $decoded->data->username;
        }
        return $username;
    }

}
