<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 02.05.2017
 * Time: 18:04
 */

namespace Controller;

use Module\CampingModule;
use Module\FriendlyModule;
use Module\MachineNumbersModule;
use System\MainModule;
use System\View as View;
use System\Core;
use Module\ListModule;
use Module\CardsModule;
use Module\OrderModule;
use Module\ChangeModule;
use Module\OnlineModule;

class SearchControler extends Core
{
    public $list;
    public $cards;
    public $order;
    public $camping;
    public $online;
    public $friendly;
    public $machine;

    private function list_quest($q)
    {
        $list = new ListModule();
        return $list->seatchAll($q);
    }

    private function cards_quest($q)
    {
        $cards = new CardsModule;
        return $cards->seatchAll((int)$q);
    }

    private function camping_quest($q)
    {
        $cards = new CampingModule();
        return $cards->seatchAll($q);
    }

    private function online_quest($q)
    {
        $cards = new OnlineModule();
        return $cards->seatchAll($q);
    }

    private function order_quest($q)
    {
        $order = new OrderModule;
        return $order->seatchAll($q);
    }

    private function order_friendly($q)
    {
        $order = new FriendlyModule();
        return $order->seatchAll($q);
    }

    private function machine_numbers($q)
    {
        $order = new MachineNumbersModule();
        return $order->seatchAll($q);
    }

    public function run()
    {
        parent::run();
        $q = '';
        if (!empty($this->router->get['q'])) {
            $q = $this->router->get['q'];
        }
        View::view_search($q);
    }

    public function quest($q)
    {
        if (preg_match('/^[e,е][0-9]+/', $q) > 0) {
            $this->list = $this->list_quest(preg_replace("/[^,.0-9]/", '', $q));
        }   elseif (preg_match('/^[f][0-9]+/', $q) > 0) {
            $this->friendly = $this->order_friendly(preg_replace("/[^,.0-9]/", '', $q));
        } else {
            // поиск по таблицы list
            $this->order = $this->order_quest($q);
            $this->list = $this->list_quest($q);
            $this->cards = $this->cards_quest($q);
            $this->camping = $this->camping_quest($q);
            $this->friendly = $this->order_friendly($q);
            $this->machine = $this->machine_numbers($q);
            //$this->online = $this->online_quest($q);
        }
 
        View::view_result_search($this);
    }

    public function ChangeStatus($id, $date)
    {
        $type = $date['type'];
        unset($date['type']);
        switch ($type) {
            case 'OrderModule':
                $obj = new OrderModule();
                break;
            case 'CardsModule':
                $obj = new CardsModule();
                break;
            case 'ListModule':
                $obj = new ListModule();
                break;
            case 'CampingModule':
                $obj = new CampingModule();
                break;
            case 'FriendlyModule':
                $obj = new FriendlyModule();
                break;
            case 'MachineNumbersModule':
                $obj = new MachineNumbersModule();
                break;
            /*case 'OnlineModule':
                $obj = new OnlineModule();
                break;*/
        }

        if ($date['status'] == 'del') {
            $res = $obj->del($id);
            if ($res) View::view("<h1 style='color:blue;'>Запись удалена!!!</h1>");

        } else {
            $this->isChangedStatus($id, $date, $obj);

            if(count($id) === 0) {
                return;
            }
            $res = $obj->update($id, $date);
        }

        $Change = new ChangeModule();
        $count = 1;
        if (is_array($id)) {
            $count = count($id);
        }

        $Change->addChange($type, $this->user->id, $count);
    }

    /**
     * @param $id
     * @param $date
     * @param MainModule $obj
     */
    private function isChangedStatus(&$ids, $date, $obj)
    {
        $faindObj = $obj->seatchId($ids);
        foreach ($ids as $key=>$id) {
            if(isset($faindObj[$id]['status']) && $faindObj[$id]['status'] === $date['status']) {
                unset($ids[$key]);
            }
        }
    }
}
