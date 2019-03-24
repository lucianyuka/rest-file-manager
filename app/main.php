<?php
declare (strict_types = 1);

namespace App;

use App\Auth;
use App\Response;
use App\User;
use Dotenv\Dotenv;

class Main
{
    private $username;
    private $response;
    private $user;
    private static $aclJSON;
    public function __construct()
    {
        $dotenv = Dotenv::create(dirname(__DIR__));
        $dotenv->load();
        $this::$aclJSON = dirname(__DIR__) . $_ENV['JSON_PATH'];
        $this->response = new Response();
        $this->user = new User;
        $this->auth = new Auth;
        $this->username = $this->auth->getUsernameFromToken();

    }

    public function showInfo($data)
    {

        if ($this->user->hasThePerm($this->username, "read-file")) {
            $this->response->setStatus('200');
            $this->response->setUserCred($this->username);
            $this->response->setContent($data);
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }

    }

    public function upload()
    {

        if ($this->user->hasThePerm($this->username, "create-file")) {
            $this->response->setStatus('200');
            $this->response->setContent("OK");
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function addFolder()
    {
        if ($this->user->hasThePerm($this->username, "create-file")) {
            $this->response->setStatus('200');
            $this->response->setContent("OK");
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function rename()
    {
        if ($this->user->hasThePerm($this->username, "update-file")) {
            $this->response->setStatus('200');
            $this->response->setContent("OK");
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function copy()
    {
        if ($this->user->hasThePerm($this->username, "update-file")) {
            $this->response->setStatus('200');
            $this->response->setContent("OK");
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function copyFolder()
    {
        if ($this->user->hasThePerm($this->username, "update-file")) {
            $this->response->setStatus('200');
            $this->response->setContent("OK");
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function delete()
    {
        if ($this->user->hasThePerm($this->username, "delete-file")) {
            $this->response->setStatus('200');
            $this->response->setContent("OK");
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function forceDelete()
    {
        if ($this->user->hasThePerm($this->username, "delete-file")) {
            $this->response->setStatus('200');
            $this->response->setContent("OK");
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function addUser()
    {
        if (!$this->user->hasThePerm($this->username, "create-user")) {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();
        }

        $input = file_get_contents('php://input');
        $object = json_decode($input,true);

        if ($object == NULL){
            $this->response->setStatus('415');
            $this->response->setContent("Invalid Format");
            $this->response->finish();
        }

        if (!array_key_exists("username",$object) or !array_key_exists("permissions_string",$object))
        {
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

        if (count($perms_input) != 8)
        {
            $this->response->setStatus('400');
            $this->response->setContent("Permissions too long or too short");
            $this->response->finish();
        }

        $target_arr1 = explode('-', 'cf-rf-uf-df-cu-ru-uu-du');
        //$target_arr2 = explode('-', 'xx-xx-xx-xx-xx-xx-xx-xx');
        //$this->response->setContent(count(array_intersect($target_arr1, $perms_input)). " - ". count(array_intersect($target_arr2, $perms_input)) .' --- '.count(array_diff($target_arr1, $perms_input)). " - ". count(array_diff($target_arr2, $perms_input)));
        foreach ($perms_input as $key => $val) {
            foreach ($target_arr1 as $keyt1 => $valuet1) {
                if ($key ==  $keyt1) {
                    if ($val != $valuet1 and $val != 'xx'){
                        $this->response->setStatus('400');
                        $this->response->setContent("Permissions Not Accurate");
                        $this->response->finish();
                    }

                }
            }
        }

        $aclJSON = $this::$aclJSON;
        $jsonFile = file_get_contents($aclJSON);
        $json_a = json_decode($jsonFile, true);
        $output =array_merge($json_a,array($object['username']=>$object['permissions_string']));
        file_put_contents($aclJSON, json_encode($output, JSON_PRETTY_PRINT));



        $this->response->setStatus('200');
        $this->response->setContent($object['username']. " - ". $object['permissions_string']);
        $this->response->finish();



    }

    public function userInfo()
    {
        if ($this->user->hasThePerm($this->username, "read-user")) {
            $this->response->setStatus('200');
            $this->response->setContent("OK");
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function listUsers()
    {
        if ($this->user->hasThePerm($this->username, "read-user")) {
            $this->response->setStatus('200');
            $this->response->setContent("OK");
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function updateUser()
    {
        if ($this->user->hasThePerm($this->username, "update-users-permissions")) {
            $this->response->setStatus('200');
            $this->response->setContent("OK");
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function deleteUser()
    {
        if ($this->user->hasThePerm($this->username, "delete-user")) {
            $this->response->setStatus('200');
            $this->response->setContent("OK");
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

}
