<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 25.04.2017
 * Time: 23:24
 */

namespace System;

use System\Menu;

class Page
{
    public $url; // url страницы
    public $title; // Название страницы
    public $menu; // меню
    public $access; // доступ
    public $pageController; // запускаемый контроллер

    /**
     * Page constructor.
     */
    function __construct()
    {
        $this->setUrl($_SERVER['REQUEST_URI']);
        $this->setParams(Menu::getMenu());
    }

    /**
     * @param mixed $title
     */
    private function setParams($Menu)
    {
        $id=array_search($this->url,array_column($Menu, 'url'));
        $this->title=$Menu[$id]['title'];
        $this->menu=$Menu;
        $this->access=$Menu[$id]['protected'];
        //$this->pageController=

    }

    /**
     * @param mixed $url
     */
    private function setUrl($url)
    {
        $this->url = substr($url, 1);
    }

    /**
     * @param mixed $menu
     */
    private function setMenu($menu)
    {
        $this->menu = $menu;
    }
}