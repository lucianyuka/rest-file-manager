<?php
namespace App;

use App\Response;
use App\User;
use Dotenv\Dotenv;

class HomeController
{
    public $response;
    public function __construct()
    {
        $dotenv = Dotenv::create(dirname(__DIR__));
        $dotenv->load();
        $this->user = new User;
        $this->response = new Response();
    }

    //echo $_ENV['APP_NAME'];

    public function info($data)
    {
        if ($this->user->hasThePerm($_SERVER['PHP_AUTH_USER'], "read-file")) {
            $this->response->setStatus('200');
            $this->response->setContent($data . '--' . $_SERVER['PHP_AUTH_USER']);
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }

    }

    public function upload($data)
    {
        if ($this->user->hasThePerm($_SERVER['PHP_AUTH_USER'], "create-file")) {
            $this->response->setStatus('200');
            $this->response->setContent($data[0] . '--' . $data[1] . '--' . $_SERVER['PHP_AUTH_USER']);
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function addFolder()
    {
        if ($this->user->hasThePerm($_SERVER['PHP_AUTH_USER'], "create-file")) {
            $this->response->setStatus('200');
            $this->response->setContent($data . '--' . $_SERVER['PHP_AUTH_USER']);
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function rename()
    {
        if ($this->user->hasThePerm($_SERVER['PHP_AUTH_USER'], "update-file")) {
            $this->response->setStatus('200');
            $this->response->setContent($data . '--' . $_SERVER['PHP_AUTH_USER']);
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function copy()
    {
        if ($this->user->hasThePerm($_SERVER['PHP_AUTH_USER'], "update-file")) {
            $this->response->setStatus('200');
            $this->response->setContent($data . '--' . $_SERVER['PHP_AUTH_USER']);
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function copyFolder()
    {
        if ($this->user->hasThePerm($_SERVER['PHP_AUTH_USER'], "update-file")) {
            $this->response->setStatus('200');
            $this->response->setContent($data . '--' . $_SERVER['PHP_AUTH_USER']);
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function delete()
    {
        if ($this->user->hasThePerm($_SERVER['PHP_AUTH_USER'], "delete-file")) {
            $this->response->setStatus('200');
            $this->response->setContent($data . '--' . $_SERVER['PHP_AUTH_USER']);
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function forceDelete()
    {
        if ($this->user->hasThePerm($_SERVER['PHP_AUTH_USER'], "delete-file")) {
            $this->response->setStatus('200');
            $this->response->setContent($data . '--' . $_SERVER['PHP_AUTH_USER']);
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function addUser()
    {
        if ($this->user->hasThePerm($_SERVER['PHP_AUTH_USER'], "create-user")) {
            $this->response->setStatus('200');
            $this->response->setContent($data . '--' . $_SERVER['PHP_AUTH_USER']);
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function userInfo()
    {
        if ($this->user->hasThePerm($_SERVER['PHP_AUTH_USER'], "read-user")) {
            $this->response->setStatus('200');
            $this->response->setContent($data . '--' . $_SERVER['PHP_AUTH_USER']);
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function listUsers()
    {
        if ($this->user->hasThePerm($_SERVER['PHP_AUTH_USER'], "read-user")) {
            $this->response->setStatus('200');
            $this->response->setContent($data . '--' . $_SERVER['PHP_AUTH_USER']);
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function updateUser()
    {
        if ($this->user->hasThePerm($_SERVER['PHP_AUTH_USER'], "update-users-permissions")) {
            $this->response->setStatus('200');
            $this->response->setContent($data . '--' . $_SERVER['PHP_AUTH_USER']);
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function deleteUser()
    {
        if ($this->user->hasThePerm($_SERVER['PHP_AUTH_USER'], "delete-user")) {
            $this->response->setStatus('200');
            $this->response->setContent($data . '--' . $_SERVER['PHP_AUTH_USER']);
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

}
