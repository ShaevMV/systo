<?php
ini_set("display_errors", 1);
ini_set('error_reporting', E_ALL);

include 'cfg.php'; // конфигурации
require_once 'System/headfun.php'; // корневые функции
use System\Core; // ядровой класс

use System\Router;

class Index extends Core{
    // функция старта
    public function run()
    {
        dd($this->page->url);
    }
}

$index=new Index();
//$index->run();
$index->wayController('','search.php','a');
$index->wayController('search','search.php','a');
$index->wayController('login','login.php','');
$index->wayController('adminer.php','adminer.php','');
$index->wayController('change','change.php','z');
$index->wayController('live','live.php','a');
$index->wayController('braslet','braslet.php','a');
$index->wayController('change','change.php','z');
$index->wayController('insert','insert.php','z');
$index->wayController('list','list.php','z');
$index->go();

