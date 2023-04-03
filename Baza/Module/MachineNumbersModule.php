<?php

namespace Module;

use System\MainModule;

class MachineNumbersModule extends MainModule
{
    protected $table="el_machine_numbers";

    protected $fields=['project_name','number'];

}
