<?php
namespace App;

use App\Response;
use App\User;
use App\Auth;
use Dotenv\Dotenv;

class HomeController
{
    private $username;
    public $response;

    public function __construct()
    {
        $dotenv = Dotenv::create(dirname(__DIR__));
        $dotenv->load();
        $this->response = new Response();
        $this->user = new User;
        $this->auth = new Auth;
        $this->username = $this->auth->getUsernameFromToken();

    }

    //echo $_ENV['APP_NAME'];

    public function info($data)
    {
        if ($this->user->hasThePerm($this->username, "read-file")) {
            $this->response->setStatus('200');
            $this->response->setUserCred($this->username);
            $this->response->setContent("OK") ;
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }

    }

    public function uploada()
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
            $this->response->setContent("OK") ;
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
            $this->response->setContent("OK") ;
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
            $this->response->setContent("OK") ;
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
            $this->response->setContent("OK") ;
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
            $this->response->setContent("OK") ;
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function addUser()
    {
        if ($this->user->hasThePerm($this->username, "create-user")) {
            $this->response->setStatus('200');
            $this->response->setContent("OK") ;
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

    public function userInfo()
    {
        if ($this->user->hasThePerm($this->username, "read-user")) {
            $this->response->setStatus('200');
            $this->response->setContent("OK") ;
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
            $this->response->setContent("OK") ;
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
            $this->response->setContent("OK") ;
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
            $this->response->setContent("OK") ;
            $this->response->finish();
        } else {
            $this->response->setStatus('401');
            $this->response->setContent("no authorization");
            $this->response->finish();

        }
    }

}
