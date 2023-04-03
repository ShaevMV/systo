<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 30.04.2017
 * Time: 16:47
 */

namespace Module;
use System\MainModule;


class CampingModule extends MainModule
{
    protected $table='ticket_camping';
    protected $invite=[
        [
            "table"     =>  "`camping`",
            "binding"   =>  "`camping`.`id`=`ticket_camping`.`id_order` AND `camping`.`id_fest`=18 AND `camping`.`status`=4" // 13 новая база
        ],
        [
            "table"     =>  "`user`",
            "binding"   =>  "`user`.`id`=`camping`.`id_user`"
        ]
    ];
    protected $fields=[
        "`user`.`nickname`",
        "`user`.`phone`",
        "`ticket_camping`.`id_order`",
        "`user`.`email`",
        "`ticket_camping`.`fio`",
        "`ticket_camping`.`id`"
    ];
    protected $select="
        `ticket_camping`.`id`,
        `ticket_camping`.`id_order`,
        `ticket_camping`.`status`,
        `ticket_camping`.`fio`,
        `user`.`nickname`,
        `user`.`phone`,
        `user`.`email`,
        `camping`.`status` as `order_status`,
        `camping`.`date_che` as `order_changes`,
        `ticket_camping`.`changes_time`,
        `ticket_camping`.`changes`";


}
