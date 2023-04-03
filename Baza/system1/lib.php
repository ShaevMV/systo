<?php
	/*	Библиотека классов
	*/
  class lib
   {
        /*меню для CMS. Перенести в файл*/
		public $page_cms=array (
						 		0=>array (
						 			'url'	=> 'page',
						 			'php'	=> 'edit_page'
					 			),
						 		1=>array (
									'url'	=> 'menu',
						 			'php'	=> 'edit_menu'
									),
								2=>array (
									'url'	=> 'order',
						 			'php'	=> 'edit_order'
									),
                                3=>array (
									'url'	=> 'fest',
						 			'php'	=> 'edit_fest'
									),
								4=>array (
									'url'	=> 'status',
						 			'php'	=> 'edit_status'
									),
								5=>array (
									'url'	=> 'user',
						 			'php'	=> 'edit_user'
									),
								6=>array (
									'url'	=> '',
						 			'php'	=> 'home_page'
									),
								7=>array (
									'url'	=> 'ticket',
						 			'php'	=> 'edit_ticket'
									),
								8=>array (
									'url'	=> 'type_ticket',
						 			'php'	=> 'edit_type'
									),
								9=>array (
									'url'	=> 'bey',
						 			'php'	=> 'edit_bey'
									),
								10=>array (
									'url'	=> 'form',
									'php'	=> 'edit_form'
									),
								11=>array (
									'url'	=> 'data_form',
									'php'	=> 'data_form'
								),
								12=>array (
									'url'	=> 'bus',
									'php'	=> 'data_bus'
								),
								13=>array (
									'url'	=> 'order_bus',
									'php'	=> 'order_bus'
								),
								14=>array (
									'url'	=> 'ticket_bus',
									'php'	=> 'ticket_bus'
								),
								15=>array (
									'url'	=> 'log',
									'php'	=> 'log_page'
								),
								16=>array (
									'url'	=> 'shirt',
									'php'	=> 'edit_shirt'
								),
								17=>array (
									'url'	=> 'ticket_shirt',
									'php'	=> 'ticket_shirt'
								),

	 	);

               /*
			Красивый вывод
			<pre>
				prnt_r(html);
			<pre>
		*/
		public function view_var($data)
	    {
	    	echo "<pre>";
	    	print_r($data);
	    	echo "</pre>";
	    	die();
		}

		/* переводит URL в мссив */
		public function url($url='')
	    {
	    	if (isset($url)) $url=$_SERVER['REDIRECT_URL'];
            $url_ar = explode('/',$url);
	    	unset($url_ar[0]);
	    	return $url_ar;
		}

		/*вывод полного пути к странице*/
        public function view_url($name)
        {
            if(empty($_GET))
                $g='?';
            else
            {
                $g='?';
                //$b=false;
                foreach ($_GET as $key=>$item)
                {
                    if($key!=$name)
                    $g.=$key.'='.$item;
                    $g.='&';
                 //   $b=true;
                }

            }
            return $_SERVER['REDIRECT_URL'].$g;

        }


        /* $type=1 получение всей инфы по странице
        	иначе название запускемого  PHP файла*/
        public function info_page($url,$type=1)
	    {
	    	if (is_array($url)) $url = implode ('/',$url);
	    	$url=str_replace("/","",$url);
	    	$res=$this->query("SELECT * FROM  `page` WHERE `url`= '".$url."'");

	    	if (!$res)  return $res="404"; // переход на 404 страницу
			else if ($type==1) return $res[0]['type'];
		    else return $res[0];

		}

			/* запрос на чтение к БД */
		public function query($sql)
	    {

		   $row = mysql_query($sql);
		   $bul=false;
		   while($data=mysql_fetch_assoc($row)) // цикл вывода
			{
		   	  $bul=true;
			  $res[]=$data;
			}


           if ($bul)
           {
  			   return $res;
           }
		   //mysql_affected_rows($res);
		   return false;
		}

			/* запрос на чтение к БД с выводом index=id
			Тип вывода
			Eсли $type=1
			Вывод массив value=значение поле таблици, name=имя поля
			Если $type=2
			Вывод результата по ассоативному массиву, где ключь = имя поля
			Иначе обычный вывод mysql_fetch_array*/
		public function query_index($sql,$col,$type=1)
	    {
		   $row = mysql_query($sql);

		   $res=false;
           //var_dump($sql);
		  	if($type==1)
			{
		  		 while($data=mysql_fetch_array($row)) // цикл вывода
					{
							  $keys=array_keys($data);
							  $vals = array();
							  $j=0;
							  for ($i=0;$i<count($keys);$i++)
							  {
								  	if ($i%2!=0)
							  		{
							  			$vals[$j]['name'] = $keys[$i];
										$vals[$j]['value'] = $data[$keys[$i]];
										$vals[$j]['type'] = mysql_field_type($row, $j);
										$j++;
								  	}
							  }
							  $res[$data[$col]]=$vals;
					}
			}
			else if ($type==2)
  			 	while($data=mysql_fetch_assoc($row)) // цикл вывода
				{
				  $res[$data[$col]]=$data;
				}

		   //mysql_affected_rows($res);
		   return $res;
		}

		/* запрос на на запись в БД */
		public function insert($sql)
		{
		   $row = mysql_query($sql);

		   return $row;
		}

		/* получение название запускемого PHP файла для CMS части*/
		public function info_page_cms($url)
	    {

	         $page = $this->page_cms;
	         foreach ($page as $value)
	         {
		         if ($url==$value['url'])
		         {
		         	return $value['php'];
		         }
		     }
		     return '404';
		}
        /*отправка писма
        //$to      = 'shaevMV@gmail.com';
			//$subject = 'the subject';
			//$message = 'hello';*/
		public function mail_user($to,$subject,$message)
		{

			$headers = 'From: noreply@no-reply.ru' . "\r\n" .
		    'Reply-To: noreply@no-reply.ru' . "\r\n" .
		    'X-Mailer: PHP/' . phpversion();
			return  mail($to, $subject, $message, $headers);
		}
        /*отправка писма
        //$to      = 'shaevMV@gmail.com';
			//$subject = 'the subject';
			//$message = 'hello';*/
		public function mail_user_html($to,$subject,$message)
		{
            $headers = "Content-type: text/html; charset=iso-8859-1\r\n";
			$headers .= "From: noreply@no-reply.ru ";
			return  mail($to, $subject, $message, $headers);
		}


        /*генерация паролей */
		public function creat_pas()
		{
			// Символы, которые будут использоваться в пароле.
			$chars="qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
			// Количество символов в пароле.
			$max=10;
			// Определяем количество символов в $chars
			$size=StrLen($chars)-1;
			// Определяем пустую переменную, в которую и будем записывать символы.
			$password=null;
			// Создаём пароль.
		    while($max--)
		    $password.=$chars[rand(0,$size)];
		    return $password;
		}
		/*html вывод формативную дату*/
		public function data_format($date)
		{
			return date("m/d/Y", strtotime($date));
		}
        /*шифрование пароля MD5*/
		public function md5_mysql($pas)
		{
			       	//	$pas=md5($pas);
	       		$sql="SELECT MD5('".$pas."') as pas";

	       		$res=$this->query($sql);
	       		return $res[0]['pas'];
         }
   }
?>