<?php

use Controller\BrasletController;

$braslet = new BrasletController();
$braslet->run();
if(!empty($_POST)){
    $braslet->add($_POST);
}