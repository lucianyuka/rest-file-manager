<?php
declare (strict_types = 1);

namespace App;

use App\Auth;
use App\Response;
use App\User;
use Dotenv\Dotenv;
use League\Flysystem\Adapter\Local as Adapter;
use League\Flysystem\Filesystem;

class Main
{
    private $username;
    private $response;
    private $user;
    private $auth;
    private $filesystem;

    private static $aclJSON;
    private static $uploadsFolder;
    private static $tempFolder;

    public function __construct()
    {
        $dotenv = Dotenv::create(dirname(__DIR__));
        $dotenv->load();
        $this::$aclJSON = dirname(__DIR__) . $_ENV['JSON_PATH'];
        $this::$uploadsFolder = $_ENV['UPLOADS_FOLDER'];
        $this::$tempFolder = $_ENV['TEMP_FOLDER'];
        $this->filesystem = new Filesystem(new Adapter($this::$uploadsFolder));
        $this->response = new Response();
        $this->user = new User;
        $this->auth = new Auth;
        $this->username = $this->auth->getUsernameFromToken();

    }

    public function showInfo($data)
    {
        if (!$this->user->hasThePerm($this->username, "read-file")) {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();
        }

        $this->response->setStatus('200');
        $this->response->setContent($data);
        $this->response->finish();

    }

    public function upload()
    {
        if (!$this->user->hasThePerm($this->username, "create-file")) {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();
        }

        if (count($_POST) == 0 or count($_FILES) ==0) {
            $this->response->setStatus('415');
            $this->response->setContent("Invalid Format");
            $this->response->finish();
        }

        if (!$_FILES['file']) {
            $this->response->setStatus('400');
            $this->response->setContent("Missing Property");
            $this->response->finish();
        }

        /* array (size=5)
        'name' => string 'v7bsnwekQ_I.jpg' (length=15)
        'type' => string 'image/jpeg' (length=10)
        'tmp_name' => string '/tmp/phpajFZqT' (length=14)
        'error' => int 0
        'size' => int 241673 */
        //substr($_POST['path'], -1)!= '/'
        $stream = fopen($_FILES['file']['tmp_name'], 'r+');
        $this->filesystem->writeStream(
            $_POST['path'] . DIRECTORY_SEPARATOR . $_FILES['file']['name'],
            $stream
        );

        if (is_resource($stream)) {
            fclose($stream);
        }

        $this->response->setStatus('200');
        $this->response->setContent("File " . $_FILES['file']['name']. " uploaded successfully to ".$_POST['path']);
        $this->response->finish();

    }

    public function addFolder()
    {
        if (!$this->user->hasThePerm($this->username, "create-file")) {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();
        }

        $this->response->setStatus('200');
        $this->response->setContent("OK");
        $this->response->finish();

    }

    public function rename()
    {
        if (!$this->user->hasThePerm($this->username, "update-file")) {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();
        }

        $this->response->setStatus('200');
        $this->response->setContent("OK");
        $this->response->finish();

    }

    public function copy()
    {
        if (!$this->user->hasThePerm($this->username, "update-file")) {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();
        }

        $this->response->setStatus('200');
        $this->response->setContent("OK");
        $this->response->finish();

    }

    public function copyFolder()
    {
        if (!$this->user->hasThePerm($this->username, "update-file")) {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();
        }

        $this->response->setStatus('200');
        $this->response->setContent("OK");
        $this->response->finish();

    }

    public function delete()
    {
        if (!$this->user->hasThePerm($this->username, "delete-file")) {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();
        }

        $this->response->setStatus('200');
        $this->response->setContent("OK");
        $this->response->finish();

    }

    public function forceDelete()
    {
        if (!$this->user->hasThePerm($this->username, "delete-file")) {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();
        }

        $this->response->setStatus('200');
        $this->response->setContent("OK");
        $this->response->finish();

    }

    public function addUser()
    {
        if (!$this->user->hasThePerm($this->username, "create-user")) {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();
        }

        $input = file_get_contents('php://input');
        $object = json_decode($input, true);

        if ($object == null) {
            $this->response->setStatus('415');
            $this->response->setContent("Invalid Format");
            $this->response->finish();
        }

        if (!array_key_exists("username", $object) or !array_key_exists("permissions_string", $object)) {
            $this->response->setStatus('400');
            $this->response->setContent("Missing Property");
            $this->response->finish();
        }
        if ($this->user->isRegistredUser($object['username'])) {
            $this->response->setStatus('400');
            $this->response->setContent("Username Not Available");
            $this->response->finish();
        }
        $perms_input = explode('-', $object['permissions_string']);

        if (count($perms_input) != 8) {
            $this->response->setStatus('400');
            $this->response->setContent("Permissions too long or too short");
            $this->response->finish();
        }

        $target_arr1 = explode('-', 'cf-rf-uf-df-cu-ru-uu-du');
        //$target_arr2 = explode('-', 'xx-xx-xx-xx-xx-xx-xx-xx');
        //$this->response->setContent(count(array_intersect($target_arr1, $perms_input)). " - ". count(array_intersect($target_arr2, $perms_input)) .' --- '.count(array_diff($target_arr1, $perms_input)). " - ". count(array_diff($target_arr2, $perms_input)));
        foreach ($perms_input as $key => $val) {
            foreach ($target_arr1 as $keyt1 => $valuet1) {
                if ($key == $keyt1) {
                    if ($val != $valuet1 and $val != 'xx') {
                        $this->response->setStatus('400');
                        $this->response->setContent("Permissions Not Accurate");
                        $this->response->finish();
                    }

                }
            }
        }

        $json_a = $this->jsonToArray($this::$aclJSON);

        $output = array_merge($json_a, array(strtolower($object['username']) => $object['permissions_string']));
        file_put_contents($this::$aclJSON, json_encode($output, JSON_PRETTY_PRINT));

        $token_generated = $this->auth->generateToken($object['username']);

        $this->response->setStatus('200');
        $this->response->setUserCred($token_generated);
        $this->response->setContent("User " . $object['username'] . " with permissions " . $object['permissions_string'] . " was added successfully");
        $this->response->finish();

    }

    public function userInfo($data)
    {
        if (!$this->user->hasThePerm($this->username, "read-user")) {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();
        }

        if (!$this->user->isRegistredUser($data)) {
            $this->response->setStatus('400');
            $this->response->setContent("Username Not Available");
            $this->response->finish();
        }

        $json_a = $this->jsonToArray($this::$aclJSON);

        foreach ($json_a as $key => $val) {
            if ($key == strtolower($data)) {
                $this->response->setStatus('200');
                $this->response->setContent("User " . $key . " has the following permissions " . $val);
                $this->response->finish();
            }
        }

    }

    public function listUsers()
    {
        if (!$this->user->hasThePerm($this->username, "read-user")) {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();
        }

        $json_a = $this->jsonToArray($this::$aclJSON);
        $str = '';
        foreach ($json_a as $key => $val) {
            $str .= $key . ", ";
        }

        $this->response->setStatus('200');
        $this->response->setContent("There are " . count($json_a) . " Users : " . rtrim($str, ', '));
        $this->response->finish();

    }

    public function updateUser()
    {
        if (!$this->user->hasThePerm($this->username, "update-users-permissions")) {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();
        }

        $input = file_get_contents('php://input');
        //parse_str(file_get_contents("php://input"), $input);

        $object = json_decode($input, true);

        if ($object == null) {
            $this->response->setStatus('415');
            $this->response->setContent("Invalid Format");
            $this->response->finish();
        }

        if (!array_key_exists("username", $object) or !array_key_exists("permissions_string", $object)) {
            $this->response->setStatus('400');
            $this->response->setContent("Missing Property");
            $this->response->finish();
        }

        if (!$this->user->isRegistredUser($object['username'])) {
            $this->response->setStatus('400');
            $this->response->setContent("Username Not Available");
            $this->response->finish();
        }
        $perms_input = explode('-', $object['permissions_string']);

        if (count($perms_input) != 8) {
            $this->response->setStatus('400');
            $this->response->setContent("Permissions too long or too short");
            $this->response->finish();
        }

        $target_arr1 = explode('-', 'cf-rf-uf-df-cu-ru-uu-du');

        foreach ($perms_input as $key => $val) {
            foreach ($target_arr1 as $keyt1 => $valuet1) {
                if ($key == $keyt1) {
                    if ($val != $valuet1 and $val != 'xx') {
                        $this->response->setStatus('400');
                        $this->response->setContent("Permissions Not Accurate");
                        $this->response->finish();
                    }
                }
            }
        }

        $json_a = $this->jsonToArray($this::$aclJSON);

        foreach ($json_a as $key => &$val) {
            if ($key == strtolower($object['username'])) {
                if ($val == $object['permissions_string']) {
                    $this->response->setStatus('200');
                    $this->response->setContent("Nothing to Update");
                    $this->response->finish();
                } else {
                    $val = $object['permissions_string'];
                }
            }
        }

        file_put_contents($this::$aclJSON, json_encode($json_a, JSON_PRETTY_PRINT));

        $this->response->setStatus('200');
        $this->response->setContent("User " . $object['username'] . " was succefully updated with the following permissions " . $object['permissions_string']);
        $this->response->finish();

    }

    public function deleteUser()
    {
        if (!$this->user->hasThePerm($this->username, "delete-user")) {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();
        }

        $input = file_get_contents('php://input');
        //parse_str(file_get_contents("php://input"), $input);

        $object = json_decode($input, true);

        if ($object == null) {
            $this->response->setStatus('415');
            $this->response->setContent("Invalid Format");
            $this->response->finish();
        }

        if (!array_key_exists("username", $object)) {
            $this->response->setStatus('400');
            $this->response->setContent("Missing Property");
            $this->response->finish();
        }

        if (!$this->user->isRegistredUser($object['username'])) {
            $this->response->setStatus('400');
            $this->response->setContent("Username Not Available");
            $this->response->finish();
        }

        $json_a = $this->jsonToArray($this::$aclJSON);

        unset($json_a[strtolower($object['username'])]);

        file_put_contents($this::$aclJSON, json_encode($json_a, JSON_PRETTY_PRINT));

        $this->response->setStatus('200');
        $this->response->setContent("User " . $object['username'] . " was succefully deleted!");
        $this->response->finish();

    }

    private function jsonToArray($file)
    {
        $jsonFile = file_get_contents($file);
        $json_a = json_decode($jsonFile, true);
        return $json_a;
    }

}
