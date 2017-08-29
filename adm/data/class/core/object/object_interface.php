<?php
/**
 * Класс содержащий методы для формирования общих компонентов интерфейса - меню, фильтр, общий костяк и т.д.
 *
 * @package    RBC_Contents_5_0
 * @subpackage core
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class object_interface extends object{
	
	public $object;
	
	/**
	* Конструктор
	 * @param string $obj			Название конструируемого объекта
	 * @param string $full_object	Ссылка на полный объект, если ее нет, то в качестве такой ссылки используется сам конструируемый объект
	 * @param object $object	Объект для работы
	*/
	function __construct($obj, $full_object="", $object=null) {
		parent::__construct($obj, $full_object);

		$this->object = $object;
		$this->tpl = new smarty_ee(metadata::$lang);
		set_error_handler(array(&$this, 'php_error_handler'));
	}

	/**
	 * Выводит готовую страницу административного интерфейса
	 * 
	 * @param object $object - объект, обрабатываемый в настоящий момент
	 */
	
	public function print_common($object=null){
		global $bench;//
		try {
			if(auth::is_auth()){
				// Логаут, если надо
				if($_REQUEST["logout"]){
					auth::logout();
				}
				// Меняем язык, если надо
				if($_REQUEST['lang'])
					$this -> change_lang( $_REQUEST['lang'] );
			
$bench->register(bench::bencher("all_parts"), "head", 1);//
				

				if (!$object) {
					$object=object::factory();
$bench->register(bench::bencher("all_parts"), "object factory", 1);//
				}
				if (!$object) return;

				$this->object = $object;

				// Контент объекта
				$this->object->dispatcher();
				
$bench->register(bench::bencher("all_parts"), "content", 1);//
			}
			else {
				if ($_SERVER["QUERY_STRING"])
					$_SESSION["back_url"]=$_SERVER["SCRIPT_NAME"]."?".$_SERVER["QUERY_STRING"];			
			}
			
			$this->set_template_parameters();
			
			echo $this->tpl->fetch($this->tpl_dir."core/object/html_common.tpl");
			
$bench->register(bench::bencher("all_parts"), "common template", 1);//
		}
		catch (Exception $e) {
				trigger_error(self::get_exception_serialized($e), E_USER_ERROR);
		}
	}
	
	/**
	* Возвращает информацию об исключении в сериализованном виде
	* @param object Exception $e Исключение
	* @return string
	*/
	
	public static function get_exception_serialized($e) {
		return serialize(
					array(
						'FILE'=>$e->getFile(),
						'LINE'=>$e->getLine(),
						'MSG'=>$e->getMessage(),
						'TRACE'=> bench::get_trace_as_string(bench::filter_trace($e->getTrace())),
						'DEBUG_MESSAGE'=>(method_exists($e, 'getDebugMessage'))?$e->getDebugMessage():''
					)
				);
	}
	
	/**
	* Устанавливает параметры главного шаблона
	* @todo Если произошла  ошибка здесь, то выдается пустая страница пользователю и ничего не регистрируется в журнале ошибок
	* @todo При большом числе записей метод приводит к заметным тормозам на стадии "common template" (bugs 230). Причина в медленной работе метода fetch с большими объемами данных ( > 200 кБ ). Скорее всего с этим ничего не поделаешь. 
	*/
	
	private function set_template_parameters () {
global $bench;//
		if(auth::is_auth()){
			$this->tpl->assign("title", $this->get_title());			
			if ( $this->object ){
				$this->tpl->assign("status_title", $this->object->get_title());
				$this->tpl->assign( "favourite_title", $this->object->get_title() );
			} else {
				$this->object = $this;
			}
		
			try {
				// Шапка
				$this->tpl->assign( "head_menu", $this->html_head_menu($this -> object -> section_type) );
				$this->tpl->assign( "user_panel", $this->html_user_panel() );
				
				// Меню и стратусная строка
				if( $this->object->is_menu() )
					$this->tpl->assign( "system_menu", $this->object->get_system_menu() );
				
				$this->object->path_id_array[]=array('TITLE'=>$this->object->section_title, 'URL'=>$this->object->section_url);
				
				$this->tpl->assign("system_path", array_reverse($this->object->path_id_array));

$bench->register(bench::bencher("all_parts"), "menu", 1);//

				$this->tpl->assign( "favourite_list", $this->html_favourite_list() );
				$this->tpl->assign( "favourite_url", $_SERVER["REQUEST_URI"] );
				
				$this->tpl->assign( "context_help", $this->html_context_help() );
				
				$this->tpl->assign('is_debug', params::$params['debug_mode']['value']);
				
				$this->tpl->assign("body", $this->object->get_body());
			}
			catch (Exception $e) {echo '<PRE>'; print_r($e); }
		}
		else {
			// При попытке обратиться web-сервису, неавторизованный пользователь получает 401 ошибку
			if ($_REQUEST["action"]=="service") {
				header("HTTP/1.0 401 Unauthorized"); exit;
			}
			
			$this->tpl->assign("title", $this->get_title());
			$this->tpl->assign("body", $this->html_login_form());
		}
		
		$this->tpl->assign("is_auth", auth::is_auth());
		$this->tpl->assign("encoding", params::$params["encoding"]["value"]);
		$this->tpl->assign("interface_lang", params::$params["default_interface_lang"]["value"]);
		
		// Извлекаем из куков выбранный размер шрифта интерфейса
		$font_size = in_array( $_COOKIE['font_size'], array( 'small', 'middle', 'big' ) ) ?
			$_COOKIE['font_size'] : params::$params['default_font_size']['value'];
		
		// Если необходимо, сохраняем его в параметрах пользователя
		if ( auth::is_auth() && $font_size != params::$params['default_font_size']['value'] )
			object::factory( 'SYSTEM_AUTH_USER_PARAMS_TOOL' ) -> set_parameter_new_value( 'default_font_size', $font_size );
		
		// Выдаем размер шрифта интерфейса в шаблон
		$this -> tpl -> assign( 'font_size', $font_size );
		
		// С CMS система или нет (выводить ли ссылку на тулбар)
		$this -> tpl -> assign( 'is_cms', params::$params['install_cms']['value'] );
	}
	/**
	 * Возвращает заголовок страницы
	 * 
	 * @param boolean $with_project_name - включать в заголовок название проекта
	 */
	public function get_title( $with_project_name = true )
	{
		$title_list = $with_project_name ? array( params::$params['project_name']['value'] ) : array();
		
		if ( auth::is_auth() )
		{
			if ( $this -> object ) {
				if ( $this -> object -> get_title() != metadata::$objects[ $this -> object -> get_object_name()]['title'])
					$title_list[] = metadata::$objects[$this -> object -> get_object_name()]['title'];
			
				if ( $this -> object -> get_title() ) 
					$title_list[] = $this -> object -> get_title();
			}
		}
		else
		{
			$title_list[] = metadata::$lang['lang_authentication'];
		}
		
		
		return join( params::$params['title_separator']['value'], $title_list );
	}
	
	/**
	 * Формирует список языков
	 */
	protected function html_lang_list()
	{
		$langs = db::sql_select( '
				select LANG_ID, ROOT_DIR, ORIGINAL_NAME from LANG
				where IN_ADMIN = :in_admin
				order by CASE WHEN LANG_ID = :lang_id THEN 1 ELSE 0 END desc, ROOT_DIR',
			array( 'lang_id' => $this -> get_interface_lang(), 'in_admin' => 1 ) );
		
		$lang_list = array();
		foreach ( $langs as $lang )
		{
			$lang_flag_name = file_exists( params::$params["common_htdocs_server"]["value"] . 'adm/img/lang/' . $lang['ROOT_DIR'] . '.gif' ) ? $lang['ROOT_DIR'] : 'default';
			$lang_list[] = "{ 'title': '" . mb_substr( $lang['ORIGINAL_NAME'], 0, 3, params::$params["encoding"]["value"] ) .  "', 'alt': '" . $lang['ORIGINAL_NAME'] .  "', 'image': '/common/adm/img/lang/{$lang_flag_name}.gif'".( $lang['ROOT_DIR'] != params::$params["default_interface_lang"]["value"] ? ", 'object': window, 'method': 'redirect_obj', 'param': { 'url': '" . lib::make_request_uri( array( 'lang' => $lang['LANG_ID'] ) ) . "' }" : "") . " }";
		}
		
		return join( ',', $lang_list );
	}
	
	/**
	 * Формирует список избранного
	 */
	protected function html_favourite_list()
	{
		$favourite_list = db::sql_select( '
				select * from FAVOURITE
				where AUTH_USER_ID = :auth_user_id
				order by FAVOURITE_ORDER',
			array( 'auth_user_id' => $_SESSION['AUTH_USER_ID'] ) );
		return $favourite_list;
	}
	
	/**
	 * Возвращает содержимое контекстной помощи
	 */
	protected function html_context_help()
	{
		$context_help_obj = object::factory( 'CONTEXT_HELP' );
		list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
			$context_help_obj -> ext_field_selection( 'BODY', 1 );
		$context_help_obj -> __destruct();
		
		$context_help = db::replace_field(db::sql_select( '
			select
				CONTEXT_HELP.*, ' . $dec_field . ' as "_BODY"
			from
				CONTEXT_HELP ' . $dec_join[0] . '
					inner join TE_OBJECT on
						TE_OBJECT.TE_OBJECT_ID = CONTEXT_HELP.TE_OBJECT_ID and
						TE_OBJECT.SYSTEM_NAME = :object_name
			where
				CONTEXT_HELP.OBJECT_ACTION = :object_action
			order by
				CONTEXT_HELP.OBJECT_PARAM desc',
			array(
				'object_name' => $this -> object -> get_object_name(),
				'object_action' => $_REQUEST['action'] ? $_REQUEST['action'] : 'index' ) +  $dec_binds ), 'BODY', '_BODY');
		
		if ( !count( $context_help ) )
			return '';
		
		$request = explode( '&', http_build_query( $_GET ) );
		
		foreach ( $context_help as $context_item )
		{
			$object_params = $context_item['OBJECT_PARAM'] !== '' ? explode( '&', $context_item['OBJECT_PARAM'] ) : array();
			if ( count( $object_params ) == 0 || ( count( array_intersect( $object_params, $request ) ) == count( $object_params ) ) )
			{
				$context_body = $context_item['BODY']; break;
			}
		}
		
		return $context_body;
	}
	
	/**
	 * Формирует html-код шапки админки
	 */
	public function html_head_menu( $section_type )
	{
		$head_menu = array();
		
		if (params::$params['install_cms']['value']) {
			$head_menu[] = array( 'TITLE' => metadata::$lang['lang_page_table'], 'URL' => 'index.php?obj=PAGE', 'SELECTED' => $section_type == 'page', 'IMG' => 'page.gif' );
			
			$head_menu[] = array( 'TITLE' => metadata::$lang['lang_content'],
				'URL' => 'index.php?obj=AUTH_SYSTEM_SECTION_TOOL&SECTION_TYPE=content', 'SELECTED' => $section_type == 'content', 'IMG' => 'content.gif' );
		}
		
		$head_menu[] = array( 'TITLE' => metadata::$lang['lang_settings'],
			'URL' => 'index.php?obj=AUTH_SYSTEM_SECTION_TOOL&SECTION_TYPE=settings', 'SELECTED' => $section_type == 'settings', 'IMG' => 'settings.gif' );
		$head_menu[] = array( 'TITLE' => metadata::$lang['lang_utility'],
			'URL' => 'index.php?obj=AUTH_SYSTEM_SECTION_TOOL&SECTION_TYPE=utility', 'SELECTED' => $section_type == 'utility', 'IMG' => 'utility.gif' );
		
		$tpl = new smarty_ee( metadata::$lang );
		$tpl -> assign( 'head_menu', $head_menu );
		
		return $tpl -> fetch( $this -> tpl_dir . 'core/object/html_head_menu.tpl' );
	}
	
	/**
	 * Формирует html-код панели пользователя
	 */
	public function html_user_panel()
	{
		$tpl = new smarty_ee( metadata::$lang );
		
		if ( !params::$params['client_mode']['value'] )
		{
			$tpl -> assign( 'menu_lang', $this -> html_lang_list() );
			$tpl -> assign( 'interface_lang', params::$params['default_interface_lang']['value'] );
		}
		else
		{
			$tpl -> assign( 'client_mode', true );
		}
		
		$tpl -> assign( 'user_name', $this -> auth -> user_info['SURNAME'] . ' ' . $this -> auth -> user_info['NAME'] );
		
		return $tpl -> fetch( $this -> tpl_dir . 'core/object/html_user_panel.tpl' );
	}
	
	/**
	 * Формирует html-код формы авториазации
	 */
	public function html_login_form()
	{
		return auth::html_login_form( $this -> tpl_dir . 'core/auth/html_login_form.tpl' );
	}
	
	/**
	 * Метод меняет язык административного интерфейса
	 * 
	 * @param integer $lang	идентификатор языка
	 */
	protected function change_lang( $lang )
	{
		$user_params = object::factory( 'SYSTEM_AUTH_USER_PARAMS_TOOL' );
		$user_params -> set_parameter_new_value( 'default_interface_lang', $lang );
		header( 'Location: ' . lib::make_request_uri( array( 'lang' => '' ) ) );
		exit;
	}
	
	/**
	* Обработчик ошибки php, перекрывающий стандартный
	* @param int $errno Тип ошибки
	* @param string $errstr Текст ошибки
	* @param string $errfile Название файла с ошибкой
	* @param string $errline Строка в файле с ошибкой
	*/
	
	public function php_error_handler($errno, $errstr, $errfile, $errline) {
			// пропускаем все ошибки, которые были вызваны операторами с @
			if (!error_reporting()) return true;
			
			static $already_run=0;
			if ($errno & (E_NOTICE | E_STRICT) ) return true;
			
			if ($already_run) return true;
			
			$already_run=1;
			
			$trace = '';
			
			if ($errno==E_USER_ERROR) {
				// вышли по Exception-у
				if ($error_info=@unserialize($errstr)) {
					$errstr=$error_info['MSG'];
					$errfile=$error_info['FILE'];
					$errline=$error_info['LINE'];
					$trace = $error_info['TRACE'];
					$debug=$error_info['DEBUG_MESSAGE'];					
				}
			}
			
			if (!$trace)
				// если нет трейса - формируем его из debug_backtrace. 3 чтобы пропустить последние ф-ии, которые регистрировали ошибку.
				$trace = bench::get_trace_as_string (bench::filter_trace( bench::get_full_trace(3) ));

			$this->log_register_error($errstr, $errfile, $errline, $debug, $trace);
			
			if ( !params::$params["client_mode"]["value"] )
			{
				$this->show_error($errstr, $errfile, $errline, $debug, $trace);
				exit;
			}

	}
	
	/**
	* Показывает сообщение об ошибке
	* @param string $errstr Текст ошибки
	* @param string $errfile Файл, в котором произошла ошибка
	* @param string $errline Строка, в которой произошла ошибка
	* @param string $debug Дебаггерная информация
	* @param string $trace Трейс
	*/
	
	private function show_error ($errstr, $errfile, $errline, $debug, $trace) {
		if ($_REQUEST['action']=='service') {
			// если объект является только сервисом, то нужно выдать ему xml-текст ошибки
			header( 'Content-Type: text/xml; charset=' . params::$params['encoding']['value'] );
			echo object::xml_error($errstr, $errfile, $errline, $debug, $trace);
		}
		else {
			$this->set_template_parameters();
		
			if ($title = $this->tpl->get_template_vars('title'))
				$title.=params::$params['title_separator']['value'];
			$title.=metadata::$lang["lang_error_during_operation"];
			
			$this->tpl->assign("system_path", array(array("TITLE"=>metadata::$lang["lang_error_during_operation"])));
			
			$this->tpl->assign("title", $title);
			$this->tpl->assign("status_title", metadata::$lang["lang_error_during_operation"]);
			
			$this->tpl->assign("body", object::html_error($errstr, $errfile, $errline, $debug, $trace));
			
			if ($this->auth->is_auth()) {
				echo $this->tpl->fetch($this->tpl_dir."core/object/html_common.tpl");
			}
			else {
				auth::logout();
			}
		}
	}
	
	/**
	* Функция регистрации ошибки в журнале ошибок
	*
	* @param string $msg	Текст ошибки
	* @param string $file	Файл
	* @param string $line	Строка
	* @param string $trace	трейс
	*
	*/
	public function log_register_error ($msg, $file, $line, $debug, $trace) {
		if (!log::is_enabled('log_errors')) return;
		
		$log_info = array (
			'file' => $file,
			'line' => $line,
			'msg' => $msg,
			'debug' => $debug,
			'trace' => $trace
		);
		
		log::register('log_errors', 'common_error', $log_info);
	}
}
?>
