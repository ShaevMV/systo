<?php
/*
	модуль для изминение данных в БД
*/

class change_data extends lib
{
	/*чтение записей из таблицы/таблиц $table обёрнутых `` */
	public function read_table($table,$sql_in='',$col='*')
    {
      if (strlen($sql_in)>0) $sql_in=" WHERE ".$sql_in;
      $sql="SELECT * FROM ".$table." ".$sql_in;
      return $this->query($sql);
    }

    /*узнать кол-во записей в таблице*/
    public function count_rows($table,$sql_in='')
    {
      if (!strripos($table,'`')) $table="`".$table."`";
      if (strlen($sql_in)>0) $sql_in=" WHERE ".$sql_in;
      $sql="SELECT COUNT(1) FROM ".$table." ".$sql_in;

      $res=$this->query($sql);

      return $res[0]['COUNT(1)'];
    }

	/*чтение записей из таблицы/таблиц $table c индексом по полю $ind*/
	public function read_table_ind($table,$type=1,$ind='id',$sql_in='',$col='*',$start=0,$final=50,$order="`id` ASC")
	{

      if (!strripos($table,'`')) $table="`".$table."`";
      if (strlen($sql_in)>0) $sql_in=" WHERE ".$sql_in;
      $sql="SELECT ".$col." FROM ".$table." ".$sql_in." ORDER BY ".$order." LIMIT ".$start." , ".$final;
        //echo $sql;
      return $this->query_index($sql,$ind,$type);
    }

    /*чтение записей из таблицы $table c индексом по полю $ind*/
	public function read_table_view($table,$type=1,$ind='id',$sql_in='')
    {
      $sql="SHOW COLUMNS FROM `".$table."`";//получение списка сталбцов
      $res[0]=$this->query($sql);

      $table_add="`".$table."`";// таблицы

      $col='';
      foreach($res[0] as $val) // чтение списка полей
      {

      	if ($val['Key']=='MUL')// проверка на вторичный ключь
      	{
      		$table_add.=", `".substr($val['Field'], 3)."`";//
      		$table_sql=substr($val['Field'], 3);
      		if (substr($table_sql, -1)>0) // проверка на исключение
				{
					$table_sql=substr($table_sql, 0, strlen($table_sql)-1);
				}
      		if (strlen($col)>0)
      		    $col.=',`'.$table_sql.'`.`name` as `'.$table_sql.'`';
      		else
      			$col.='`'.$table_sql.'`.`name` as `'.$table_sql.'`';
      		if (strlen($sql_in)>0) $sql_in.=" AND ";
			$sql_in.="`".$table."`.`".$val['Field']."`=`".$table_sql."`.`id`";
      	}
      	else
      	{
           if (strlen($col)>0)
      			$col.=',`'.$table.'`.`'.$val['Field'].'`';
      		else
      		    $col.='`'.$table.'`.`'.$val['Field'].'`';
      	}

      }

      if (strlen($sql_in)>0) $sql_in=" WHERE ".$sql_in;
      if (strlen($col)==0) $col="*";
      $sql="SELECT ".$col." FROM ".$table_add." ".$sql_in;

      $res=$this->query_index($sql,$ind,$type);

      return $res;
    }

	/*чтение сталбцов таблици*/
	public function read_table_col($table)
    {

      if (is_array($table)) $table=implode(",", $table);
      $sql="SHOW COLUMNS FROM ".$table;

      $res[0]=$this->query($sql);
      foreach($res[0] as &$val)
      {
      	$val['name']=$val['Field'];
		$val['type']=$val['Type'];
		$val['value']='';
      }
      return $res;
    }



	/*чтение записи из таблице по его id*/
    public function read_table_item($table,$id)
    {
      $sql="SELECT * FROM `".$table."` WHERE `id`=".(int) $id;
      return $this->query($sql);
    }

	/* Запись массива занчений $data в таблице $table, где имя колонки = имени ключа в массиве $data*/
	public function write_table($table,$data)
    {
		$column=array_keys($data);
        $sql="INSERT INTO `".$table."` (`id` ,";
        $val="VALUES (NULL ,";
		$i=0;
        foreach($column as $col)
        {
        	$sql.="`".mysql_real_escape_string($col)."`";
        	$val.="'".mysql_real_escape_string($data[$col])."'";
        	if ($i<count($column)-1)
        	{
        		$sql.=',';
        		$val.=',';
        	}

        	$i++;
        }
        $sql=$sql.')'.$val.');';
        $this->insert($sql);
        $mes_log=date("d.m.Y H:i:s", time())." ".$_SESSION['nickname']."- ".$sql."\r\n";

		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/cms/log.txt',$mes_log, FILE_APPEND);
        $sql="SELECT MAX(`id`) as `id` FROM `".$table."`";
        $res=$this->query($sql);
      	return $res[0]['id'];

    }

    /* Изминение значений в нутри таблице $table занчениями из массива $data, где имя колонки = имени ключа в массиве $data*/
	public function write_table_id($table,$data,$id)
    {
		$column=array_keys($data);
        $sql="UPDATE  `".$table."` SET ";

		$i=0;
        foreach($column as $col)
        {
        	$sql.=" `".mysql_real_escape_string($col)."`= "." '".mysql_real_escape_string($data[$col])."' ";
        	if ($i<count($column)-1)
        	{
        		$sql.=',';
        	}
        	$i++;
        }
        $sql=$sql.' WHERE `id`= '.$id.';';
        // пишем лог файла
        if (!empty($_SESSION['nickname']))
        	$mes_log="<b>".date("d.m.Y H:i:s", time())." ".$_SESSION['nickname']." ip:".$_SERVER["REMOTE_ADDR"]."</b> - ".$sql."\r\n <br/>";
		else
            $mes_log="<b>".date("d.m.Y H:i:s", time())."  ip:".$_SERVER["REMOTE_ADDR"]."</b> - ".$sql."\r\n <br/>";
		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/cms/log.txt',$mes_log, FILE_APPEND);
      	return $this->insert($sql);
    }

    /*
    	Удаляет запись $id из таблице $table
    */
    public function delete_table_id($table,$id)
    {
        $sql="DELETE FROM `".$table."` WHERE `id`=".(int)$id;
        //echo $sql;
      	return $this->insert($sql);
    }

	/*
		Получение имён полей таблици
		type- тип поля
		count - мак кол-во символов в поле
		value - значние по умолчанию
		key - ключь поля (PRI-первичный/MUL-вторичный
		Null - обизательное ли поле
	*/
	public function col_table($table)
    {
        $sql="SHOW COLUMNS FROM `".$table."` ";
        $cols=$this->query($sql);
        $item= array();// поля таблицы, значение по умолчанию
		$i=0;
    	foreach($cols as $col) // заполнение переменной $item
    	{
     		$item[$i]['name']=$col['Field'];
			$type = $col['Type'];
			$pos=strpos($type, '(');
			if ($pos === false)
			{
				$item[$i]['type']=$col['Type'];
				$item[$i]['count']=substr($type, $pos+1,-1);
			}
            else
            {
        		$item[$i]['count']=substr($type, $pos+1,-1);
				$item[$i]['type']=substr($type,0,$pos);
			}

			$item[$i]['value']=$col['Default'];
			if ($col['Key']=='MUL')
			{
				$table=substr($col['Field'], 3);//почучение имя таблтцы
				if (substr($table, -1)>0) // проверка на исключение
				{
					$table=substr($table, 0, strlen($table)-1);
				}
				$item[$i]['select']=$this->read_table_ind($table);
			}
			$item[$i]['key']=$col['Key'];
			$i++;
    	}
      	return $item;
    }


}

?>