<?php
use Controller\ListController;
use System\View;

$list = new ListController();
$list->run();
if(!empty($_POST))
{
    $dateTemp=$_POST;
    $date=array();

    for($i=0;$i<count($dateTemp['project'])-1;$i++)
    {
        $date[$i]['project']=$dateTemp['project'][$i];
        $date[$i]['surname']=$dateTemp['surname'][$i];
        $date[$i]['name']=$dateTemp['name'][$i];
        $date[$i]['comment']=$dateTemp['comment'][$i];
        $date[$i]['type']=$dateTemp['type'][$i];
    }

    $list->add($date);
}

