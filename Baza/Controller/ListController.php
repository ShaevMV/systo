<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 03.05.2017
 * Time: 1:57
 */

namespace Controller;
use System\Core;
use Module\ListModule;
use System\View;

class ListController extends Core
{
    public function run()
    {
        parent::run();
        View::view_form_list();
    }

    public function add($data)
    {
        $List = new ListModule();

        foreach ($data as $item)
        {
            $id=$List->insert($item);
            if(!$id>0)
            {
                View::view("<h1 style='color:red;'>Ошибка ввода:".implode(",", $item)."</h1>");
            }
            else
            {
                View::view("<h1 style='color:blue;'>Запись добавлена!!!</h1>");
            }
        }

    }
}