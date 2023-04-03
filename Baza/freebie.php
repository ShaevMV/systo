<?php
	include 'cfg.php';
	class class_freebie
	{
		public $error='';
     // показывать кто вошел в систему
		 public function viwe_enter()
		 {
		 	$time=date('H:i:s', time());
			$today = date("d.m.y");
		 	global $db;
		 	$sql = mysql_query("SELECT `login`.`login`,`change`.`start`,`change`.`commint` FROM  `change`,`login` WHERE  `change`.`id` =  '".$_SESSION['change_id']."' AND `login`.`id` =  `change`.`user`" ,$db);
		 	$data = mysql_fetch_row ($sql);
//		 	var_dump($data);
			echo '<header>
				<h3>Привет,'.$data[0].' вы вошли в систему <b>'.$data[1].'</b> и у вас на смени <b>'.$data[2].'</b>. <a href="index.php?exit='.$_SESSION['change_id'].'"> Выйти </a></h3>
			</header>';
			echo '<nav>
				<ul>
					<li><a href="/order.php">Электронная предпродажа</a></li>
					<li><a href="/live_tickets.php">Живые Билеты</a></li>
					<li><a href="/braslet.php">Браслеты</a></li>
					<li><a href="/list.php">Списки</a></li>
					<li><a href="/freebie.php">Халява</a></li>
					<li><a href="/index.php">Смены</a></li>
				</ul>

				</nav>';
			echo '<div id="picture"><img src="Solar-Systo-2020.jpg"></div>';
			echo '<h2>Браслеты</h2>';

		 	//echo 'Привет <b>'.$data[0].'</b> вы вошли в систему <b>'.$data[1].'</b> и вы находитесь в <b>'.$data[2].'</b>. <a href="/?exit='.$_SESSION['change_id'].'"> Выйти </a>' ;
		 }
         // форма фильтрации
  		 public function viwe_fillter()
  		 {
  		 global $db;
  		 $sql = mysql_query("SELECT * FROM  `types`" ,$db);

           echo ' <br/><h2>Фильтр</h2>
           	<table>
           		<form action="/freebie.php" method="POST">
           		<tr>
           			<td>
           				Поиск по id
           			</td>
           			<td>
           				<input type="text" name="id" value="">
           			</td>
           		</tr>
           		<tr>
           			<td>
           				Поиск по фио
           			</td>
           			<td>
           				<input type="text" name="fio" value="">
           			</td>
           		</tr>
           		<tr>
           			<td>
           				Поиск по телефону
           			</td>
           			<td>
           				<input type="text" name="phone" value="">
           			</td>
           		</tr>

           		<tr>
           			<td>
           				Поиск по комментарию
           			</td>
           			<td>
           				<input type="text" name="comment" value="">
           			</td>
           		</tr>
           		<tr>
           			<td>
           				Поиск по Статусу
           			</td>
           			<td>
           				<select name="status">
   							<option value="" selected=""></option>
				          <option value="1">не прошёл</option>
				          <option value="2">прошёл</option>
				        </select>
           			</td>
           		</tr>
           		<tr>
           			<td>
           				<input type="submit" value="Поиск"> </form>
           			</td>
           			<td>
           				<form action="/freebie.php" method="POST" enctype="multipart/form-data">
							<input type="hidden" name="clear" value="yes">
							<input type="submit" value="Очистить фильтр">
							Отправить этот файл: <br /><input name="userfile" type="file" /><br />
    						<input type="submit" value="загрузить" />
						</form>
						<a href="?export=true">выгрузить</a>
           			</td>
           		</tr>
           	</table>';
  		 }
     //показать данные
      public function viwe_tabel()
         {
	         global $db;
         	 echo ("<table border ='1'>");
			  //выводим строку заголовков
  				echo ('<tr>
  					<th>
		  				<a href="?grup=id">id</a>
  					</th>
  					<th>
		  				<a href="?grup=status">Статус</a>
  					</th>
  					<th>
  						Изменить статус
		  			</th>
  					<th>
  						<a href="?grup=fio">ФИО</a>
		  			</th>
  					<th>
		  				<a href="?grup=comment">Комментрий</a>
  					</th>
  					<th>
		  				<a href="?grup=phone">Телефон</a>
  					</th>
  					<th>
  						<a href="?grup=changes_time">Изменения</a>
		  			</th>
		  			<th>
  						<a href="?grup=changes">Смена</a>
		  			</th>

  				</tr>');
      		   		$sql = mysql_query("SELECT * FROM  `freebie`".$this->fillter()." ".$this->grup() ,$db);
                	//var_dump("SELECT * FROM  `freebie`".$this->fillter()." ".$this->grup());
			  	while($data = mysql_fetch_array($sql)){
    			  	if ($data['status']==1)
			  		{
			  			$color='red';
			  		}
			  		else
			  		{
			  			$color='green';
			  		}
    			  echo '<tr >';
			      echo '<td style="
    background-color: '.$color.';
">' . $data['id'] . '</td>';
 					echo '<td>';
			      				if ($data['status']==1)
				      				echo 'не прошёл';
				      			else
				      				echo 'прошёл';
			      echo '</td>';
                 echo '<td>';
			    if ($data['status']==1)
			     echo ' <form action="/freebie.php" method="POST">
					      	<input type="hidden" name="enter" value="'.$data['id'].'">
							<input type="submit" value="Пропустить">
						</form>';
				echo '</td>';
			      echo '<td>' . $data['fio'] . '</td>';
			      echo '<td>' . $data['phone'] . '</td>';
			      echo '<td>' . $data['comment'] . '</td>';
			      echo '<td>' . $data['changes_time'] . '</td>';
			      echo '<td> <a href="/?id='.$data['changes'].'">' . $data['changes'] . '</a></td>';

			      echo '</tr>';
			   	}
         }
		// фильтрация
		public function fillter()
		{
			 $key=array_keys($_REQUEST);

  			 $g=0;
  			 $where='';
			 if (isset($_REQUEST['clear']))
			 {
			 	$key=array_keys($_REQUEST);

  				for ($i=0;$i<count($key);$i++)
  				  	{
				  		unset ($_REQUEST[$key[$i]]);
				  	}
				 return  $where;
			 }
			 for ($i=0;$i<count($key);$i++)
			 {
		  		if ($key[$i]!="grup" && $key[$i]!="PHPSESSID" && $key[$i]!="exit" && $key[$i]!="login" && $key[$i]!="pplace" && $key[$i]!="password" && $key[$i]!="enter" && $key[$i]!="summa_enter")
  				{

		  			if (!empty($_REQUEST[$key[$i]]) || $_REQUEST[$key[$i]]=='0')
	  				{
  						if ($g==0)
  						{
  						 	$where.=' WHERE ';
  						}
  						else
  						{
	  						$where.=' AND ';
  						}

  						if ($key[$i]=='id')
  						{
		  					$where.='`'.$key[$i].'` ='.$_REQUEST[$key[$i]];
	  					}
	  					elseif ($key[$i]=='status')
  						{
		  					$where.='`'.$key[$i].'` ='.$_REQUEST[$key[$i]];
	  					}

	  					else
		  				{
  							$where.='`list`.`'.$key[$i]."` LIKE  '%".$_REQUEST[$key[$i]]."%'";
			  			}
	  					$g++;
	  					//`location` LIKE  '%Олег Дуров%'
			  			//WHERE  `id` =2555 AND  `location` LIKE  '%Олег%' AND  `name` LIKE  '%Ол%' AND  `nic` LIKE  '%Ду%'
  			 		}
  				}
			 }
        	return  $where;
		}
       //группировка
  		public function grup()
		 {
		 	if (!empty($_GET['grup']))
  			{
 	 			$grup= 'ORDER BY `'.$_GET['grup'].'` ASC';
			}
			  else
			{
			  	$grup= 'ORDER BY  `id` DESC  ';
			}
			return $grup;
		 }
		//  проход
		public function enter_live_tickets(){
			  global $db;
			$time=date('H:i:s', time());
			$today = date("d.m.y");
			$sql = mysql_query("SELECT * FROM  `change` WHERE  `id` =".$_SESSION['change_id'] ,$db);
			$data = mysql_fetch_row($sql);
            $freebie=$data[10];


			$sql = mysql_query("UPDATE  `freebie` SET  `status` =  '2', `changes_time` =  '".$today." ".$time."',`changes` ='".$_SESSION['change_id']."' WHERE  `id` = ". $_REQUEST['enter'],$db);


           		$freebie++;
				$sql = mysql_query("UPDATE  `change` SET  `freebie` =  '".$freebie."' WHERE  `change`.`id` =".$_SESSION['change_id'],$db);

            $key=array_keys($_REQUEST);
            $_POST['enter']='';
            header("Location: /freebie.php");
			//UPDATE  `sisto`.`change` SET  `live_tickets` =  '12' WHERE  `change`.`id` =26;
		 	//$sql = mysql_query("UPDATE  `change` SET  `status` =  'прошёл', `changes` =  '".$today." ".$time.":".$_SESSION['id']. "' WHERE  `id` = ". $_REQUEST['enter'],$db);
	 	}

		//  форма для добовление
		 public function viwe_form_add()
		 {
		 	echo '
<h2>Добавить </h2>
<table border="1">
			<form action="/freebie.php" method="POST">
  	<tr>

  					<th>
		  				ФИО
  					</th>
  					<th>
						телефон
		  			</th>
		  			<th>
						комментарий
		  			</th>
  					<th>
		  				статус
  					</th>
  					<th>
						Добавить
		  			</th>
  				</tr>
  	<tr>


		<td> <input type="text" name="fio" value=""> </td>
		<td> <input type="text" name="phone" value=""> </td>
		<td> <input type="text" name="comment" value=""> </td>
		<td> <select name="status">
				          <option value="1">не прошёл</option>
				          <option value="2" selected="">прошёл</option>
				        </select> </td>
		<td>  	<input type="hidden" name="add" value="true">
			<input type="submit" value="Добавить">         </td>
			</form>
  	</tr>
	<table>';
		 }
		 public function add_live_tickets()
		 {
           $time=date('H:i:s', time());
			$today = date("d.m.y");
		   global $db;
		 	$sql = mysql_query("INSERT INTO  `freebie` (
			`id` ,
			`fio` ,
			`phone` ,
			`comment` ,
			`status` ,
			`changes_time` ,
			`changes`
			)
			VALUES (
				NULL,'".$_POST['fio']."','".$_POST['phone']."','".$_POST['comment']."','".$_POST['status']."',  '".$today." ".$time."',  '".$_SESSION['change_id']."');",$db);

		 	if ($_POST['status']==2){

		 		$sql = mysql_query("SELECT * FROM  `change` WHERE  `id` =".$_SESSION['change_id'] ,$db);
				$data = mysql_fetch_row($sql);
            	$freebie=$data[10];

           		$freebie++;
				$sql = mysql_query("UPDATE  `change` SET  `freebie` =  '".$freebie."' WHERE  `change`.`id` =".$_SESSION['change_id'],$db);
				//var_dump("UPDATE  `change` SET  `braslet` =  '".$braslet."', `summa`='".$summa."' WHERE  `change`.`id` =".$_SESSION['change_id']);
				//die;
				}
		 	header("Location: /freebie.php");
		 }

		 //экспорт
          function export_csv(
        $table='freebie', 		// Имя таблицы для экспорта
        $afields='id,fio,phone,comment,status,changes_time,changes', 		// Массив строк - имен полей таблицы
        $filename='freebie.csv', 	 	// Имя CSV файла для сохранения информации
                    // (путь от корня web-сервера)
        $delim=';', 		// Разделитель полей в CSV файле
        $enclosed='"', 	 	// Кавычки для содержимого полей
        $escaped='\\', 	 	// Ставится перед специальными символами
        $lineend='\\r\\n')
        {  	// Чем заканчивать строку в файле CSV

		$array=explode('/',$_SERVER['SCRIPT_FILENAME']);
array_pop($array);
$p=implode('/',$array);
    	$q_export =
		    "SELECT ". $afields.
		    "   INTO OUTFILE '".$p.'/'.$filename."'
  				FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '".$enclosed."'
  				LINES TERMINATED BY '\n'
  				FROM ".$table;
        //var_dump($q_export);
        // Если файл существует, при экспорте будет выдана ошибка
        if(file_exists($p.'/'.$filename))
            unlink($p.'/'.$filename);


        return mysql_query($q_export);
    }

		 //импорт таблици
		 	public function file_uploads()
         {
             global $db;
         	 $array=explode('/',$_SERVER['SCRIPT_FILENAME']);
			 array_pop($array);
			 $p=implode('/',$array);
         	 $uploaddir = $p.'/uploads/';
			 $uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
             $pos=strripos($_FILES['userfile']['name'], 'freebie');

			if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile) && $pos!==false) {
            	$sql = mysql_query("SELECT * FROM  `freebie` " ,$db);
            	$ar=array();
            	$i=0;
            	while($data = mysql_fetch_array($sql))
            	{
            		    $ar[$i]['date']=$data['changes_time'];
            		    $ar[$i]['status']=$data['status'];
            		    $ar[$i]['id']=$data['id'];
            			$i++;
            	}

            	$file = file($uploadfile);
                $i=0;
	            foreach($file as $fe)
	            {
	            	$temp=explode(',',$fe);

	            	if (!empty($temp[0]))
	            	{


	                    if(empty($ar[$i]['id']))
	                    {
	                        $sql = mysql_query("INSERT INTO `freebie`(`id`, `fio`, `phone`, `comment`, `status`, `changes_time`, `changes`) VALUES (  ".$fe.")",$db);
	                    }
	                    $i++;
                    }
	            }

			} else {
			    $this->error="<h2 style='color: red;'>Эй мэн проверь свой файл, походу это не тот. Он должен называться &laquo;freebie&raquo;!</h2>";
			}


  		 }
        //выгрузка файла
		function file_download($filename, $mimetype='application/octet-stream') {
		  if (file_exists($filename)) {
		    header($_SERVER["SERVER_PROTOCOL"] . ' 200 OK');
		    header('Content-Type: ' . $mimetype);
		    header('Last-Modified: ' . gmdate('r', filemtime($filename)));
		    header('ETag: ' . sprintf('%x-%x-%x', fileinode($filename), filesize($filename), filemtime($filename)));
		    header('Content-Length: ' . (filesize($filename)));
		    header('Connection: close');
		    header('Content-Disposition: attachment; filename="' . basename($filename) . '";');
		// Открываем искомый файл
		    $f=fopen($filename, 'r');
		    while(!feof($f)) {
		// Читаем килобайтный блок, отдаем его в вывод и сбрасываем в буфер
		      echo fread($f, 1024);
		      flush();
		    }
		// Закрываем файл
		    fclose($f);
		  } else {
		    header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
		    header('Status: 404 Not Found');
		  }
		  header("Location: /freebie.php");
		  exit;
		}

	}

//var_dump($_SESSION);
//var_dump($_POST);
if (empty($_SESSION['change_id']))
{
//	var_dump("Location: ".$_SERVER['HTTP_HOST']);
	header("Location: /index.php");
	exit();
}
$a = new class_freebie();

if (!empty($_POST['enter']))
{
	$a->enter_live_tickets();
}
if (!empty($_POST['add']))
{
	$a->add_live_tickets();
}
if (!empty($_GET['export']))
{
	$a->export_csv();
	$array=explode('/',$_SERVER['SCRIPT_FILENAME']);
	array_pop($array);
	$p=implode('/',$array);
	$p=$p.'/freebie.csv';
	$a->file_download($p);
}

if (isset($_FILES['userfile']))
{
	$a->file_uploads();
}

$a->viwe_enter();
$a->viwe_fillter();
echo $a->error;
$a->viwe_form_add();
$a->viwe_tabel();

?>
