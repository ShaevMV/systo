<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 03.05.2017
 * Time: 1:57
 */

namespace Controller;
use System\Core;
use Module\ChangeModule;
use System\DB;
class ShiftController extends Core
{
    public function change()
    {
        $Change = new ChangeModule();
        return $Change->scan();
    }



}