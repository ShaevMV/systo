<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 25.04.2017
 * Time: 22:06
 * */
namespace System;


class Menu
{
    private static $menu=[
        [
            "title"         =>  "Поиск",
            "url"           =>  "search",
            "protected"     =>  "a"
        ],
        [
            "title"         =>  "Карточки",
            "url"           =>  "live",
            "protected"     =>  "a"
        ],
        [
            "title"         =>  "Продажа билетов на входе",
            "url"           =>  "braslet",
            "protected"     =>  "a"
        ],
        [
            "title"         =>  "Смены",
            "url"           =>  "change",
            "protected"     =>  "z"
        ],
        /*[
            "title"         =>  "Добавить в список",
            "url"           =>  "list",
            "protected"     =>  "z"
        ]*/
    ];

    /**
     * @return array
     */
    public static function getMenu()
    {
        $arMenu=self::$menu;
        return $arMenu;
    }




}