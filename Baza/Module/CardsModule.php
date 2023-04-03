<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 30.04.2017
 * Time: 16:47
 */

namespace Module;
use System\MainModule;


class CardsModule extends MainModule
{
    protected $table="cards_new";
    protected $fields=['ticket_id'];
    protected $select='*';

    protected $filling = array(
        'ticket_id' => 'integer',
        'status' => 'integer',
        'changes_time' => 'string',
        'changes'  => 'string',
        );

}