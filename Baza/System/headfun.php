<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 25.04.2017
 * Time: 22:29
 */

function my_autoload ($className) {
    $className = str_replace("..", "", $className);
    $className = str_replace("\\", "/", $className);
    require_once($className.".php");
}
spl_autoload_register("my_autoload");

function dd($val)
{
    echo "<pre>";
    print_r($val);
    echo "</pre>";
    die;
}
function dump($val)
{
    echo "<pre>";
    print_r($val);
    echo "</pre>";
}

if(!function_exists('array_column'))
{
    function array_column($array, $column_key, $index_key = null)
    {
        return array_reduce($array, function ($result, $item) use ($column_key, $index_key)
        {
            if (null === $index_key) {
                $result[] = $item[$column_key];
            } else {
                $result[$item[$index_key]] = $item[$column_key];
            }

            return $result;
        }, []);
    }
}
