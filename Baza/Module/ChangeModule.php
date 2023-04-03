<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 01.05.2017
 * Time: 0:54
 */
namespace Module;

use System\MainModule;
use System\DB;

class ChangeModule extends MainModule
{
    protected $table = "change";
    protected $select="`change`.* ,`login`.`login` as `login`";

    protected $filling = array(
        'user' => 'integer',
        'start' => 'string',
        'final' => 'string',
        'ListModule' => 'integer',
        'CardsModule' => 'integer',
        'OrderModule' => 'integer',
        'FriendlyModule' => 'integer', 
        'MachineNumbersModule' => 'integer',
        'commint' => 'string'
    );

    protected $invite = [
        "table" => "`login`",
        "binding" => "`login`.`id`=`change`.`user`"
    ];

    public function addChange($type,$id,$count=1)
    {
        $sql="UPDATE `".$this->table."` SET `".$type."` = `".$type."` + ".$count;
        $sql.=" WHERE `id`=".$id;
        DB::insert($sql);
    }
}
