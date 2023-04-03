<?php
//require_once 'System/headfun.php';
use Controller\SearchControler;

$search = new SearchControler();


$search->run();

if (!empty($search->router->post['type'])) {
    $id = $search->router->post['id'];
    $date = $search->router->post;
    unset($date['id']);
    $search->ChangeStatus($id, $date);
}

if (!empty($search->router->get['q'])) {
    $search->quest($search->router->get['q']);
}
?>
