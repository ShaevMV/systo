<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 02.05.2017
 * Time: 15:17
 */

namespace System;


class Router
{
    protected static $_instance;  //экземпляр объекта
    public $get;
    public $post;
    public $request;
    public $server;
    public $session;

    private $url;

    private $WayList;


    private function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->request = $_REQUEST;
        $this->server = $_SERVER;
        $this->session = $_SESSION;
        $this->url = substr(parse_url($_SERVER['REQUEST_URI'])['path'], 1);

    }


    private function __clone()
    { //запрещаем клонирование объекта модификатором private
    }

    public static function getInstance()
    { // получить экземпляр данного класса
        if (self::$_instance === null) { // если экземпляр данного класса  не создан
            self::$_instance = new self;  // создаем экземпляр данного класса
        }
        return self::$_instance; // возвращаем экземпляр данного класса
    }


    /**
     * записать пути Url
     * @param $url -- URL странницы
     * @param $controller -- запускающийся контроллер
     */
    public function setWayList($url, $controller, $access)
    {
        $this->WayList[] = [
            'url' => $url,
            'controller' => $controller,
            'access' => $access];
    }

    // пойти по нужноиму пути или полувходные данные
    public function go($user)
    {
        $WayList = $this->getWay();

        if (!$WayList) {
            dd('Такой страницы нет');
        }
        if (!empty($WayList['access']) && $WayList['access'] > $user->access) {
            header("Location: /login?url=" . $this->url);
        } else {
            include($WayList['controller']);
        }
    }

    // показать нужный путь
    private function getWay()
    {
        foreach ($this->WayList as $item) {
            if ($item['url'] == $this->url) {
                return $item;
            }
        }
        return false;
    }

}