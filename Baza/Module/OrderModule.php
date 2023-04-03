<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 30.04.2017
 * Time: 17:08
 */

namespace Module;

use System\MainModule;

class OrderModule extends MainModule
{
    protected $table = 'ticket';
    protected $invite = [
        [
            "table" => "`order`",
            "binding" => "`order`.`id`=`ticket`.`id_order` AND `order`.`id_fest`=20" // 13 новая база
        ],
        [
            "table" => "`user`",
            "binding" => "`user`.`id`=`order`.`id_user`"
        ]
    ];
    protected $fields = [
        "`user`.`nickname`",
        "`user`.`phone`",
        "`ticket`.`id_order`",
        "`user`.`email`",
        "`ticket`.`fio`",
        "`ticket`.`id`"
    ];
    protected $select = "
        `ticket`.`id`,
        `ticket`.`id_order`,
        `ticket`.`status`,
        `ticket`.`fio`,
        `user`.`nickname`,
        `user`.`phone`,
        `user`.`email`,
        `order`.`status` as `order_status`,
        `order`.`date_che` as `order_changes`,
        `ticket`.`changes_time`,
        `ticket`.`changes`";
}
