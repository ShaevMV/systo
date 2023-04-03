<?php

namespace System;
class DB
{
    protected static $_instance;  //экземпляр объекта
    protected $count_sql = 0;  //кол-во записей

    private function __construct()
    { // конструктор отрабатывает один раз при вызове DB::getInstance();
        //echo "<br/><em>1.  Установка соединения с хостом...";
        //подключаемся к БД
        $this->connect = mysqli_connect(DBHOST, DBUSERNAME, DBPASS) or die("Невозможно установить соединение" . mysql_error());
        // выбираем таблицу
        //echo "<br/>2.  Выбор базы...";
        mysqli_select_db($this->connect, DBNAME) or die ("Невозможно выбрать указанную базу" . mysqli_error($this->connect));
        // устанавливаем кодировку таблицы
        //echo "<br/>3.  Устанавливаем кодировку базы: ";
        @mysqli_set_charset($this->connect, 'utf8');
        //echo "<br/> Конструктор успешно открыл соединение с БД! и установил кодировку.</em>";
    }

    public static function query($sql)
    {
        $obj = self::$_instance;
        $res = false;
        if (isset($obj->connect)) {
            $obj->count_sql++;
            $start_time_sql = microtime(true);
            //echo $sql;
            $result = mysqli_query($obj->connect, $sql) or die("<br/><span style='color:red'>Ошибка в SQL запросе:</span> " . mysqli_error($obj->connect));
            $time_sql = microtime(true) - $start_time_sql;
            $arRes = [];

            while ($data = mysqli_fetch_assoc($result)) // цикл вывода
            {
                $arRes[] = $data;
            }

            if (count($arRes) == 1) {
                $res = end($arRes);
            } else {
                $res = $arRes;
            }
            return $res;
        }
        return false;
    }

    public static function insert($sql)
    {
        mysqli_query(self::$_instance->connect, $sql);
        return @mysqli_insert_id(self::$_instance->connect);
    }

    public static function getInstance()
    { // получить экземпляр данного класса
        if (self::$_instance === null) { // если экземпляр данного класса  не создан
            self::$_instance = new self;  // создаем экземпляр данного класса
        }
        return self::$_instance; // возвращаем экземпляр данного класса
    }

    public static function fetch_object($object)
    {
        return @mysqli_fetch_object($object);
    }

    public static function fetch_array($object)
    {
        return @mysqli_fetch_array($object);
    }

//возвращает запись в виде объекта

    public static function insert_id()
    {
        return @mysqli_insert_id(self::$_instance->connect);
    }

//возвращает запись в виде массива

    private function __clone()
    { //запрещаем клонирование объекта модификатором private
    }

//mysql_insert_id() возвращает ID,
//сгенерированный колонкой с AUTO_INCREMENT последним запросом INSERT к серверу
}