<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 02.05.2017
 * Time: 19:47
 */


/**
 * 1 - 3000 живые билеты
 */

namespace Controller;

use System\Core;
use Module\CardsModule;
use Module\ChangeModule;
use System\View;
use System\DB;

class LiveController extends Core
{
    const START_NUMBER = 1;
    const END_NUMBER = 1000;

    public function run()
    {
        parent::run();
    }

    /*функция пропуска*/
    public function skip($ticket_id)
    {
        $cards = new CardsModule();
        
        if ((int)$ticket_id <= self::START_NUMBER && (int)$ticket_id >= self::END_NUMBER) {
            $this->setError('<b style="color: red">Живые билеты только от 0001 до 1000</b>');
            return false;
        }

        $id = $this->find($cards, $ticket_id);
        if ($id === true) {
            $id_new = $cards->insert([
                "ticket_id" => $ticket_id,
                "status" => 1,
                "changes" => $this->user->id,
                "changes_time" => date("d.m.Y H:i:s"),
            ]);
            if ($id_new > 0) {
                return true;
            } else {
                $this->setError('<b style="color: red">Что то пошло не так!!!</b>');
                return false;
            }

            //
        } elseif ($id > 0) {
            $cards->update($id, [
                'status' => 1,
                "changes" => $this->user->id,
                "changes_time" => date("d.m.Y H:i:s"),
                ]);
            return true;
            //$this->setError('<b>Добро пожаловать на фестиваль, статус билета изминён</b>');
        } else {
            return false;
        }
    }

    private function find($cards, $ticket_id)
    {

        $res = $cards->scan(['ticket_id' => (int)$ticket_id]);
        //var_dump($res);
        if (empty($res)) {
            return true;
        }

        if (isset($res[0])) {
            $this->setError('<b style="color: red">Больше одного варианта</b>');
            return false;
        } elseif ($res['changes'] > 0) {
            $this->setError('<b style="color: red">Билет уже прошел</b>');
            return false;
        } elseif (empty($res)) {
            return true;
        } else {
            return $res['id'];
        }
    }

    public function insertChange()
    {
        $change = new ChangeModule();
        $change->addChange('CardsModule', $this->user->id);
    }

    public function insert($in, $out)
    {
        DB::insert("TRUNCATE TABLE `cards_new`;");
        $Change = new CardsModule();
        for ($i = $in; $i <= $out; $i++) {
            $Change->insert([
                "ticket_id" => $i,
                "status" => 0
            ]);

        }
    }
    // public
}
