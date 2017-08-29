<?php
/**
 * Модуль "Административная часть"
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2007 RBC SOFT
 */
class m_admin_system extends module
{
	/**
	 * Объект шаблона модуля
	 * @var object
	 */
	protected $tpl;

	/**
	 * Объект интерфейса
	 * @var object
	 */
	protected $object_interface;

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Диспетчер модуля
	 */
	protected function content_init()
	{
		// Инициализация шаблонизатора
		$this -> tpl = new smarty_ee_module( $this );
		
		// Устанавливаем признак работы административной части в режиме клиентского модуля
		params::$params['client_mode']['value'] = true;
		
		include_once( params::$params['adm_data_server']['value'] . 'class/core/object/object.php' );
		
		// Модуль не должен работать в случае, если клиентская и административная часть работают на разных серверах
		// @todo Пока эта возможность не реализована, следовательно нет способа проверить это условие
		if ( false )
			throw new Exception( metadata::$lang["Сообщение о невозможности работы административной части"] );
		
		// Создаем объект интерфейса
		$this -> object_interface = object::factory( '_object_interface' );
		
		try
		{
			if ( auth::is_auth() )
			{
				if( $_REQUEST['logout'] )
					auth::logout();
				
				$this -> object_interface -> object = object::factory();
				
				// Только для главной области есть смысл вызывать диспетчер
				if ( $this -> view_param['view_mode'] == 'content' )
				{
					// Устанавливаем признак раскрытости фильтра в главной области
					params::$params['filter_expand']['value'] = $this -> view_param['filter_expand'] == 'yes';
					
					// Переопределяем признака доступности системного раздела "Настройки/Администраторский интерфейс"
					params::$params['params_access']['value'] = $this -> view_param['params_access'] == 'yes';
					
					$this -> object_interface -> object -> dispatcher();
					
					$content = $this -> object_interface -> object -> get_body();
				}
			}
			else
			{
				if ( $_SERVER['QUERY_STRING'] )
					$_SESSION['back_url'] = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'];
				
				// Форма авторизации должна выводиться только в главной области
				if ( $this -> view_param['view_mode'] == 'content' )
					$content = $this -> object_interface -> html_login_form();
			}
		}
		catch ( Exception $e )
		{
			// Сообщение об ошибке должно выводиться только в главной области
			if ( $this -> view_param['view_mode'] == 'content' )
			{
				$this -> tpl -> assign( 'lang_user_error_message', metadata::$lang['lang_user_error_message'] );
				
				$this -> tpl -> assign( 'file', $e -> getFile() );
				$this -> tpl -> assign( 'line', $e -> getLine );
				$this -> tpl -> assign( 'msg', $e -> getMessage() );
				
				$this -> object_interface -> title = metadata::$lang['lang_error_during_operation'];
				
				$content = $this -> tpl -> fetch( params::$params['adm_data_server']['value'] . 'tpl/core/object/html_error.tpl' );
			}
			
			// Регистрируем ошибку, используя обработчик, описанный в классе object_interfacе
			trigger_error( object_interface::get_exception_serialized( $e ), E_USER_ERROR );
		}
		
		if ( auth::is_auth() )
		{
			if ( !$this -> object_interface -> object )
				$this -> object_interface -> object = $this -> object_interface;
			
			// Области, отличные от главной должны заполняться даже в случае ошибки
			if ( $this -> view_param['view_mode'] == 'user_panel' )
				$content = $this -> object_interface -> html_user_panel();
			else if ( $this -> view_param['view_mode'] == 'head_menu' )
				$content = $this -> object_interface -> html_head_menu( $this -> object_interface -> object -> section_type );
			else if ( $this -> view_param['view_mode'] == 'system_menu' &&
					$this -> object_interface -> object -> is_menu() )
				$content = $this -> object_interface -> object -> get_system_menu();
			
			if ( $e )
				$this -> object_interface -> object = null;
		}

		// Выводим в шаблон контент модуля и информацию о языке интерфейса
		$this -> tpl -> assign( 'content', $content );
		$this -> tpl -> assign( 'interface_lang', params::$params['default_interface_lang']['value'] );
		
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir . 'admin_system.tpl' );
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Возвращает заголовок страницы
	 */
	public function get_title(){
		return $this -> object_interface -> get_title( false );
	}

	/**
	 * Возвращает ключевые слова страницы
	 */
	public function get_keywords(){
		return $this -> object_interface -> get_keywords();
	}

	/**
	 * Возвращает описание страницы
	 */
	public function get_description(){
		return $this -> object_interface -> get_description();
	}

	/**
	 * Оключаем кэширование
	 */
	protected function get_hash_code()
	{
		return false;
	}
}
?>
