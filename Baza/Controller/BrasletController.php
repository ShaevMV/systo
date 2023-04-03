<?php

namespace Controller;

use Module\BrasletModule;
use Module\ChangeModule;
use System\Core;
use System\View;

class BrasletController extends Core
{
    const DEFAULT_PRICE = 4500;
    const START_NUMBER = 1;
    const END_NUMBER = 1000;

    public function run()
    {
        parent::run();
        View::view_form_braslet();
    }

    public function add($data)
    {
        $braslet = new BrasletModule();

        if ($scan = $braslet->scan([
            'number' => $data['number'],
        ])) {
            View::view("<h1 style='color:red;'>Такой билет был продан сменой :{$scan['changes']} в {$scan['changes_time']}</h1>");
        } else {
            if ((int)$data['number'] >= self::START_NUMBER && (int)$data['number'] <= self::END_NUMBER) {
                $data = array_merge([
                    "status" => 1,
                    "changes" => $this->user->id,
                    "changes_time" => date("d.m.Y H:i:s"),
                ], $data);
                
                $id = $braslet->insert($data);
                if ($id > 0) {
                    View::view("<h1 style='color:blue;'>Всё хорошо!!!</h1>");
                    $Change = new ChangeModule();
                    $Change->addChange('BrasletModule', $this->user->id, 1);
                    $Change->addChange('price', $this->user->id, $data['price']);

                } else {
                    View::view("<h1 style='color:red;'>Ошибка ввода:" . implode(",", $data) . "</h1>");
                }
            } else {
                View::view("<h1 style='color:red;'>Ошибка номера билета при покупки возможно только " . self::START_NUMBER . "-" . self::END_NUMBER . "</h1>");
            }
        }


    }
}
