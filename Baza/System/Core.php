<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 25.04.2017
 * Time: 20:58
 */
namespace System;
use System\User;
use System\Page;
use System\Router;

class Core
{

    private $error; // ошибки
    public $user;
    public $page;
    public $router;

    function __construct()
    {
        $this->user=new User();
        $this->page=new Page();
        $this->router=Router::getInstance();
    }

    public function wayController($url,$conntroller,$access=null)
    {
        $this->router->setWayList($url,$conntroller,$access);
    }

    /**
     * @return mixed
     */
    public function go()
    {
        $this->router->go($this->user);
    }

    public function run(){

        View::header($this->user,$this->page);
    }


    /**
     * @param mixed $error
     */
    public function setError($error)
    {
        $this->error.= $error."<br/>";
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }


}
