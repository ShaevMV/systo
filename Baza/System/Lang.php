<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 01.05.2017
 * Time: 14:50
 */

namespace System;


class Lang
{
    /*перевод тегов*/
    private static $data = [
        "list" => "Списки",
        "cards" => "Карточки",
        "order" => "Электронные оргвзносы",
        "online" => "Суперонлайн",
        "camping" => "Кейпинг",
        "friendly" => "Френдли",
        "machine" => "Номера авто"
    ];
    /*статус заказа*/
    private static $statusOrder = [
        "1" => "Возникли трудности",
        "2" => "Отменен",
        "3" => "Обрабатывается",
        "4" => "Оплачен",
        "5" => "Не прошел",
        "6" => "Всё хорошо"
    ];
    /*статус прохода*/
    private static $statusChanges = [
        "0" => "Не прошел",
        "1" => "<b style='color:red'>Прошел</b>",
        "2" => "Получил карточку",
        "4" => "Не прошел"
    ];
    /*цвет статуса / цвет брослета*/
    private static $collorstatus = [
        "0" => 'blue',
        "1" => 'green',
        "2" => 'purple',
        "4" => 'red'
    ];
    /*название табов по модулю*/
    private static $tabs = [
        "ListModule" => "tabs-list",
        "CardsModule" => "tabs-cards",
        "OrderModule" => "tabs-order",
        'CampingModule' => "tabs-camping",
        "OnlineModule" => "tabs-online",
        "FriendlyModule" => "tabs-friendly",
        "MachineNumbersModule" => "tabs-machine"
    ];

    private static $delButton = [

    ];

    private static $DisabledOfStatys = [
        "0" => "",
        "1" => "disabled",
        "2" => "disabled"
    ];

    public static function getLang($key)
    {
        return self::$data[$key];
    }

    public static function getOrderStatus($key)
    {
        return self::$statusOrder[$key];
    }

    public static function getChangeStatus($key)
    {
        return self::$statusChanges[$key];
    }

    public static function getColorStatus($key)
    {
        return self::$collorstatus[$key];
    }

    public static function getDisabledOfStatys($key, $UserAcces = "a")
    {
        if ($UserAcces == "z") {
            return "";
        } else {
            if (isset(self::$DisabledOfStatys[$key])) {
                return self::$DisabledOfStatys[$key];
            } else {
                return "";
            }
        }
    }


    public static function getTabs($key)
    {
        return self::$tabs[$key];
    }


}
