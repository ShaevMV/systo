<?php
use Controller\ShiftController;
use System\View;

$change = new ShiftController();
$change->run();
$archange=$change->change();

View::view_table_change($archange);

