<?php
/**
 * Модуль "Пользователи"
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class m_client extends module
{
	/**
	 * Объект шаблонизатора модуля
	 */
	protected $tpl;
	
	/**
	 * Признак аутентификации пользователя
	 */
	protected $is_registrated = false;
	
	/**
	 * Диспетчер модуля
	 */
	protected function content_init()
	{
		// Необходимо для работы каптчи
		session_start();
		
		// Инициализация шаблонизатора
		$this -> tpl = new smarty_ee_module( $this );
		
		// Передаем в шаблон флаг разрешения авторизации по OpenID
		$this -> tpl -> assign( 'openid_mode', params::$params['openid_mode']['value'] );
		
		$this -> is_registrated = self::is_registrated_client();
		
	    if ( $this -> is_registrated || in_array( $this -> view_param['view_mode'], array( 'auth', 'registration', 'reminder' ) ) )
	    	$template_file = $this -> view_param['view_mode'] . '.tpl';
		else
	    	$template_file = 'auth.tpl';
		
		if ( $this -> view_param['view_mode'] == 'auth' )
			$this -> mode_auth();
		elseif ( $this -> view_param['view_mode'] == 'registration' )
			$this -> mode_registration();
		elseif ( $this -> view_param['view_mode'] == 'reminder' )
			$this -> mode_reminder();
		elseif ( $this -> view_param['view_mode'] == 'personal_data' )
			$this -> mode_personal_data();
		
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir . $template_file );
	}
	
	/**
	 * Возвращает запись текущего пользователя
	 *
	 * @return mixed
	 */
	public static function get_current_client()
	{
		$client_record = db::sql_select( 'select * from CL_CLIENT where CL_CLIENT_ID = :client_id',
			array( 'client_id' => $_COOKIE['client_id'] ) );
		
		return count( $client_record ) > 0 ? $client_record[0] : false;
	}
	
	/**
	 * Кодирует инфоормацию о пользователе
	 */
	public static function get_client_key( $client_id, $client_email, $client_password_md5 )
	{
		return md5( $client_id . '|' . $client_email . '|' . $client_password_md5 );
	}
	
	/**
	 * Проверка регистрации пользователя
	 *
	 * @return boolean
	 */
	public static function is_registrated_client()
	{
		$client_record = self::get_current_client();
		
		if ( $client_record === false ) return false;
		
		$client_key = self::get_client_key( $client_record['CL_CLIENT_ID'],
			( params::$params['openid_mode']['value'] && $client_record['OPENID'] ) ? $client_record['OPENID'] : $client_record['EMAIL'], $client_record['PASSWORD_MD5'] );
		
		return $client_key === $_COOKIE['client_key'];
	}
	
	/**
	 * Устанавливает "куки" на стороне аутентифицированного пользователя
	 */
	public static function set_cookie_to_client( $client_id = '', $client_key = '', $out_computer = false )
	{
		$time = !$out_computer ? time() + 365 * 24 * 60 * 60 : 0;
		
		$site = db::sql_select( 'select HOST from SITE order by SITE_ID' );
		
		setcookie( 'client_id', $client_id, $time, '/', '.' . $site[0]['HOST'] );
		setcookie( 'client_key', $client_key, $time, '/', '.' . $site[0]['HOST'] );
	}
	
	/**
	 * Проверяет закрыт ли раздел
	 *
	 * @return boolean
	 */
	public function is_protected_page()
	{
		$query = 'select IS_PROTECTED from PAGE where PAGE_ID = :page_id and SITE_ID = :site_id and VERSION = :version';
		
		$result = db::sql_select( $query, array( 'page_id' => $this -> env['page_id'],
			'site_id' => $this -> env['site_id'], 'version' => $this -> env['version'] ) );

		return $result[0]['IS_PROTECTED'] ? true : false;
	}	
	
	/**
	 * Вариант использования "Форма аутентификации"
	 * 
	 * @return mixed
	 */
	public function mode_auth()
	{
		if ( $_GET['from_url'] ) $this -> q_param['from_url'] = $_GET['from_url'];
		
		$path_and_area = $this -> get_url_by_module_param( 'CLIENT', 'view_mode', 'reminder', $this -> env['block_id'] );
		$this -> tpl -> assign( 'path_to_reminder', $path_and_area['PATH'] );
		
		$path_and_area = $this -> get_url_by_module_param( 'CLIENT', 'view_mode', 'registration', $this -> env['block_id'] );
		$this -> tpl -> assign( 'path_to_registration', $path_and_area['PATH'] );
		
		$this -> tpl -> assign( 'from_url', htmlspecialchars( $this -> q_param['from_url'], ENT_QUOTES ) );
		$this -> tpl -> assign( 'email', htmlspecialchars( $this -> q_param['email'], ENT_QUOTES ) );
		
		if ( params::$params['openid_mode']['value'] )
			$this -> tpl -> assign( 'openid', htmlspecialchars( $this -> q_param['openid'], ENT_QUOTES ) );
		
		if ( $this -> q_param['exit'] )
		{
			self::set_cookie_to_client();
			
			if ( $this -> is_protected_page() )
			{
				$path_and_area = $this -> get_url_by_module_param( 'CLIENT', 'view_mode', 'auth', $this -> env['block_id'] );
				$location = $path_and_area['PATH'];
			}
			
			header( 'Location: ' . ( $location ? $location : 'index.php' ) );
			exit;
		}
		
		if ( $this -> is_registrated )
		{
			if ( $this -> q_param['from_url'] )
				header( 'Location: ' . base64_decode( $this -> q_param['from_url'] ) );
			
			$client_record = self::get_current_client();
			
			$this -> tpl -> assign( 'is_registrated', 1 );
			
			$this -> tpl -> assign( 'name', htmlspecialchars( $client_record['NAME'], ENT_QUOTES ) );
			$this -> tpl -> assign( 'surname', htmlspecialchars( $client_record['SURNAME'], ENT_QUOTES ) );
			
			if ( params::$params['openid_mode']['value'] )
				$this -> tpl -> assign( 'openid', htmlspecialchars( $client_record['OPENID'], ENT_QUOTES ) );
			
			return true;
		}
		
		$errors = array();
		
		if ( params::$params['openid_mode']['value'] && $this -> q_param['openid'] )
		{
			$consumer = $this -> getConsumer();
			
			$auth_request = $consumer -> begin( $this -> q_param['openid'] );
			
			if ( !$auth_request )
			{
				$errors[] = $this -> lang['sysw_client_error_not_a_valid_openid'];
			}
			else if ( $auth_request -> shouldSendRedirect() )
			{
				$redirect_url = $auth_request -> redirectURL( $this -> getTrustRoot(), $this -> getReturnTo() );
				
				header( 'Location: ' . $redirect_url );
				exit;
			}
			else
			{
				$form_html = $auth_request -> htmlMarkup( $this -> getTrustRoot(), $this -> getReturnTo(), false, array( 'id' => 'openid_message' ) );
				
				print $form_html;
				exit;
			}
		}
		else if ( params::$params['openid_mode']['value'] && $_GET['openid_mode'] )
		{
			$consumer = $this -> getConsumer();
			
			$response = $consumer -> complete( $this -> getReturnTo() );
			
			if ( $response -> status == Auth_OpenID_SUCCESS )
			{
				$identity_structure = parse_url( $_GET['openid_identity'] );
				$openid = preg_replace( '/\/$/', '', $identity_structure['host'] . $identity_structure['path'] );
				
				$client_record = db::sql_select( 'select * from CL_CLIENT where OPENID = :openid',
					array( 'openid' => $openid ) );
				
				if ( count( $client_record ) == 0 )
				{
					db::insert_record( 'CL_CLIENT', array( 'OPENID' => $openid, 'SURNAME' => $openid, 'PASSWORD_MD5' => md5( session_id() ) ) );
					
					$client_record = db::sql_select( 'select * from CL_CLIENT where OPENID = :openid',
						array( 'openid' => $openid ) );
				}
			}
			else if ( $response -> status == Auth_OpenID_CANCEL )
			{
				$errors[] = $this -> lang['sysw_client_error_verification_cancelled'];
			}
			else if ( $response -> status == Auth_OpenID_FAILURE )
			{
				$errors[] = $this -> lang['sysw_client_error_authentication_failed'];
			}
			else
			{
				$errors[] = $this -> lang['sysw_client_error_no_response'];
			}				
		}
		else if ( $this -> q_param['email'] )
		{
			$client_record = db::sql_select( 'select * from CL_CLIENT where EMAIL = :email and PASSWORD_MD5 = md5( :password )',
				array( 'email' => $this -> q_param['email'], 'password' => $this -> q_param['password'] ) );
			
			if ( count( $client_record ) == 0 )
			{
				$errors[] = $this -> lang['sysw_client_error1'];
			}
		}
		else
		{
			return false;
		}
		
		if ( count( $errors ) )
		{
			$this -> tpl -> assign( 'errors', $errors );
			return false;
		}
		
		$client_key = self::get_client_key( $client_record[0]['CL_CLIENT_ID'],
			( params::$params['openid_mode']['value'] && $client_record[0]['OPENID'] ) ? $client_record[0]['OPENID'] : $client_record[0]['EMAIL'], $client_record[0]['PASSWORD_MD5'] );
		
		self::set_cookie_to_client( $client_record[0]['CL_CLIENT_ID'], $client_key, $this -> q_param['out_computer'] );
		
		if ( $this -> q_param['from_url'] )
			$location = base64_decode( $this -> q_param['from_url'] );
		else if ( $this -> view_param['path_after_auth'] )
			$location = $this -> get_url_by_page( $this -> view_param['path_after_auth'] );
		else
			$location = 'index.php';
		
		header( 'Location: ' . $location );
	}
	
	/**
	 * Вариант использования "Форма регистрации"
	 */
	protected function mode_registration()
	{
		if ( !$this -> is_correct_client_data() )
			return false;
		
		db::insert_record( 'CL_CLIENT', array(
			'PASSWORD_MD5' => md5( $this -> q_param['PASSWORD'] ),
			'SURNAME' => $this -> q_param['SURNAME'],
			'NAME' => $this -> q_param['NAME'],
			'PATRONYMIC' => $this -> q_param['PATRONYMIC'],
			'CLIENT_TYPE' => $this -> q_param['CLIENT_TYPE'],
			'EMAIL' => $this -> q_param['EMAIL'],
			'LEGAL_PERSON' => $this -> q_param['LEGAL_PERSON'],
			'ADDRESS' => $this -> q_param['ADDRESS'],
			'INN' => $this -> q_param['INN'],
			'KPP' => $this -> q_param['KPP'],
			'R_ACCOUNT' => $this -> q_param['R_ACCOUNT'],
			'BANK_NAME' => $this -> q_param['BANK_NAME'],
			'K_ACCOUNT' => $this -> q_param['K_ACCOUNT'],
			'BIK' => $this -> q_param['BIK'],
			'CODE_OKPO' => $this -> q_param['CODE_OKPO'],
			'CODE_OKVED' => $this -> q_param['CODE_OKVED'],
			'TELEPHONE' => $this -> q_param['TELEPHONE'],
			'FAX' => $this -> q_param['FAX'] ) );
		
		$client_id = db::last_insert_id( 'CL_CLIENT_SEQ' );
		
		$client_key = self::get_client_key( $client_id, $this -> q_param['EMAIL'], md5( $this -> q_param['PASSWORD'] ) );
		
		self::set_cookie_to_client( $client_id, $client_key );
		
		// Инициализация шаблонизатора
		$notify_tpl = new smarty_ee_module( $this );
		
		$notify_tpl -> assign( lib::array_htmlspecialchars( $this -> q_param ) );
		$notify_tpl -> assign( 'HTTP_HOST', $_SERVER['HTTP_HOST'] );
		
		$client_message = $notify_tpl -> fetch( $this -> tpl_dir . 'client_notice.tpl' );
		$manager_message = $notify_tpl -> fetch( $this -> tpl_dir . 'manager_notice.tpl' );
		
		// Отправка письма пользователю
		lib::post_mail( $this -> q_param['EMAIL'], "{$this -> q_param['SURNAME']} {$this -> q_param['NAME']} {$this -> q_param['PATRONYMIC']}",
			'admin@' . $_SERVER['SERVER_NAME'], $this -> lang['sysw_client_registration_from'] . ' ' . $_SERVER['HTTP_HOST'],
				$this -> lang['sysw_client_registration_subj'] . ' ' . $_SERVER['HTTP_HOST'], $client_message );
		
		// Отправка писем администраторам
		if ( preg_match_all( '/[a-z0-9_\.-]+@[a-z0-9_\.-]+\.[a-z]{2,}/i', $this -> view_param['email_to_notice'], $matches ) )
		{
			foreach( $matches[0] as $email )
				lib::post_mail( $email, $this -> lang['sysw_administrator'],
					'admin@' . $_SERVER['SERVER_NAME'], $this -> lang['sysw_client_registration_from'] . ' ' . $_SERVER['HTTP_HOST'],
						$this -> lang['sysw_client_registration_subj'] . ' ' . $_SERVER['HTTP_HOST'], $manager_message );
		}
		
		$this -> tpl -> assign( 'is_registrated', 1 );
	}
	
	/**
	 * Вариант использования "Личные данные"
	 */
	protected function mode_personal_data()
	{
		if ( !$this -> mode_auth() ) return false;
		
		$client_record = self::get_current_client();
		
		if ( $client_record['CLIENT_TYPE'] )
			$this -> tpl -> assign( 'is_legal_person', 1 );
		
		$is_openid_person = params::$params['openid_mode']['value'] && $client_record['OPENID'];
		if ( $is_openid_person )
			$this -> tpl -> assign( 'is_openid_person', 1 );
		
		if ( $this -> q_param['is_updated'] )
			$this -> tpl -> assign( 'is_updated', 1 );
		unset( $this -> q_param['is_updated'] );
		
		if ( !count( $this -> q_param ) )
			$this -> tpl -> assign( lib::array_htmlspecialchars( $client_record ) );
		
		if ( !$this -> is_correct_client_data( $client_record['CL_CLIENT_ID'], $is_openid_person ) )
			return false;
		
		if ( $this -> q_param['PASSWORD'] && !$is_openid_person )
			$password_md5 = md5( $this -> q_param['PASSWORD'] );
		else
			$password_md5 = $client_record['PASSWORD_MD5'];
		
		db::update_record( 'CL_CLIENT', array(
			'SURNAME' => $this -> q_param['SURNAME'],
			'NAME' => $this -> q_param['NAME'],
			'PATRONYMIC' => $this -> q_param['PATRONYMIC'],
			'EMAIL' => $this -> q_param['EMAIL'],
			'PASSWORD_MD5' => $password_md5,
			'LEGAL_PERSON' => $this -> q_param['LEGAL_PERSON'],
			'ADDRESS' => $this -> q_param['ADDRESS'],
			'INN' => $this -> q_param['INN'],
			'KPP' => $this -> q_param['KPP'],
			'R_ACCOUNT' => $this -> q_param['R_ACCOUNT'],
			'BANK_NAME' => $this -> q_param['BANK_NAME'],
			'K_ACCOUNT' => $this -> q_param['K_ACCOUNT'],
			'BIK' => $this -> q_param['BIK'],
			'CODE_OKPO' => $this -> q_param['CODE_OKPO'],
			'CODE_OKVED' => $this -> q_param['CODE_OKVED'],
			'TELEPHONE' => $this -> q_param['TELEPHONE'],
			'FAX' => $this -> q_param['FAX'] ), '',
			array( 'CL_CLIENT_ID' => $client_record['CL_CLIENT_ID'] ) );
		
		// @todo Синхронизация и клонирование администраторов не должна работать работать в случае,
		// @todo если клиентская и административная часть работают на разных серверах.
		// @todo Пока эта возможность не реализована, в коде на соответствующих местах расставлены заглушки
		if ( true )
		{
			// Выясняем наличие связанной записи в таблице "Администраторы"
			$is_admin_link = count( db::sql_select( 'select * from AUTH_USER where CL_CLIENT_ID = :client_id',
				array( 'client_id' => $client_record['CL_CLIENT_ID'] ) ) ) > 0;
			
			if ( $is_admin_link )
			{
				// Обновление связанной записи в таблице "Администраторы"
				db::update_record( 'AUTH_USER', array(
					'SURNAME' => $this -> q_param['SURNAME'],
					'NAME' => $this -> q_param['NAME'],
					'PATRONYMIC' => $this -> q_param['PATRONYMIC'],
					'EMAIL' => $this -> q_param['EMAIL'],
					'PASSWORD_MD5' => $password_md5 ), '',
					array( 'CL_CLIENT_ID' => $client_record['CL_CLIENT_ID'] ) );
			}
		}
		
		$client_key = self::get_client_key( $client_record['CL_CLIENT_ID'],
			$is_openid_person ? $client_record['OPENID'] : $this -> q_param['EMAIL'], $password_md5 );
		
		self::set_cookie_to_client( $client_record['CL_CLIENT_ID'], $client_key );
		
		header( 'Location: index.php?is_updated_' . $this -> env['area_id'] . '=1' );
	}
	
	/**
	 * Вариант использования "Смена пароля"
	 */
	protected function mode_reminder()
	{
		$errors = array();
		
		if ( $this -> q_param['EMAIL'] ) // Отправка письма со ссылкой
		{
			$client_record = db::sql_select( 'select * from CL_CLIENT where EMAIL = :email',
				array( 'email' => $this -> q_param['EMAIL'] ) );
			
			if ( count( $client_record ) > 0 && !( params::$params['openid_mode']['value'] && $client_record[0]['OPENID'] ) )
			{
				// Инициализация шаблонизатора
				$reminder_tpl = new smarty_ee_module( $this );
				
				$reminder_tpl -> assign( lib::array_htmlspecialchars( $client_record[0] ) );
				$reminder_tpl -> assign( 'HTTP_HOST', $_SERVER['HTTP_HOST'] );
				
				$client_key = self::get_client_key( $client_record[0]['CL_CLIENT_ID'], $client_record[0]['EMAIL'], $client_record[0]['PASSWORD_MD5'] );
				
				$script_name = 'http' . ( $_SERVER['HTTPS'] == 'on' ? 's' : '' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
				
				$reminder_tpl -> assign( 'reminder_url', lib::make_request_uri( array(
					'client_id_' . $this -> env['area_id'] => $client_record[0]['CL_CLIENT_ID'],
					'client_key_' . $this -> env['area_id'] => $client_key ), $script_name ) );
				
				$reminder_message = $reminder_tpl -> fetch( $this -> tpl_dir . 'reminder_notice.tpl' );
				
				// Отправка письма пользователю
				lib::post_mail( $this -> q_param['EMAIL'], "{$client_record[0]['SURNAME']} {$client_record[0]['NAME']} {$client_record[0]['PATRONYMIC']}",
					'admin@' . $_SERVER['SERVER_NAME'], $this -> lang['sysw_client_reminder_from'] . ' ' . $_SERVER['HTTP_HOST'],
						$this -> lang['sysw_client_reminder_subj'] . ' ' . $_SERVER['HTTP_HOST'], $reminder_message );
				
				$this -> tpl -> assign( 'is_reminded', 1 );
			}
			else
				$errors[] = $this -> lang['sysw_client_error8'];
		}
		else if ( $this -> q_param['client_id'] ) // Форма ввода пароля
		{
			$client_record = db::sql_select( 'select * from CL_CLIENT where CL_CLIENT_ID = :client_id',
				array( 'client_id' => $this -> q_param['client_id'] ) );
			
			if ( count( $client_record ) > 0 && !( params::$params['openid_mode']['value'] && $client_record[0]['OPENID'] ) )
			{
				$client_key = self::get_client_key( $client_record[0]['CL_CLIENT_ID'], $client_record[0]['EMAIL'], $client_record[0]['PASSWORD_MD5'] );
				
				if ( $client_key === $this -> q_param['client_key'] )
				{
					$this -> tpl -> assign( 'is_change_form', 1 );
					
					if ( $this -> q_param['PASSWORD'] )
					{
						if ( $this -> q_param['PASSWORD'] == $this -> q_param['PASSWORD2'] )
						{
							$password_md5 = md5( $this -> q_param['PASSWORD'] );
							
							// Сохранение нового пароля
							db::update_record( 'CL_CLIENT', array( 'PASSWORD_MD5' => $password_md5 ), '',
								array( 'CL_CLIENT_ID' => $client_record[0]['CL_CLIENT_ID'] ) );
							
							if ( true )
							{
								// Выясняем наличие связанной записи в таблице "Администраторы"
								$is_admin_link = count( db::sql_select( 'select * from AUTH_USER where CL_CLIENT_ID = :client_id',
									array( 'client_id' => $client_record[0]['CL_CLIENT_ID'] ) ) ) > 0;
								
								if ( $is_admin_link )
								{
									// Обновление связанной записи в таблице "Администраторы"
									db::update_record( 'AUTH_USER', array( 'PASSWORD_MD5' => $password_md5 ), '',
										array( 'CL_CLIENT_ID' => $client_record[0]['CL_CLIENT_ID'] ) );
								}
							}
							
							$this -> tpl -> assign( 'is_updated', 1 );
						}
						else
							$errors[] = $this -> lang['sysw_client_error6'];
					}
				}
				else
					$errors[] = $this -> lang['sysw_client_error11'];
			}
			else
				$errors[] = $this -> lang['sysw_client_error11'];
		}
		
		// Выводим ошибки в шаблон
		if ( count( $errors ) )
		{
			$this -> tpl -> assign( 'errors', $errors );
		}
	}
	
	/**
	 * Проверка корректности пользовательских данных
	 */
	protected function is_correct_client_data( $client_id = '', $is_openid_person = false )
	{
		$errors = array();
		
		if ( !count( $this -> q_param ) ) 
		{
			$this -> tpl -> assign( 'captcha_id', captcha::generate() );
			return false;
		}
		
		$this -> tpl -> assign( lib::array_htmlspecialchars( $this -> q_param ) );
		
		// Если регистрируется юридическое лицо
		if ( $this -> q_param['CLIENT_TYPE'] )
		{
			$this -> tpl -> assign( 'is_legal_person', 1 );
			
			if ( !$this -> q_param['LEGAL_PERSON'] )
			{
				$this -> tpl -> assign( 'captcha_id', captcha::generate() );
				return false;
			}
		}
		
		// Проверяется заполнение обязательных полей
		if ( $this -> q_param['SURNAME'] === '' && !$is_openid_person )
			$errors[] = $this -> lang['sysw_client_nonempty'] . ': "' . $this -> lang['sysw_client_surname'] . '"!';
		if ( $this -> q_param['EMAIL'] === '' && !$is_openid_person )
			$errors[] = $this -> lang['sysw_client_nonempty'] . ': "' . $this -> lang['sysw_client_email'] . '"!';
		
		if ( $this -> view_param['view_mode'] == 'registration' )
		{
			if ( $this -> q_param['PASSWORD'] === '' )
				$errors[] = $this -> lang['sysw_client_nonempty'] . ': "' . $this -> lang['sysw_client_password'] . '"!';
			if ( $this -> q_param['PASSWORD2'] === '' )
				$errors[] = $this -> lang['sysw_client_nonempty'] . ': "' . $this -> lang['sysw_client_password2'] . '"!';
		}
		
		if ( $this -> q_param['CLIENT_TYPE'] )
		{
			if ( $this -> q_param['LEGAL_PERSON'] === '' )
				$errors[] = $this -> lang['sysw_client_nonempty'] . ': "' . $this -> lang['sysw_client_organization'] . '"!';
			if ( $this -> q_param['ADDRESS'] === '' )
				$errors[] = $this -> lang['sysw_client_nonempty'] . ': "' . $this -> lang['sysw_client_legal_address'] . '"!';
			if ( $this -> q_param['INN'] === '' )
				$errors[] = $this -> lang['sysw_client_nonempty'] . ': "' . $this -> lang['sysw_client_inn'] . '"!';
			if ( $this -> q_param['KPP'] === '' )
				$errors[] = $this -> lang['sysw_client_nonempty'] . ': "' . $this -> lang['sysw_client_kpp'] . '"!';
			if ( $this -> q_param['R_ACCOUNT'] === '' )
				$errors[] = $this -> lang['sysw_client_nonempty'] . ': "' . $this -> lang['sysw_client_settlement_account'] . '"!';
			if ( $this -> q_param['BANK_NAME'] === '' )
				$errors[] = $this -> lang['sysw_client_nonempty'] . ': "' . $this -> lang['sysw_client_bank'] . '"!';
			if ( $this -> q_param['K_ACCOUNT'] === '' )
				$errors[] = $this -> lang['sysw_client_nonempty'] . ': "' . $this -> lang['sysw_client_correspondent_account'] . '"!';
			if ( $this -> q_param['BIK'] === '' )
				$errors[] = $this -> lang['sysw_client_nonempty'] . ': "' . $this -> lang['sysw_client_bik'] . '"!';
			if ( $this -> q_param['CODE_OKPO'] === '' )
				$errors[] = $this -> lang['sysw_client_nonempty'] . ': "' . $this -> lang['sysw_client_okpo'] . '"!';
			if ( $this -> q_param['CODE_OKVED'] === '' )
				$errors[] = $this -> lang['sysw_client_nonempty'] . ': "' . $this -> lang['sysw_client_okved'] . '"!';
		}
		
		// Проверяется уникальность логина пользователя (email)
		if ( count( db::sql_select( 'select * from CL_CLIENT where EMAIL = :email and EMAIL <> \'\' and EMAIL is not null and CL_CLIENT_ID <> :client_id',
				array( 'email' => $this -> q_param['EMAIL'], 'client_id' => $client_id ) ) ) )
			$errors[] = $this -> lang['sysw_client_error5'];
		
		// Проверяется правильность введенных паролей
		if ( $this -> q_param['PASSWORD'] != $this -> q_param['PASSWORD2'] )
			$errors[] = $this -> lang['sysw_client_error6'];
		
		// Проверяется совпадение введенной фразы коду на изображении
		if ( $this -> view_param['view_mode'] == 'registration' &&
				!captcha::check( $this -> q_param['captcha_id'], $this -> q_param['captcha_value'] ) )
			$errors[] = $this -> lang['sysw_captcha_error'];
		
		// Выводим ошибки в шаблон
		if ( count( $errors ) )
		{
			$this -> tpl -> assign( 'errors', $errors );
			$this -> tpl -> assign( 'captcha_id', captcha::generate() );
			return false;
		}
		
		return true;
	}
	
	/**
	 * Оключаем кэширование
	 */
	protected function get_hash_code()
	{
		return false;
	}
	
	///////////////////////////////// Методы для работы с OpenID ///////////////////////////////////
	
	/**
	 * Возвращает объект потребителя OpenID
	 */
	protected function getConsumer()
	{
		$pear_path = params::$params['common_data_server']['value'] . 'lib/pear/';
		set_include_path( $pear_path . PATH_SEPARATOR. get_include_path() );
		
		include_once 'Auth/OpenID/Consumer.php';
		include_once 'Auth/OpenID/FileStore.php';
		
	    $store_path = params::$params['common_data_server']['value'] . 'block_cache/_openid';
		
	    $store = new Auth_OpenID_FileStore( $store_path );
	    $consumer = new Auth_OpenID_Consumer( $store );
		
	    return $consumer;
	}
	
	/**
	 * Возвращает протокол текущего соединения
	 */
	protected function getScheme()
	{
		return 'http' . ( $_SERVER['HTTPS'] == 'on' ? 's' : '' );
	}

	/**
	 * Возвращает URL возврата пользователя после проверки подлинности
	 */
	protected function getReturnTo()
	{
		return sprintf( '%s://%s:%s%s',
			$this -> getScheme(), $_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT'], lib::make_request_uri( array() ) );
	}

	/**
	 * Возвращает URL для проверки доверия
	 */
	protected function getTrustRoot()
	{
		return sprintf( '%s://%s:%s/',
			$this -> getScheme(), $_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT'] );
	}	
}
?>
