<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 30.04.2017
 * Time: 16:19
 */

namespace Module;

use System\MainModule;
use System\DB;

class ListModule extends MainModule
{
    protected $table = "project_lists";
    /*SELECT
guests.id,
baza_orders.project as project,
guests.name,
baza_type_orders.name as type,
guests.changes_time,
guests.changes,
guests.changes_status
FROM `guests`
JOIN `baza_orders` ON baza_orders.id = guests.order_id
JOIN `baza_type_orders` ON baza_orders.id_type_orders = baza_type_orders.id

WHERE guests.status = 1*/
    protected $fields = ['project', 'name', 'id'];
    /*protected $filling=[
        'project' => 'string',
        'surname' => 'string',
        'name' => 'string',
        'comment'=> 'string',
        'type'=>'integer'];*/


    //protected $table='ticket';
    protected $invite = [];
    protected $select = "
        `id`,
        `project`,
        `name` as `surname`,
        `changes_time`,
        `changes`,
        `changes_status`";

    public function seatchAll($q)
    {
        $sql = "SELECT ".$this->select." FROM `".$this->table."` ";
        $sql .= $this->setInvite($this->invite);

        $sql .= " WHERE  id >= 30000 AND (";
        foreach ($this->fields as $key => $item) {
            if ($item === 'id' && preg_match('/^[e,е][0-9]+/', $q) > 0) {
                $sql .= "".$item." LIKE '%".$q."%'";
            } else {
                $sql .= "".$item." LIKE '%".$q."%'";
            }
            if ($key < count($this->fields) - 1) {
                $sql .= " OR ";
            }
        }
        $sql .= " )";
        //var_dump($sql);
        //dd($sql);
        return DB::query($sql);
    }

    public function update($id, $date)
    {
        $sql = "UPDATE `".$this->table."` SET ";
        $i = 0;
        $date['changes_status'] = $date['status'];
        unset($date['status']);
        foreach ($date as $key => $item) {
            $sql .= "`".$key."`='".$item."'";
            if ($i < count($date) - 1) {
                $sql .= ",";
            }
            $i++;
        }
        if (!is_array($id)) {
            $sql .= " WHERE `id`=".$id;
        } else {
            $sql .= " WHERE ";
            foreach ($id as $key => $item) {
                $sql .= "`id`=".$item;
                if ($key < count($id) - 1) {
                    $sql .= " OR ";
                }
            }

        }

        return DB::insert($sql);
    }
}
