<?php

namespace Module;
use System\MainModule;

class BrasletModule extends MainModule
{
    protected $table='braslet';

    protected $fields=['price','number'];

    protected $filling = array(
        'price' => 'integer',
        'number' => 'integer',
        'status' => 'integer',
        'changes_time' => 'string',
        'changes'  => 'string',
    );

}
