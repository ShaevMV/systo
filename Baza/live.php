<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 02.05.2017
 * Time: 11:33
 */
use Controller\LiveController;
use System\View;

$Live=new LiveController();
$Live->run();
$find="";
if(!empty($Live->router->post['ticket_id']))
{
    $find=$Live->router->post['ticket_id'];
    if($Live->skip($Live->router->post['ticket_id']))
    {
        $Live->insertChange();
        View::view("<h1>Билет продан, хорошего фестиваля ".$Live->router->post['ticket_id']."</h1>");
    }
    else
    {
        View::view("<h1 style='color: red;'
>".$Live->getError()."</h1>");
    }
}
View::form_bey($find);

