<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 30.04.2017
 * Time: 17:08
 */

namespace Module;

use System\DB;
use System\MainModule;

class FriendlyModule extends MainModule
{
    protected $table = 'friendly_tickets';

    protected $fields = ['email', 'fio_friendly', 'seller', 'id'];

    /*поиск по всем полям*/
    public function seatchAll($q)
    {
        $sql = "SELECT ".$this->select." FROM `".$this->table."` ";
        $sql .= $this->setInvite($this->invite);

        $sql .= " WHERE id > 1000 AND ( ";
        foreach ($this->fields as $key => $item) {
            if ($item === 'id' && preg_match('/^[f][0-9]+/', $q) > 0) {
                $sql .= "".$item." LIKE '%".(int) $q."%'";
            } else {
                $sql .= "".$item." LIKE '%".$q."%'";
            }

            if ($key < count($this->fields) - 1) {
                $sql .= " OR ";
            }
        }
        $sql .= " )";
        //var_dump($sql);
        return DB::query($sql);
    }
}
