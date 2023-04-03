<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 25.04.2017
 * Time: 21:02
 */
namespace System;

class View
{

    /**
     * вывод head
     */
    private static function head()
    {

        self::view('<head>  
            <meta charset="utf-8" />
            <title>Systo Baza</title>
            <link type="text/css" rel="stylesheet" href="/style.css" />
            <link type="text/css" rel="stylesheet" href="/ui.css">
            <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
            <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
            <script>
                $( function() {
                    $( "#tabs" ).tabs();
                } );                
            </script>
		</head>');
    }

    /**
     * вывод footer
     */
    private static function footer()
    {
        self::view('<script type="text/javascript" src="tabs.js"></script>');
    }

    /**
     * вывод данных на экран
     * @param $str
     */
    public static function view($str)
    {
        echo $str;
    }

    /**
     * показывать кто вошел в систему
     */
    private static function viwe_enter($user)
    {
        $time = date('H:i:s', time());
        $today = date("d.m.y");
        $head = '<header>
                    <h3>Привет,' . $user->name . ' вы вошли в систему <b>' . $user->date . '</b> и у вас на смене <b>' . $user->commint . '</b>. <a href="/login?exit=1"> Выйти </a></h3>
                </header>';
        return $head;


        //echo 'Привет <b>'.$data[0].'</b> вы вошли в систему <b>'.$data[1].'</b> и вы находитесь в <b>'.$data[2].'</b>. <a href="/?exit='.$_SESSION['change_id'].'"> Выйти </a>' ;
    }

    /**
     * показать меню
     */
    private static function view_menu($page, $user)
    {
        $menu = $page->menu;
        $ViewMenu = '<nav><ul>';
        foreach ($menu as $item) {

            if ($user->access < $item['protected']) continue;
            $class = '';
            if ($item['url'] == $page->url)
                $class = 'active';

            $ViewMenu .= '<li class="' . $class . '"><a href="' . $item['url'] . '">' . $item['title'] .'</a></li>';
        }
        $ViewMenu .= '</ul></nav>';
        return $ViewMenu;
    }

    /**
     * показать ресунок и заголовок
     */
    private static function view_picture($page)
    {
        $picture = '<div id="picture"><img src="/Solar-Systo-2020.jpg"></div>';
        $picture .= '<h2>' . $page->title . '</h2>';
        return $picture;
    }


    /**
     *  показать заголовок
     */
    public static function header($user, $page)
    {
        self::head();
        self::view(self::viwe_enter($user)); // вощедщие
        self::view(self::view_menu($page, $user));
        self::view(self::view_picture($page));

    }

    /**
     * показать строку поиска
     */
    public static function view_search($q)
    {
        $str = '<form><input type="search" name="q" placeholder="Поиск" value="' . $q . '"><input type="submit" value="Поиск"></form>';
        self::view($str);
        return true;
    }

    /*фома входа в систему*/
    public static function view_login()
    {
        self::head();
        $str = '<div id="logster"><h2>Вход</h2><form name="" action="/login" method="post">
    			<p><span>Логин</span>
		    		<input name="login" type="text" value="">
    			</p>
		    	<p><span>Пароль</span>
    				<input name="password" type="password" value="">
		    	</p>
    			<!--<p> Кто на смене
		    		<textarea name="commint"></textarea>
				</p>-->
					<input type="submit" value="Войти">
				</form></div>';
        self::view($str);
    }

    /*вывод вкладок по результатом поиска */
    public static function view_result_search($obj)
    {
        $strHead = '<div id="tabs"> <ul>';
        $strCon = '';
        $onclick = "$(':input[type=checkbox]').attr('checked', false)";
        foreach ($obj as $key => $items) {
            if (is_array($items) && !empty($items)) {
                $strHead .= '<li onclick="' . $onclick . '"><a href="#tabs-' . $key . '">' . Lang::getLang($key) . '</a></li>';
                $strCon .= '<div id="tabs-' . $key . '">';
                $fun = 'view_table_' . $key;

                $strCon .= self::$fun($items, $obj->user);
                $strCon .= '</div>';
                //dd($strCon);
            }
        }
        $strHead .= "</ul>";
        $strCon .= '</div>';
        self::view($strHead . $strCon);
        //self::footer();
    }

    public static function view_table_camping($data, $user)
    {
        $str = self::form_head('CampingModule', $user);

        $str .= '<table border="1">                
                <tr>
                    <th>
                        Отметить
                    </th>
  					<th>
		  				id
  					</th>
  					<th>
		  				id_заказа
  					</th>
  					<th>
  						Статус
		  			</th>  					
  					<th>
		  				Имя
  					</th>
  					<th>
		  				Фамилия/Ник
  					</th>
                    <th>
  						Телефон
		  			</th>
		  			<th>
  						Оплата
		  			</th>
		  			<th>
  						Дата оплаты
		  			</th>
  					<th>
  						Изменения
		  			</th>
		  			<th>
  						Смена
		  			</th>
		  			
  				</tr>';

        if (isset($data[0])) {

            foreach ($data as $items) {

                $str .= "<tr>
                         <td style='background: purple;'>
                            <input type='checkbox' name='id[]' value='" . $items['id'] . "' ". Lang::getDisabledOfStatys($items['status'],$user->access) .">
                        </td>
                        <td>" . $items['id'] . "</td>
                        <td style='color: blue;'>" . $items['id_order'] . "</td>
                        <td>" . Lang::getChangeStatus($items['status']) . "</td>                        
                        <td>" . $items['fio'] . "</td>
                        <td>" . $items['nickname'] . "</td>                
                        <td>" . $items['phone'] . "</td>                
                        <td>" . Lang::getOrderStatus($items['order_status']) . "</td>
                        <td>" . $items['order_changes'] . "</td>
                        <td>" . $items['changes_time'] . "</td>
                        <td>" . $items['changes'] . "</td>                                                        
                               
                    </tr>";

            }
        } elseif(!empty($data)) {

            //$statusCh = $data['status_changes'];
            $str .= "<tr>  
                        <td style='background: purple;'>
                            <input type='checkbox' name='id[]' value='" . $data['id'] . "' ". Lang::getDisabledOfStatys($data['status'],$user->access) .">
                        </td>
                        <td>" . $data['id'] . "</td>          
                        <td style='color: blue;'>" . $data['id_order'] . "</td>
                        <td>" . Lang::getChangeStatus($data['status']) . "</td>
                        
                        
                        <td>" . $data['fio'] . "</td>
                        <td>" . $data['nickname'] . "</td>                  
                        <td>" . $data['phone'] . "</td>                
                        <td>" . Lang::getOrderStatus($data['order_status']) . "</td>
                        <td>" . $data['order_changes'] . "</td>
                        <td>" . $data['changes_time'] . "</td>
                        <td>" . $data['changes'] . "</td>
                                 
                    </tr>";

        }


        $str .= "</table></form>";
        return $str;

    }

    /*Вывод табличной части списков*/
    public static function view_table_list($data, $user)
    {

        $str = self::form_head('ListModule', $user);
        $str .= '
            <table border="1">                
                <tr>
                    <th>
                        Цвет браслета
                    </th>
  					<th>
		  				id
  					</th>  	
  					<th>
  						Статус
		  			</th>
  					<th>
		  				Имя
  					</th>
                    <th>
  						Проект
		  			</th>		  			
  					<th>
  						Изменения
		  			</th>
		  			<th>
  						Смена
		  			</th>
                    
  				</tr>';

        if (isset($data[0])) {
            //dd($user);
            foreach ($data as $items) {
                //$status = $items['status_changes'];
                //dd($items);
                $str .= "<tr>
                        <td style='background:black'> 
                            <input type='checkbox' name='id[]' value='" . $items['id'] . "' " . Lang::getDisabledOfStatys($items['changes_status'],$user->access) . "> 
                        </td>
                        <td>e" . $items['id'] . "</td>
                        <td>" . Lang::getChangeStatus($items['changes_status']) . "</td>
                        
                        <td>" . $items['surname'] . "</td>                
                        <td>" . $items['project'] . "</td>
                        <td>" . $items['changes_time'] . "</td>
                        <td>" . $items['changes'] . "
                        
                        </td>
                        
                    </tr>";
            }
        } elseif(!empty($data)) {
            //$status = $data['status_changes'];
            $str .= "<tr>
                        <td style='background:black'> 
                            <input type='checkbox' name='id[]' value='" . $data['id'] . "' ". Lang::getDisabledOfStatys($data['changes_status'],$user->access) ."> 
                        </td>
                        <td>e" . $data['id'] . "</td>          
                        <td>" . Lang::getChangeStatus($data['changes_status']) . "</td>                        
                        
                        <td>" . $data['surname'] . "</td>                
                        <td>" . $data['project'] . "</td>
                        <td>" . $data['changes_time'] . "</td>
                        <td>" . $data['changes'] . "</td>
                        
                    </tr>";
        }
        $str .= "</table></form>";

        return $str;

    }

    /*Вывод табличной части электронных билетов*/
    public static function view_table_order($data, $user)
    {
        $str = self::form_head('OrderModule', $user);
        $str .= '<table border="1">                
                <tr>
                    <th>
                        Отметить
                    </th>
  					<th>
		  				id
  					</th>
  					<th>
		  				id_заказа
  					</th>
  					<th>
  						Статус
		  			</th>  					
  					<th>
		  				Имя
  					</th>
  					<th>
		  				Фамилия/Ник
  					</th>
                    <th>
  						Телефон
		  			</th>
		  			<th>
  						Оплата
		  			</th>
		  			<th>
  						Дата оплаты
		  			</th>
  					<th>
  						Изменения
		  			</th>
		  			<th>
  						Смена
		  			</th>
		  			
  				</tr>';
        if (isset($data[0])) {
            foreach ($data as $items) {

                $str .= "<tr>
                         <td style='background: #C8A2C8;'>
                            <input type='checkbox' name='id[]' value='" . $items['id'] . "' ". Lang::getDisabledOfStatys($items['status'],$user->access) .">
                        </td>
                        <td>" . $items['id'] . "</td>
                        <td style='color: blue;'>" . $items['id_order'] . "</td>
                        <td>" . Lang::getChangeStatus($items['status']) . "</td>                        
                        <td>" . $items['fio'] . "</td>
                        <td>" . $items['nickname'] . "</td>                
                        <td>" . $items['phone'] . "</td>                
                        <td>" . Lang::getOrderStatus($items['order_status']) . "</td>
                        <td>" . $items['order_changes'] . "</td>
                        <td>" . $items['changes_time'] . "</td>
                        <td>" . $items['changes'] . "</td>                                                        
                               
                    </tr>";

            }
        } elseif(!empty($data)) {

            //$statusCh = $data['status_changes'];
            $str .= "<tr>  
                        <td style='background: #C8A2C8;'>
                            <input type='checkbox' name='id[]' value='" . $data['id'] . "' ". Lang::getDisabledOfStatys($data['status'],$user->access) .">
                        </td>
                        <td>" . $data['id'] . "</td>          
                        <td style='color: blue;'>" . $data['id_order'] . "</td>
                        <td>" . Lang::getChangeStatus($data['status']) . "</td>
                        
                        
                        <td>" . $data['fio'] . "</td>
                        <td>" . $data['nickname'] . "</td>                  
                        <td>" . $data['phone'] . "</td>                
                        <td>" . Lang::getOrderStatus($data['order_status']) . "</td>
                        <td>" . $data['order_changes'] . "</td>
                        <td>" . $data['changes_time'] . "</td>
                        <td>" . $data['changes'] . "</td>
                                 
                    </tr>";
        }


        $str .= "</table></form>";
        return $str;

    }

    /*Вывод табличной части электронных билетов*/
    public static function view_table_online($data, $user)
    {
        $str = self::form_head('OnlineModule', $user);
        $str .= '<table border="1">                
                <tr>
                    <th>
                        Отметить
                    </th>
  					<th>
		  				id
  					</th>
  					<th>
  						Статус
		  			</th>  					
  					<th>
		  				Имя
  					</th>
  					<th>
  						Изменения
		  			</th>
		  			<th>
  						Смена
		  			</th>
		  			
  				</tr>';
        if (isset($data[0])) {
            foreach ($data as $items) {

                $str .= "<tr>
                         <td style='background: #C8A2C8;'>
                            <input type='checkbox' name='id[]' value='" . $items['id'] . "' ". Lang::getDisabledOfStatys($items['status'],$user->access) .">
                        </td>
                        <td>" . $items['id'] . "</td>
                        <td>" . Lang::getChangeStatus($items['status']) . "</td>                        
                        <td>" . $items['name'] . "</td>
                        <td>" . $items['changes_time'] . "</td>
                        <td>" . $items['changes'] . "</td>                                                        
                               
                    </tr>";

            }
        } else {
            $items = $data;
            $str .= "<tr>background: white;
                         <td style='background: #C8A2C8;'>
                            <input type='checkbox' name='id[]' value='" . $items['id'] . "' ". Lang::getDisabledOfStatys($items['status'],$user->access) .">
                        </td>
                        <td>" . $items['id'] . "</td>
                        <td>" . Lang::getChangeStatus($items['status']) . "</td>                        
                        <td>" . $items['name'] . "</td>
                        <td>" . $items['changes_time'] . "</td>
                        <td>" . $items['changes'] . "</td>                                                        
                               
                    </tr>";
        }


        $str .= "</table></form>";
        return $str;

    }

    private static function get_number_live($ticket_id)
    {
        $result = '';
        if($ticket_id < 10) {
            $result.="0";
        }
        if($ticket_id < 100) {
            $result.="0";
        }
        if($ticket_id < 1000) {
            $result.="0";
        }


        $result.=$ticket_id;

        return $result;
    }

    /*вывод табличной части живых билетов*/
    public static function view_table_cards($data, $user)
    {
        $str = self::form_head('CardsModule',$user);
        $str .= '<table border="1">                
                <tr>
                    <th>
                        Отметить
                    </th>
  					<th>
		  				id
  					</th>
  					<th>
  						Статус
		  			</th> 
  					<th>
  						Изменения
		  			</th>
		  			<th>
  						Смена
		  			</th>
		  			
  				</tr>';
        if (isset($data[0])) {
            foreach ($data as $items) {
                $ticketId = self::get_number_live($items['ticket_id']);
                //$statusCh = $items['status_changes'];
                $str .= "<tr>
                        <td style='background: #C8A2C8;'> 
                            <input type='checkbox' name='id[]' value='" . $items['id'] . "' ". Lang::getDisabledOfStatys($items['status'],$user->access) .">
                        </td>
                        <td>" . $ticketId . "</td>
                        <td>" . Lang::getChangeStatus($items['status']) . "</td>  
                        <td>" . $items['changes_time'] . "</td>
                        <td>" . $items['changes'] . "</td>                                                        
                                     
                    </tr>";

            }
        } elseif(!empty($data)) {
            $ticketId = self::get_number_live($data['ticket_id']);
            //$statusCh = $data['status_changes'];
            $str .= "<tr>  
                        <td style='background: #C8A2C8;'> 
                            <input type='checkbox' name='id[]' value='" . $data['id'] . "' ". Lang::getDisabledOfStatys($data['status'],$user->access) .">
                        </td>
                        <td>" . $ticketId . "</td>          
                        <td>" . Lang::getChangeStatus($data['status']) . "</td>   
                        <td>" . $data['changes_time'] . "</td>
                        <td>" . $data['changes'] . "</td>
                                  
                    </tr>";
        }


        $str .= "</table></form>";
        return $str;
    }

    /*вывод вверха формы*/
    private static function form_head($type, $user = null)
    {
        $selecthtml = '<select name="status"> <option value="1">Пропустить</option>';
        if (!empty($user) && $user->access == "z") $selecthtml .= '<option value="2">Выдать карточку</option>  <option value="del">удалить</option>';
        /*if ($type=='ListModule') {
            $selecthtml .= '<option value="del">удалить</option>';
        }*/
        if($user->access == "z") $selecthtml .= '<option value="0">Отменить</option>';
        $selecthtml .= '</select>';
        $str = '<form action="#' . Lang::getTabs($type) . '" method="POST">
                <input type="hidden" name="type" value="' . $type . '">
                <input type="hidden" name="changes_time" value="' . date("d.m.Y H:i:s", time()) . '">
                <input type="hidden" name="changes" value="' . $_SESSION['change_id'] . '">                
                ' . $selecthtml . '
                <input type="submit" value="Выполнить">';

        return $str;
    }

    /*выводить форму */
    public static function form_bey($val = null)
    {
        $str = "<form action='/live' method='post'>
                <p>Номер билета:</p>
                <input name='ticket_id' type='number' value='" . $val . "'>
                <input type='submit' value='ПрОпУсТиТь'>
              </form>";
        self::view($str);
    }

    /*показать таблицу списков*/
    public static function view_table_change($date)
    {
        $str= '<table border="1">                
                <tr>
  					<th>
		  				id
  					</th>
  					<th>
  						Место
		  			</th>  					
  					<th>
		  				Время начала
  					</th>
  					<th>
		  				Время конца
  					</th>
                    <th>
  						Списки
		  			</th>
		  			<th>
  						Живые Билеты
		  			</th>
		  			<th>
  						Электронная предпродажа
		  			</th>
		  			<th>
  						Френдли
		  			</th>
		  			<th>
  						Авто
		  			</th>
		  			<th>
  						Продажа билетов на входе
		  			</th>
                    <th>
  						Денег за продажу
		  			</th>
  					<th>
  						Кто на смене
		  			</th>		  			
  				</tr>';
        if(isset($date[0]))
        {
            foreach ($date as $item)
            {
                $str .= "<tr>
                        <td>".  $item['id']."</td>
                        <td>" . $item['login'] . "</td>
                        <td>" . $item['start'] . "</td>  
                        <td>" . $item['final'] . "</td>
                        <td>" . $item['ListModule'] . "</td>
                        <td>" . $item['CardsModule'] . "</td>
                        <td>" . $item['OrderModule'] . "</td>
                        <td>" . $item['MachineNumbersModule'] . "</td>
                        <td>" . $item['BrasletModule'] . "</td>
                        <td>" . $item['price'] . "</td>
                        <td>" . $item['commint'] . "</td>
                    </tr>";

            }
        }
        else
        {
            $str .= "<tr>
                        <td>".  $date['id']."</td>
                        <td>" . $date['login'] . "</td>
                        <td>" . $date['start'] . "</td>  
                        <td>" . $date['final'] . "</td>
                        <td>" . $date['ListModule'] . "</td>
                        <td>" . $date['CardsModule'] . "</td>
                        <td>" . $date['OrderModule'] . "</td>
                        <td>" . $date['BrasletModule'] . "</td>
                        <td>" . $date['FriendlyModule'] . "</td>
                        <td>" . $date['MachineNumbersModule'] . "</td>
                        <td>" . $date['price'] . "</td>
                        <td>" . $date['commint'] . "</td>
                    </tr>";
        }
        $str .= "<table>";
        self::view($str);
    }


    public static function view_form_list(){
        //'project','surname','name','comment'
        $strfun="<div>Название проекта:<input name='project[]' type='text'><input name='surname[]' type='text'>Имя:<input name='name[]' type='text'>Комментарий<textarea name='comment[]'></textarea><a href='javascript:void(0);' class='bug' onclick='AddList()'>+</a><br/></div>";

         $str="<form action='' method='post'>
                    <div id='list'>
                        <div>
                            Название проекта:
                            <input name='project[]' type='text'>
                            Фамилия:
                            <input name='surname[]' type='text'>
                            Имя:
                            <input name='name[]' type='text'>
                            Комментарий
                            <textarea name='comment[]'></textarea>
                            <select name='type[]'>
                                <option value='2'> ярмарки/гости/сми</option>
                                <option value='0'> Art / музыканты  </option>
                            </select>    
                            <a href='javascript:void(0);' class='bug' onclick='AddList()'>+</a>
                            <br/>
                        </div>
                    </div>
                    
                    <div id='AddForm' style='display: none'>
                        <div>
                            Название проекта:
                            <input name='project[]' type='text'>
                            Фамилия:
                            <input name='surname[]' type='text'>
                            Имя:
                            <input name='name[]' type='text'>
                            Комментарий
                            <textarea name='comment[]'></textarea>
                            <select name='type[]'>
                                <option value='2'> ярмарки/гости/сми</option>
                                <option value='0'> Art / музыканты </option>
                            </select>  
                            <a href='javascript:void(0);' class='bug' onclick='AddList()'>+</a>
                            <a href='javascript:void(0);' class='bug' onclick='$(this).parent().remove();'>del</a>
                            <br/>                        
                        </div>
                    </div>
                    <input type='submit' value='Добавить в базу'>    
                </form>
                <script>
                    function AddList() {
                        var str=$('#AddForm').html();
                        $( '#list' ).append(str);  
                    } 
                </script>
                ";
         self::view($str);
    }

    public static function view_form_braslet()
    {
        $str="<form action='' method='post'>
                    <label>
                        <b>Цена</b>
                        <input type='number' name='price'>
                    </label>
                    <label>
                        <b>Номер браслета</b> 
                        <input type='search' name='number'>
                    </label>
                    <input type='submit' value='Продать'>
              </form>
            ";
        self::view($str);
    }

    public static function view_table_machine($data, $user)
    {
        $str = self::form_head('MachineNumbersModule', $user);
        $str .= '<table border="1">                
                <tr>
                    <th>
                        Отметить
                    </th>
  					<th>
		  				Номер авто
  					</th>
  					<th>
		  				Проект
  					</th>
  					<th>
		  				Статус
  					</th>  					
  					<th>
  						Изменения
		  			</th>
		  			<th>
  						Смена
		  			</th>
		  			
  				</tr>';
        if (isset($data[0])) {
            foreach ($data as $items) {
                $str .= "<tr>
                         <td style='background: purple;'>
                            <input type='checkbox' name='id[]' value='" . $items['id'] . "' ". Lang::getDisabledOfStatys($items['status'],$user->access) .">
                        </td>
                        <td>" . $items['number'] . "</td>
                        <td>" . $items['project_name'] . "</td>
                        <td>" . Lang::getChangeStatus($items['status']) . "</td>    
                        <td>" . $items['changes_time'] . "</td>
                        <td>" . $items['changes'] . "</td>                                                        
                               
                    </tr>";

            }
        } elseif(!empty($data)) {

            //$statusCh = $data['status_changes'];
            $str .= "<tr>  
                        <td style='background: white;'>
                            <input type='checkbox' name='id[]' value='" . $data['id'] . "' ". Lang::getDisabledOfStatys($data['status'],$user->access) .">
                        </td>
                        <td>" . $data['number'] . "</td>          
                        <td>" . $data['project_name'] . "</td>
                        <td>" . Lang::getChangeStatus($data['status']) . "</td>
                        <td>" . $data['changes_time'] . "</td>
                        <td>" . $data['changes'] . "</td>
                    </tr>";
        }


        $str .= "</table></form>";
        return $str;
    }

    /* френдли  */
    public static function view_table_friendly($data, $user)
    {
        $str = self::form_head('FriendlyModule', $user);
        $str .= '<table border="1">                
                <tr>
                    <th>
                        Отметить
                    </th>
  					<th>
		  				id
  					</th>
  					<th>
		  				email
  					</th>
  					<th>
		  				Статус
  					</th>
  					<th>
  						ФИО
		  			</th>  					
  					<th>
		  				Продавец
  					</th>
  					<th>
  						Изменения
		  			</th>
		  			<th>
  						Смена
		  			</th>
		  			
  				</tr>';
        if (isset($data[0])) {
            foreach ($data as $items) {
                $str .= "<tr>
                         <td style='background: #C8A2C8;'>
                            <input type='checkbox' name='id[]' value='" . $items['id'] . "' ". Lang::getDisabledOfStatys($items['status'],$user->access) .">
                        </td>
                        <td>f" . $items['id'] . "</td>
                        <td style='color: purple;'>" . $items['email'] . "</td>
                        <td>" . Lang::getChangeStatus($items['status']) . "</td>                        
                        <td>" . $items['fio_friendly'] . "</td>
                        <td>" . $items['seller'] . "</td>
                        <td>" . $items['changes_time'] . "</td>
                        <td>" . $items['changes'] . "</td>                                                        
                               
                    </tr>";

            }
        } elseif(!empty($data)) {

            //$statusCh = $data['status_changes'];
            $str .= "<tr>  
                        <td style='background: #C8A2C8;'>
                            <input type='checkbox' name='id[]' value='" . $data['id'] . "' ". Lang::getDisabledOfStatys($data['status'],$user->access) .">
                        </td>
                        <td>f" . $data['id'] . "</td>          
                        <td style='color: blue;'>" . $data['email'] . "</td>
                        <td>" . Lang::getChangeStatus($data['status']) . "</td>
                        <td>" . $data['fio_friendly'] . "</td>
                        <td>" . $data['seller'] . "</td>
                        <td>" . $data['changes_time'] . "</td>
                        <td>" . $data['changes'] . "</td>
                    </tr>";
        }


        $str .= "</table></form>";
        return $str;
    }
}
