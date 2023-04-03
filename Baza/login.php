<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 30.04.2017
 * Time: 23:51
 */
//require_once 'System/headfun.php';

use System\View;
use Controller\LoginController;


$login=new LoginController();

if(!empty($login->router->post['login']) && !empty($login->router->post['password']))
{
    if(!$login->enter($login->router->post['login'],$login->router->post['password']))
    {
        View::view_login();
    }
}

if(!empty($_GET['exit']))
{
    $login->UserOut();
}

View::view_login();