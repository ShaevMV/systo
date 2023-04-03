<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 25.04.2017
 * Time: 21:53
 */

function __autoload($className)
{
    $className = str_replace("..", "", $className);
    $className = str_replace("\\", "/", $className);
    require_once($className.".php");
}