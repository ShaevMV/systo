<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 25.04.2017
 * Time: 23:54
 */
namespace System;
use Module\ChangeModule;

class User
{
    public $name;
    public $access;
    public $date;
    public $comment;
    public $id;

    public function __construct()
    {
        DB::getInstance();
        if (!empty($_SESSION['change_id'])) {
            $data = DB::query("SELECT `login`.`login`,`change`.`start`,`change`.`commint` FROM  `change`,`login` WHERE  `change`.`id` =  '" . $_SESSION['change_id'] . "' AND `login`.`id` =  `change`.`user`");
            
			$this->name = $data['login'];
            $this->date = $data['start'];
            $this->commint = $data['commint'];
            $this->id = $_SESSION['change_id'];
            if(stripos($this->name,'ШТАБ')!==false || $this->name=='ПАТРУЛЬ' || $this->name=='ИНФО')
            {
                $this->access="z";
            }
            else
                $this->access="x";
        }
        else
        {
                return false;
        }
    }

    public function login($login,$password)
    {
        DB::getInstance();
        $user = DB::query("SELECT * FROM  `login` WHERE  `login` =  '" . $login . "' AND  `password` =  '" . $password . "' ");
		if(isset($user[0])){
			$user = end($user);
		}
        if (!$user) {
            dump('<h1> Что то не то ты ввел, давай дружок, ещё разок!!! </h1>');
            return false;
        } else {
			$_SESSION['id'] = $user['id']; //id пользователя
            $data = DB::query("SELECT * FROM  `change` WHERE  `user` =  '" . $user['id'] . "' AND  `final` IS NULL"); // поиск не закрытых смен
            if (!$data) {
                $change= new ChangeModule();
                $date['user']=$_SESSION['id'];
                $date['start']=date("d.m.Y H:m:i",time());
                $date['commint']=$_POST['commint'];
                $_SESSION['change_id']=$change->insert($date);
            } else {
                $_SESSION['change_id'] = $data['id'];
            }
            return $_SESSION['change_id'];
        }

        return false;
    }

    public function logout()
    {
        DB::getInstance();
        $change = new ChangeModule();
        $date['final']=date("d.m.Y H:i:s");
        $change->update($this->id,$date);
        unset($_SESSION['change_id']);
        unset($_SESSION['id']);
    }

}
