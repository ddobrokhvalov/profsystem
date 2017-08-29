<?php
/**
 * Модуль "Подписка"
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class m_subscription extends module
{
	/**
	* Объект шаблонизатора модуля
	* 
	* @todo Не перенести ли его в module.php? И не создавать ли его в init()?
	*/
	protected $tpl;
	
	/**
	* Диспетчер модуля
	*/
	protected function content_init()
	{
		$_MESSAGES_ = array();
		
		// Инициализация шаблонизатора
		$this -> tpl = new smarty_ee_module( $this );
		
		// если подписка, то проверяем наличие e-mail в БД
		if ($this->q_param["action"] == 'subscribe') {
			$tpl_file = "subscribe_form.tpl";

			// проверка правильности заполнения полей, если все в порядке, то пишем юзера в БД
			if (preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9_-]+(\.[_a-z0-9-]+)+$/i', $this->q_param["email"]) && $this->q_param["fio"]!='') {
				$isEmailPresent = db::sql_select("select ACTIVE, PASSWORD from SUBSCRIBER where EMAIL = :email", array( 'email' => $this->q_param["email"]) );

				// если e-mail отсутствует, то добавляем нового подписчика
				if (count($isEmailPresent) < 1) {
				    	// генерация пароля и добавление юзера в БД
				    	$userPassword = $this->mkpasswd();
					db::insert_record('SUBSCRIBER', array(
									'FIO' => $this->q_param["fio"],
									'EMAIL' => $this->q_param["email"],
									'ORGANIZATION' => $this->q_param["organization"],
									'PASSWORD' => $userPassword));

					// если были указаны листы подписки, то добавление связей подписчик <=> лист подписки
					if (is_array($this->q_param["list"]) && count($this->q_param["list"]) > 0) {
						$lastInsertId = db::sql_select("select max(SUBSCRIBER_ID) as LAST_SUBSCRIBER_ID from SUBSCRIBER");
						foreach ($this->q_param["list"] as $listId) {
							db::insert_record('SUBSCRIBER_SUBSCRIBE_LIST', array(
													'SUBSCRIBER_ID' => $lastInsertId[0]['LAST_SUBSCRIBER_ID'],
													'SUBSCRIBE_LIST_ID' => $listId));
						}
					}

					// заполнение шаблона письма данными учетной записи подписавшегося
					$email_tmpl = new smarty_ee_module( $this );

					$email_tmpl->assign("EMAIL", htmlspecialchars($this->q_param["email"], ENT_QUOTES)); #!
					$email_tmpl->assign("FIO", htmlspecialchars($this->q_param["fio"], ENT_QUOTES)); #!
					$email_tmpl->assign("ORGANIZATION", htmlspecialchars($this->q_param["organization"], ENT_QUOTES)); #!
					if (is_array($this->q_param["list"]) && count($this->q_param["list"]) > 0) {
						$email_tmpl->assign("SUBSCRIBE_LIST",db::sql_select("select LIST_NAME from SUBSCRIBE_LIST where SUBSCRIBE_LIST_ID in ( " . lib::array_make_in($this->q_param["list"],"",true) . " )"));
					}
					$email_tmpl->assign("URL_SUBMIT", "http://".$_SERVER["SERVER_NAME"].
									  $this->get_url_by_page($this->env["page_id"]).
									  "?action_".$this->env["area_id"]."=subscribe_submit".
									  "&email_".$this->env["area_id"]."=".$this->q_param["email"].
									  "&pwd_".$this->env["area_id"]."=".urlencode($userPassword));
					// отправка письма
					lib::post_mail(
						$this->q_param["email"],
						$this->q_param["fio"],
						$this->view_param["subscription_sender_email"],
						$this->view_param["subscription_sender_name"],
						$this->view_param["subscription_notification_subject"],
						$email_tmpl->fetch($this -> tpl_dir . "notification.tpl"),
						params::$params['encoding']['value']);

					$this -> tpl -> assign("SUBSCRIBED", 1);
				}
				// если e-mail уже есть в БД
				else {
					// если учетная запись с флагом "активный", то отправка уведомления на e-mail с просьбой подтверждения введенных данных
					if ($isEmailPresent[0]['ACTIVE'] == 1) {
						// заполнение шаблона письма данными учетной записи подписавшегося
						$email_tmpl = new smarty_ee_module( $this );

						$email_tmpl->assign("EMAIL", htmlspecialchars($this->q_param["email"], ENT_QUOTES)); #!
						$email_tmpl->assign("FIO", htmlspecialchars($this->q_param["fio"], ENT_QUOTES)); #!
						$email_tmpl->assign("ORGANIZATION", htmlspecialchars($this->q_param["organization"], ENT_QUOTES)); #!
						if (is_array($this->q_param["list"]) && count($this->q_param["list"]) > 0) {
							$email_tmpl->assign("SUBSCRIBE_LIST",db::sql_select("select LIST_NAME from SUBSCRIBE_LIST where SUBSCRIBE_LIST_ID in ( " . lib::array_make_in($this->q_param["list"],"",true) . " )"));
						}
						$email_tmpl->assign("URL_SUBMIT", "http://".$_SERVER["SERVER_NAME"].
										  $this->get_url_by_page($this->env["page_id"]).
										  "?action_".$this->env["area_id"]."=changes_submit".
										  "&pwd_".$this->env["area_id"]."=".urlencode($isEmailPresent[0]['PASSWORD']).
										  "&email_".$this->env["area_id"]."=".$this->q_param["email"].
										  "&fio_".$this->env["area_id"]."=".urlencode($this->q_param["fio"]).
										  ($this->q_param["organization"] != '' ? "&organization_".$this->env["area_id"]."=".urlencode($this->q_param["organization"]) : '').
										  (is_array($this->q_param["list"]) && count($this->q_param["list"]) > 0 ? "&list_".$this->env["area_id"]."[]=".implode("&list_".$this->env["area_id"]."[]=", $this->q_param["list"]) : ""));
						$email_tmpl->assign("URL_CHANGE", "http://".$_SERVER["SERVER_NAME"].
										  $this->get_url_by_page($this->env["page_id"]).
										  "?action_".$this->env["area_id"]."=subscribe_change".
										  "&pwd_".$this->env["area_id"]."=".urlencode($isEmailPresent[0]['PASSWORD']).
										  "&email_".$this->env["area_id"]."=".$this->q_param["email"]);
						// отправка письма
						lib::post_mail(
							$this->q_param["email"],
							$this->q_param["fio"],
							$this->view_param["subscription_sender_email"],
							$this->view_param["subscription_sender_name"],
							$this->view_param["subscription_changes_subject"],
							$email_tmpl->fetch($this -> tpl_dir . "changes.tpl"),
							params::$params['encoding']['value']);

						$this -> tpl -> assign("CHANGED", 1);
					}
					// отлуп, если флаг "активный" сброшен
					else {
						$this -> tpl -> assign("CANT_CHANGED", 1);
					}
				}
			}
			// если были ошибки в заполнении полей, то делаем отлуп
			else {
				if (!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9_-]+(\.[_a-z0-9-]+)+$/i', $this->q_param["email"]))
					$_MESSAGES_[] = array("MESSAGE" => "Некорректно заполнено поле \"E-mail\".");

				if ($this->q_param["fio"] == '')
					$_MESSAGES_[] = array("MESSAGE" => "Не заполнено поле \"Ф.И.О.\".");

				$this -> tpl -> assign("MESSAGES", $_MESSAGES_);
			}
		}
		// если было подтверждение подписки или подтверждение изменения параметров
		elseif ($this->q_param["action"] == "subscribe_submit" || $this->q_param["action"] == "changes_submit") {
			$tpl_file = "submit.tpl";

			// получение ID подписчик по e-mail и паролю
			$userId = db::sql_select("select SUBSCRIBER_ID from SUBSCRIBER where EMAIL = :email
						     and PASSWORD = :password and ".
						     ($this->q_param["action"] == "subscribe_submit" ? "(ACTIVE = 0 or ACTIVE is null)" : "ACTIVE = 1"),
							 array('email'=>$this->q_param["email"], 'password'=>$this->q_param["pwd"]));

			// выставляем флаг "активный", если было подтверждение подписки
			if ($this->q_param["action"] == "subscribe_submit") {
				if (count($userId) > 0) {
					db::update_record('SUBSCRIBER', array('ACTIVE' => 1), "", array("SUBSCRIBER_ID"=>$userId[0]['SUBSCRIBER_ID']));
				}
				else {
					$this -> tpl -> assign("ERROR", 1);
				}

				$this -> tpl -> assign("SUBSCRIBE_SUBMIT", 1);
			}
			// обновляем учетную запись, если было подтверждение изменения параметров
			else {
				// обновляем, если есть ID юзера
				if (count($userId) > 0) {
					db::update_record('SUBSCRIBER', array(
									'FIO' => $this->q_param["fio"],
									'EMAIL' => $this->q_param["email"],
									'ORGANIZATION' => $this->q_param["organization"]), "",
									array("SUBSCRIBER_ID"=>$userId[0]['SUBSCRIBER_ID']));

					// выборка листов подписки данного сайта и данной языковой версии
					$listArray = db::sql_select( "
						select	SUBSCRIBE_LIST.SUBSCRIBE_LIST_ID as ID
						from	SUBSCRIBE_LIST, SUBSCRIBE_LIST_SITE
						where	SUBSCRIBE_LIST.SUBSCRIBE_LIST_ID = SUBSCRIBE_LIST_SITE.SUBSCRIBE_LIST_ID
							and SUBSCRIBE_LIST_SITE.SITE_ID = :site_id
							and SUBSCRIBE_LIST.LANG_ID = :lang_id",
						array( 'site_id' => $this -> env['site_id'], 'lang_id' => $this -> env['lang_id'] ) );
					
					foreach ($listArray as $listId) {
						$lists[] = $listId["ID"];
					}

					// удаление листов подписки текущего пользователя
					if ( count( $listArray ) > 0 ) {
						db::sql_query( "
							delete
							from	SUBSCRIBER_SUBSCRIBE_LIST
							where	SUBSCRIBER_ID = '{$userId[0]['SUBSCRIBER_ID']}'
								and SUBSCRIBE_LIST_ID in (".implode( ",", $lists ).")"
							);
					}
					
					// если были указаны листы подписки, то добавление связей подписчик <=> лист подписки
					if (is_array($this->q_param["list"]) && count($this->q_param["list"]) > 0) {
						foreach ($this->q_param["list"] as $listId) {
							db::insert_record('SUBSCRIBER_SUBSCRIBE_LIST', array(
													'SUBSCRIBER_ID' => $userId[0]['SUBSCRIBER_ID'],
													'SUBSCRIBE_LIST_ID' => $listId));
						}
					}
				}
				// выдаем ошибку, если ID отсутствует
				else {
					$this -> tpl -> assign("ERROR", 1);
				}
				
				$this -> tpl -> assign("CHANGES_SUBMIT", 1);
			}
		}
		// вывод формы подписки
		else {
			$tpl_file = "subscribe_form.tpl";

			if ($this->q_param["action"] == 'subscribe_change') {
				$subscriber = db::sql_select("select SUBSCRIBER_ID, FIO, ORGANIZATION from SUBSCRIBER ".
							 "where EMAIL = :email and PASSWORD = :password and ACTIVE = 1",
							 array('email'=>$this->q_param["email"], 'password'=>$this->q_param["pwd"]));

				if (count($subscriber) > 0) {
					$this -> tpl -> assign("EMAIL", $this->q_param["email"]);
					$this -> tpl -> assign("FIO", $subscriber[0]["FIO"]);
					$this -> tpl -> assign("ORGANIZATION", $subscriber[0]["ORGANIZATION"]);

					$_lists_ =   db::sql_select("select SUBSCRIBE_LIST_ID from SUBSCRIBER_SUBSCRIBE_LIST ".
								"where SUBSCRIBER_ID = :SUBSCRIBER_ID", array('SUBSCRIBER_ID' => $subscriber[0]["SUBSCRIBER_ID"]));
					foreach ($_lists_ as $row) {
						$subscribed[$row["SUBSCRIBE_LIST_ID"]] = 1;
					}
				}
			}

			$lists = db::sql_select( "
				select	SUBSCRIBE_LIST.SUBSCRIBE_LIST_ID as ID,
					SUBSCRIBE_LIST.LIST_NAME,
					SUBSCRIBE_LIST.LIST_DESCRIPTION
				from	SUBSCRIBE_LIST, SUBSCRIBE_LIST_SITE
				where	SUBSCRIBE_LIST.SUBSCRIBE_LIST_ID = SUBSCRIBE_LIST_SITE.SUBSCRIBE_LIST_ID
					and SUBSCRIBE_LIST_SITE.SITE_ID = :site_id
					and SUBSCRIBE_LIST.LANG_ID = :lang_id
				order by SUBSCRIBE_LIST.LIST_ORDER",
				array( 'site_id' => $this -> env['site_id'], 'lang_id' => $this -> env['lang_id'] ) );
			
			foreach ($lists as $list_id=>$list_item) {
				if ($subscribed[$list_item["ID"]]) $lists[$list_id]["CHECKED"] = 'checked = "checked"';
			}
			
			$this -> tpl -> assign( "SUBSCRIBE_LIST", $lists );
			
			$this -> tpl -> assign( "FORM_FORMAT", $this->view_param["form_format"] );
		}
		
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir . $tpl_file );
	}
	
	// функция генерации пароля
	protected function mkpasswd()
	{
		$reg_password = "";
		$tmp_array = array();
		$up_alpha  = range(65, 90);
		$low_alpha = range(97, 122);
		$number    = range(48, 57);
		$misc      = array("!", "#", "\$", "%", "&", "(", ")",
				   "*", "+", "-", "=", "]", "^", "_");

		srand ((double)microtime()*1000000);

		while (sizeof($tmp_array) < 7) {
			// добавить 0-1 спец. символ
			for ($i = 0; $i < rand(0, 1); $i++) {
				$tmp_array[] = $misc[rand(1, count($misc)) - 1];
			}
			// добавить 1-2 цифры
			for ($i = 0; $i < rand(1, 2); $i++) {
				$tmp_array[] = $number[rand(1, count($number)) - 1];
			}
			// добавить 2-3 заглавных буквы
			for ($i = 0; $i < rand(2, 3); $i++) {
				$tmp_array[] = $up_alpha[rand(1, count($up_alpha)) - 1];
			}
			// добавить 3-5 строчных буквы
			for ($i = 0; $i < rand(3, 5); $i++) {
				$tmp_array[] = $low_alpha[rand(1, count($low_alpha)) - 1];
			}
		}

		shuffle($tmp_array);

		foreach ($tmp_array as $value) {
			$reg_password .= (is_numeric($value) ? Chr($value) : $value);
		}

		return $reg_password;
	}
}
?>
