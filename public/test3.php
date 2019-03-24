<?php
date_default_timezone_set('Europe/Bucharest');
require_once dirname(__DIR__) . '/vendor/autoload.php';
use \Firebase\JWT\JWT;
$response = new App\Response();
$user = new App\User;

if ($user->isRegistredUser("mohamed")) {
    echo "yesss";
} else {
    echo "nooo";
}

echo '----------------------------';
echo '<br>';
if ($user->hasThePerm("mohamed", "create-user")) {
    echo "yesss";
} else {
    echo "nooo";
}