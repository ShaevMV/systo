<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 30.04.2017
 * Time: 16:30
 */

namespace System;
use System\DB;

class MainModule
{
    protected $table; // навзвание таблицы
    protected $fields; // поля вывода / поиска
    protected $invite; // связь
    protected $filling; // поля для заполнение
    protected $select='*';


    function __construct()
    {
        DB::getInstance();
    }

    public function seatchId($ids)
    {
        $sql="SELECT * FROM `".$this->table."` ";
        $sql.=" WHERE id IN (".implode(',',$ids).")";
        $result=[];
        $resultDb = DB::query($sql);

        if(isset($resultDb['id'])) {
            $result[$resultDb['id']] = $resultDb;
        } else {
            foreach ($resultDb as $value) {
                $result[$value['id']] = $value;
            }
        }

        return $result;
    }

    /*поиск по всем полям*/
    public function seatchAll($q)
    {
        $sql="SELECT ".$this->select." FROM `".$this->table."` ";
        $sql.=$this->setInvite($this->invite);

        $sql.=" WHERE (";
        foreach ($this->fields as $key=>$item)
        {
            $sql.="".$item." LIKE '%".$q."%'";
            if($key<count($this->fields)-1)
            {
                $sql.=" OR ";
            }
        }
        $sql.=" )";
        return DB::query($sql);
    }
    /*запись в модель*/
    public function insert($data)
    {

        if (is_array($this->filling) && is_array($data))
        {
            $value=' VALUES (NULL ,';
            $field=" (`id` ,";
            $sql='INSERT INTO  `'.$this->table.'`';
			$i=0;
			$j=0;
            foreach ($this->filling as $key=>$item)
            {

                $field.="`".$key."` ";

                if(isset($data[$key]) && !empty($data[$key]))
                {
                    if($item=='string')
                    {
                        $value.="'".trim($data[$key])."' ";
                    }
                    elseif($item=='integer')
                    {
                        $value.=" ".trim($data[$key])." ";
                    }
                }
                else
                {
                    if($item=='string')
                    {
                        $value.=" NULL ";
                    }
                    elseif($item=='integer')
                    {
                        $value.=" 0 ";
                    }
                }
                if($i<count($this->filling)-1)
                {
                    $field.=",";
                    $value.=",";
                }
                $i++;
            }
            $value.=')';
            $field.=')';

            return DB::insert($sql.$field.$value);
        }
    }

    public function update($id,$date)
    {
        $sql="UPDATE `".$this->table."` SET ";
        $i=0;
        foreach ($date as $key=>$item)
        {
            $sql.="`".$key."`='".$item."'";
            if($i<count($date)-1)
            {
                $sql.=",";
            }
            $i++;
        }
        if(!is_array($id))
        {
            $sql.=" WHERE `id`=".$id;
        }
        else
        {
            $sql.=" WHERE ";
            foreach ($id as $key=>$item)
            {
                $sql.="`id`=". $item;
                if($key<count($id)-1)
                {
                    $sql.=" OR ";
                }
            }

        }

        return DB::insert($sql);
    }

    public function scan($data=null)
    {
        $sql="SELECT ".$this->select." FROM`".$this->table."`";
        echo $sql;
        $sql.=$this->setInvite($this->invite);

        if(is_array($data))
        {
            $sql.=" WHERE ";
            foreach ($data as $key=>$item)
            {
                $sql.="`".$key."`='".$item."'";
            }
        }

        return DB::query($sql);
    }

    protected function setInvite($invite)
    {
        $sql="";
        if(!empty($invite))
        {
            if(isset($invite[0]))
            {
                foreach ($this->invite as $table)
                {
                    $sql.=" JOIN ".$table['table'];
                    $sql.=" ON ".$table['binding'];
                }
            }
            else
            {
                $sql.=" JOIN ".$invite['table'];
                $sql.=" ON ".$invite['binding'];
            }
        }
        return $sql;
    }

    public function del($id)
    {
        if(count($id)>1)
        {
            $where=implode(" OR `id`=",$id);
            $sql="DELETE FROM `".$this->table."` WHERE `id`=".$where;
        }
        else
        {
            $sql="DELETE FROM `".$this->table."` WHERE `id`=".$id[0];
        }
        //dd($sql);
        DB::insert($sql);
        return true;
    }

}
