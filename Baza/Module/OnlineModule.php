<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 30.04.2017
 * Time: 17:08
 */

namespace Module;
use System\MainModule;
use System\DB;
class OnlineModule extends MainModule
{
    protected $table='online_guests';
    protected $invite=[
            [
                "table"     =>  "`online_order`",
                "binding"   =>  "`online_order`.`id`=`online_guests`.`id_order`" // 13 новая база
                ],

        ];
    protected $fields=[

        "`online_guests`.`id_order`",
        "`online_order`.`email`",
        "`online_guests`.`name`",
        "`online_guests`.`id`"
    ];
    protected $select="
        `online_guests`.`id`,
        `online_guests`.`status`,
        `online_guests`.`name`,
        
        `online_order`.`status` as `order_status`,
        `online_order`.`created_at` as `order_changes`,
        `online_guests`.`changes_time`,
        `online_guests`.`changes`";

    public function seatchAll($q)
    {
        $sql="SELECT ".$this->select." FROM `".$this->table."` ";
        $sql.=$this->setInvite($this->invite);

        $sql.=" WHERE `online_order`.`status` = 1 AND (";
        foreach ($this->fields as $key=>$item)
        {
            $sql.="".$item." LIKE '%".$q."%'";
            if($key<count($this->fields)-1)
            {
                $sql.=" OR ";
            }
        }
        $sql.=" )";
        //var_dump(DB::query($sql));
        return DB::query($sql);
    }
}