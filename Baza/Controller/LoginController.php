<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 02.05.2017
 * Time: 18:15
 */

namespace Controller;

use System\Core;


class LoginController extends Core
{
    public $url;

    public function enter($login,$password)
    {
        $res=$this->user->login($login,$password);

        if(!$res){
            return false;
        }
        if(!empty($this->router->get['url']))
        {
            header("Location: /".$this->router->get['url']);
        }
        else
            header("Location: /search");

    }

    public function UserOut(){

        $this->user->logout();
    }
}