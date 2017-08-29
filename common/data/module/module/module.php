<?php
/**
 * Файл с абстрактным классом модулей
 *
 * Помимо подключения интерфейса и класса в этом файле автоматически запускается формирование {@link module::$all_q_params}.<br>
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2006 RBC SOFT
 * @todo Организовать автоматическую передачу в шаблон текущей PAGE (Нужно ли это?)
 */

include_once(params::$params["common_data_server"]["value"]."interface/module_interface.php");
include_once(params::$params["common_data_server"]["value"]."lib/smarty/smarty_ee_module.php");

/**
 * Абстрактный класс модулей
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
abstract class module implements module_interface{

	/** 
	 * HTML-код контентной части страницы
	 * @var string
	 */
	protected $body="";

	/**
	 * Название страницы
	 * @var string
	 */
	protected $title="";

	/**
	 * Ключевые слова страницы
	 * @var string
	 */
	protected $keywords="";

	/**
	 * Описание страницы
	 * @var string
	 */
	protected $description="";

	/**
	 * Информация, которую модуль собирается передать другим модулям
	 * @var array
	 */
	protected $module_info;

	/**
	 * сведения об окружении - раздел, язык и т.д.
	 *
	 * В модуль должны быть переданы следующие значения (ключи хэша):<br>
	 * page_id, version, lang_id, lang_root_dir, site_id, area_id, block_id, cache_time, is_main (главность области),
	 * is_print (нужно ли отображать версию для печати или обычную версию)
	 * @var array
	 */
	public $env;

	/**
	 * параметры представления модуля
	 * @var array
	 */
	public $view_param;

	/**
	 * Языковые константы модуля
	 * @var array
	 */
	public $lang;

	/**
	 * параметры модуля из $_REQUEST
	 * @var array
	 */
	protected $q_param;
	
	/**
	 * Системное название модуля в нижнем регистре. Используется в абстрактном классе для выполнения общих действий для конкретного модуля
	 * @var string
	 */
	protected $module_name;

	/**
	 * Главная таблица модуля. По умолчанию равно системному названию модуля в верхнем регистре
	 * @var string
	 */
	protected $module_table;

	/**
	 * Первичный ключ модуля (автоинкрементное поле). По умолчанию равно системному названию таблицы с добавлением "_ID"
	 * @var string
	 */
	protected $module_table_pk;

	/**
	 * Абсолютный путь к директории с шаблонами модуля. Заполняется в том случае, если в модуль передан параметр представления с системным названием "template"
	 * @var string
	 */
	protected $tpl_dir="";

	/**
	 * "Ключевой" хэш декораторов (то есть array("lang"=>1, "version"=>1))
	 *
	 * Нужен для того, чтобы модуль мог автоматически перестроить запросы к БД при изменении типа хранилища данных
	 * @var array
	 * @todo Возможно стоит формировать его в конструкторе из метаданных объекта с таким же системным именем, что и модуль
	 */
	protected $decorators=array("lang"=>1, "version"=>1, "block"=>1);

	/**
	 * Позволяет ли модуль привязывать записи к нескольким блокам.
	 * @var bolean
	 */
	protected $multiple_block=false;
	
	/**
	 * Указывает конструктору запроса производить выборку записей по всем блокам текущего сайта
	 * @var bolean
	 */
	protected $any_block=false;
	
	/**
	 * параметры всех модулей из $_REQUEST. Представляет из себя массив хэшей $q_params с ключом-идентификатором области, а также список "ничейных" параметров с ключом 0
	 * @var array
	 */
	static protected $all_q_param;
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Конструктор. Заполняет $this->module_name
	 *
	 * $param string $module_name	системное название модуля в нижнем регистре
	 */
	protected function __construct($module_name){
		$this->module_name=$module_name;
		$this->module_table=strtoupper($module_name);
		$this->module_table_pk=$this->module_table."_ID";
	}

	/**
	 * Создает экземпляр модуля
	 *
	 * @param string $obj	системное название модуля. Здесь оно обязательно. Возможность быть пустым сделана для совместимости с фабрикой объектов административной системы. Приходит в верхнем регистре, далее используется в нижнем.
	 * @return object module
	 * @todo вывод сообщения сделать мультиязычным
	 */
	public static final function factory($obj=""){
		$module_name=strtolower($obj);
		$class_name="m_".$module_name;
		$file_name=params::$params["common_data_server"]["value"]."module/{$module_name}/{$class_name}.php";
		if(file_exists($file_name)){
			include_once($file_name);
			return new $class_name($module_name);
		}else{
			echo "Module not found: {$module_name} ({$file_name})";
			exit();
		}
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Формирует $all_q_param. Выполняется автоматически при подсоединении (include) этого файла
	 */
	static public final function make_q_param(){
		$all_params=array_merge($_GET, $_POST);
		foreach($all_params as $key=>$value){
			if(preg_match("/(.+)_(\d+)$/", $key, $matches)){
				self::$all_q_param[$matches[2]][$matches[1]]=$value;
			}else{
				self::$all_q_param[0][$key]=$value;
			}
		}
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Запуск работы модуля
	 *
	 * Помещает переданные данные в свойства объекта модуля и запускает собственно модуль
	 * 
	 * @see module_interface::init()
	 * @todo Понаблюдать, не будет ли include (в противовес include_once) системных слов снижать производительность
	 */
	public final function init($env, $view_param, &$module_info){
		// Сбор свойств
		$this->env=$env;
		$this->view_param=$view_param;
		$this->module_info=&$module_info;
		if(!is_array(self::$all_q_param[$this->env["area_id"]])){
			self::$all_q_param[$this->env["area_id"]]=array();
		}
		$this->q_param=&self::$all_q_param[$this->env["area_id"]];
		if($this->view_param["template"]){
			$this->tpl_dir=params::$params["common_data_server"]["value"]."module_tpl/{$this->module_name}/{$this->view_param["template"]}/";
		}
		// Подключение сисвордов
		$module_syswords_var="module_syswords_".$this->env["lang_root_dir"];
		include(params::$params["common_data_server"]["value"]."prebuild/{$module_syswords_var}.php");
		$this->lang=$$module_syswords_var;
		// Проверка кэша
		if($cache_name=$this->get_hash_code()){
			$cachemtime=@filemtime(params::$params["common_data_server"]["value"]."block_cache/block".$this->env["block_id"]."/".$cache_name);
		}
		// Запуск собственно модуля или выемка кэша
		if(!$cachemtime || time()-$cachemtime>max(0,$this->env["cache_time"])){
			$this->content_init();
			if($cache_name){
				$this->make_cache($cache_name);
			}
		}else{
			$this->get_cache($cache_name);
		}
		// Завершение инициализации модуля
		$this->complete_init();
	}

	/**
	 * Вычисляет хэш параметров модуля, как уникальное имя кэша
	 *
	 * Если возвращает false, то это значит, что кэширование отключено (например, в тестовой версии оно всегда отключено).
	 *
	 * Раньше из переменных окружения в хэш включались только block_id, lang_id, is_print. Сейчас туда добавлены site_id, is_main,
	 * так как эти параметры также могут влиять на работу модуля. page_id не добавлено в целях повышения производительности - об
	 * этом параметре модуль должен позаботиться сам (с помощью {@link ext_get_hash_code()}), если ему это надо.
	 * @todo нужно придумать как в системе кэширования учитывать $this->module_info
	 * @return mixed;
	 */
	protected function get_hash_code(){
		if($this->env["version"]==1 || max(0,$this->env["cache_time"])==0){
			return false;
		}
		$all_params="[env]|".$this->env["block_id"]."|".$this->env["lang_id"]."|".(int)$this->env["is_print"]."|".$this->env["site_id"]."|".$this->env["is_main"];
		$all_params.="|[view_param]|".join("|", array_keys($this->view_param))."|".join("|", $this->view_param);
		$all_params.="|[q_param]|".join("|", array_keys($this->q_param))."|".join("|", $this->q_param);
		$all_params.="|[ext]|".$this->ext_get_hash_code();
		return md5($all_params).".txt";
	}

	/**
	 * Позволяет дополнить вычисление хэша параметров новыми переменными, например page_id, как это в модуле "меню" сделано
	 * @return string
	 */
	protected function ext_get_hash_code(){
		return "";
	}

	/**
	 * Укладка кэша блока на файловую систему
	 */
	private final function make_cache($filename){
		$dir=params::$params["common_data_server"]["value"]."block_cache/block".$this->env["block_id"]."/";
		$content=serialize(array($this->title, $this->body, $this->keywords, $this->description));
		@mkdir($dir, 0777);
		@file_put_contents($dir.$filename, $content);
		@chmod($dir.$filename, 0777);
	}

	/**
	 * Забор кэша с файловой системы и внедрение его в инстанс
	 */
	function get_cache($filename){
		list($this->title, $this->body, $this->keywords, $this->description)=unserialize(file_get_contents(params::$params["common_data_server"]["value"]."block_cache/block".$this->env["block_id"]."/".$filename));
	}
	
	/**
	 * Очистка кэша блока
	 *
	 * @param int $block_id	идентификатор блока 
	 */
	function clear_cache( $block_id = '' )
	{
		filesystem::rm_r( params::$params['common_data_server']['value'] . 'block_cache/block' .
			( $block_id ? $block_id : $this -> env['block_id'] ) );
	}
	
	/**
	 * Завершение инициализации модуля
	 */
	function complete_init()
	{
		//
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Возвращает кляузу FROM для запросов по главной таблице модуля, опираясь на $this->decorators
	 *
	 * @return string
	 */
	protected function get_module_from(){
		$from_arr = array( $this -> module_table );
		if( $this -> decorators['block'] )
			$from_arr[] = "CONTENT_MAP";
		if( $this -> multiple_block )
			$from_arr[] = "INF_BLOCK, PRG_MODULE, CONTENT_MAP as CM2, INF_BLOCK as IF2";
		return join( ', ', $from_arr );
	}

	/**
	 * Возвращает кляузу WHERE для запросов по главной таблице модуля, опираясь на $this->decorators
	 *
	 * @return string
	 */
	protected function get_module_where()
	{
		$where_arr = array();
		if ( $this -> decorators['block'] )
		{
			if ( $this -> any_block )
			{
				$where_arr[] = "
					CONTENT_MAP.INF_BLOCK_ID IN (
						SELECT INF_BLOCK.INF_BLOCK_ID
						FROM PAGE_AREA, INF_BLOCK, PRG_MODULE, PAGE as P1, PAGE as P2
						WHERE
							PAGE_AREA.PAGE_ID = P1.PAGE_ID and PAGE_AREA.VERSION = P1.VERSION and
							P1.SITE_ID = P2.SITE_ID and P1.VERSION = P2.VERSION and
							INF_BLOCK.INF_BLOCK_ID = PAGE_AREA.INF_BLOCK_ID and
							PRG_MODULE.PRG_MODULE_ID = INF_BLOCK.PRG_MODULE_ID and
							PRG_MODULE.SYSTEM_NAME = '{$this -> module_table}' and
							PAGE_AREA.VERSION = :{$this -> module_table}_page_version and
							P2.PAGE_ID = :{$this -> module_table}_page_id
					) AND {$this -> module_table}.{$this -> module_table_pk} = CONTENT_MAP.CONTENT_ID";
			}
			else
			{	
				$where_arr[] = "CONTENT_MAP.INF_BLOCK_ID = :{$this -> module_table}_block_id AND {$this -> module_table}.{$this -> module_table_pk} = CONTENT_MAP.CONTENT_ID";
			}
		}
		if ( $this -> decorators['version'] )
			$where_arr[] = "{$this -> module_table}.VERSION = :{$this -> module_table}_version";
		if ( $this -> decorators['lang'] )
			$where_arr[] = "{$this -> module_table}.LANG_ID = :{$this -> module_table}_lang_id";
		if( $this -> multiple_block )
			$where_arr[] = "
				CONTENT_MAP.INF_BLOCK_ID = INF_BLOCK.INF_BLOCK_ID and
			 	INF_BLOCK.PRG_MODULE_ID = PRG_MODULE.PRG_MODULE_ID and
				CM2.IS_MAIN = '1' and
				CM2.CONTENT_ID = {$this -> module_table}.{$this -> module_table_pk} and
				CM2.INF_BLOCK_ID = IF2.INF_BLOCK_ID and
				IF2.PRG_MODULE_ID = PRG_MODULE.PRG_MODULE_ID";
		return join( ' and ', $where_arr );
	}
	
	/**
	 * Возвращает переменные привязки для кляузы WHERE для запросов по главной таблице модуля, опираясь на $this->decorators
	 *
	 * @return array
	 */
	protected function get_module_binds()
	{
		$binds=array();
		if ( $this -> decorators['block'] )
		{
			if ( $this -> any_block )
			{
				$binds[$this -> module_table . '_page_id'] = $this -> env['page_id'];
				$binds[$this -> module_table . '_page_version'] = $this -> env['version'];
			}
			else
			{
				$binds[$this -> module_table . '_block_id'] = $this -> env['block_id'];
			}
		}
		if ( $this -> decorators['version'] )
			$binds[$this -> module_table . '_version'] = $this -> env['version'];
		if ( $this -> decorators['lang'] )
			$binds[$this -> module_table . '_lang_id'] = $this -> env['lang_id'];
		return $binds;
	}
	
	/**
	 * Формирование запроса к БД
	 *
	 * @param string $select_str	запрашиваемые поля
	 * @param string $filter_str	дополнительные параметры фильтрации
	 * @param string $order_str		порядок сортировки
	 * @param string $limit_str		ограничение числа записей
	 * @return string
	 */
	protected function get_module_sql( $select_str, $filter_str = '', $order_str = '', $limit_str = '' )
	{
		return "
			select {$select_str}
			from ".$this -> get_module_from()."
			where ".$this -> get_module_where()."
			{$filter_str} {$order_str} {$limit_str}";
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Возвращает продукт работы модуля - контент в формате HTML
	 */
	public function get_body(){
		return $this->body;
	}

	/**
	 * Возвращает заголовок страницы
	 */
	public function get_title(){
		return $this->title;
	}

	/**
	 * Возвращает ключевые слова страницы
	 */
	public function get_keywords(){
		return $this->keywords;
	}

	/**
	 * Возвращает описание страницы
	 */
	public function get_description(){
		return $this->description;
	}

	/**
	 * Метод, организующий выполнение работы модуля (диспетчер модуля)
	 */
	abstract protected function content_init();
	
	/**
	 * Метод используется для формирования ссылки на заданную страницу
	 *
	 * @param int $pade_id			идентификатор страницы
	 * @param string $script_name	имя скрипта, если необходимо, чтобы он отличался от index.php
	 * @return string
	 */
	protected function get_url_by_page( $page_id, $script_name = 'index.php' )
	{
		static $url_by_page_cache;
		static $row_by_page_cache;
		
		$func_args = func_get_args();
		$url_by_page_cache_key = join( '|', $func_args + $this -> env );
		if ( isset( $url_by_page_cache[$url_by_page_cache_key] ) )
			return $url_by_page_cache[$url_by_page_cache_key];
		
		$page_site = db::sql_select( '
			select * from SITE
				inner join PAGE on PAGE.SITE_ID = SITE.SITE_ID
			where PAGE_ID = :page_id and VERSION = :version',
			array( 'page_id' => $page_id, 'version' => $this -> env['version'] ) );
		
		if ( count( $page_site ) == 0 ) return $script_name;
		
		$site_url = ( $page_site[0]['SITE_ID'] != $this -> env['site_id'] ) ?
			'http' . ( $_SERVER['HTTPS'] == 'on' ? 's' : '' ) . '://' .
			( $this -> env['version'] == 0 ? $page_site[0]['HOST'] : $page_site[0]['TEST_HOST'] ) : '';
		
		$paths = array( $script_name );
		while ( $page_id )
		{
			if ( !isset( $row_by_page_cache[$page_id][$this -> env['version']] ) )
			{
				$page_row = db::sql_select( '
						select PARENT_ID, DIR_NAME from PAGE where PAGE_ID = :page_id and VERSION = :version',
					array( 'page_id' => $page_id, 'version' => $this -> env['version'] ) );
				
				if ( !count( $page_row ) ) return $script_name;
				
				$row_by_page_cache[$page_id][$this -> env['version']] = $page_row[0];  
			}
			
			$paths[] = $row_by_page_cache[$page_id][$this -> env['version']]['DIR_NAME'];
			$page_id = $row_by_page_cache[$page_id][$this -> env['version']]['PARENT_ID'];
		}
		$paths[] = $site_url;
		
		return $url_by_page_cache[$url_by_page_cache_key] = join( '/', array_reverse( $paths ) );
	}
	
	/**
	 * Метод возвращает номер области, занимаемой данным блоком на данной странице
	 *
	 * @param int $pade_id			идентификатор страницы
	 * @param int $inf_block_id		идентификатор блока
	 * @param boolean $is_main		ищем блок только в главной области
	 * @return integer
	 */
	protected function get_area_id( $page_id, $inf_block_id, $is_main = false )
	{
		$area_row = db::sql_select( '
			select
				PAGE_AREA.TEMPLATE_AREA_ID
			from
				PAGE_AREA, TEMPLATE_AREA
			where
				TEMPLATE_AREA.TEMPLATE_AREA_ID = PAGE_AREA.TEMPLATE_AREA_ID and ' . ( $is_main ? ' TEMPLATE_AREA.IS_MAIN = 1 and ' : '' ) . '
				PAGE_AREA.PAGE_ID = :page_id and PAGE_AREA.INF_BLOCK_ID = :inf_block_id and PAGE_AREA.VERSION = :version',
			array( 'page_id' => intval( $page_id ), 'inf_block_id' => intval( $inf_block_id ), 'version' => intval( $this -> env['version'] ) ) );
		
		if ( !count( $area_row ) )
			return false;
		else
			return $area_row[0]['TEMPLATE_AREA_ID'];
	}
	
	/**
	 * Метод возвращает путь к странице и идентификатор области расположения блока
	 * по системному имени модуля и идентификатору элементу контента модуля
	 *
	 * @param string $module_name	системное имя модуля
	 * @param int $content_id		идентификатор элемента контента
	 * @param bool $main_area		главный блок элемента контента
	 * @param bool $is_main			блок в главной области
	 * @param bool $this_site		блок на текущем сайте
	 * @param string $script_name	имя скрипта, если необходимо, чтобы он отличался от index.php
	 * @return array
	 */
	protected function get_url_by_module_content( $module_name, $content_id, $main_area = true, $is_main = true, $this_site = true, $script_name = 'index.php' )
	{
		static $url_by_module_content_cache;
		
		$func_args = func_get_args();
		$url_by_module_content_cache_key = join( '|', $func_args + $this -> env );
		if ( isset( $url_by_module_content_cache[$url_by_module_content_cache_key] ) )
			return $url_by_module_content_cache[$url_by_module_content_cache_key];
		
		$block_id = db::sql_select( '
				select
					CONTENT_MAP.INF_BLOCK_ID
				from
					CONTENT_MAP, INF_BLOCK, PRG_MODULE
				where
					CONTENT_MAP.CONTENT_ID = :content_id and
					PRG_MODULE.SYSTEM_NAME = :module_name and 
					' . ( $main_area ? 'CONTENT_MAP.IS_MAIN = 1 and' : '' ) . '
					INF_BLOCK.INF_BLOCK_ID = CONTENT_MAP.INF_BLOCK_ID and
					INF_BLOCK.PRG_MODULE_ID = PRG_MODULE.PRG_MODULE_ID',
			array( 'module_name' => $module_name, 'content_id' => $content_id ) );
		
		if ( count( $block_id ) )
			$block_path = $this -> get_url_by_module_name( $module_name, $block_id[0]['INF_BLOCK_ID'], $is_main, $this_site, $script_name );
		else
			$block_path = array( 'PATH' => '', 'AREA' => $this -> env['area_id'] );
		
		return $url_by_module_content_cache[$url_by_module_content_cache_key] = $block_path;
	}
		
	/**
	 * Метод возвращает путь к странице и идентификатор области расположения блока по системному имени модуля
	 *
	 * @param string $module_name	системное имя модуля
	 * @param int $block_id			идентификатор блока
	 * @param bool $is_main			блок в главной области
	 * @param bool $this_site		блок на текущем сайте
	 * @param string $script_name	имя скрипта, если необходимо, чтобы он отличался от index.php
	 * @return array
	 */
	protected function get_url_by_module_name( $module_name, $block_id = '', $is_main = true, $this_site = true, $script_name = 'index.php' )
	{
		static $url_by_module_name_cache;
		
		$func_args = func_get_args();
		$url_by_module_name_cache_key = join( '|', $func_args + $this -> env );
		if ( isset( $url_by_module_name_cache[$url_by_module_name_cache_key] ) )
			return $url_by_module_name_cache[$url_by_module_name_cache_key];
	
		$page_area = db::sql_select( '
			select
				PAGE_AREA.PAGE_ID,
				PAGE_AREA.TEMPLATE_AREA_ID
			from
				PAGE,
				PAGE_AREA,
				TEMPLATE_AREA,
				PRG_MODULE,
				INF_BLOCK
			where
				PAGE.PAGE_ID = PAGE_AREA.PAGE_ID and
				PAGE.VERSION = PAGE_AREA.VERSION and
				PAGE_AREA.TEMPLATE_AREA_ID = TEMPLATE_AREA.TEMPLATE_AREA_ID and
				INF_BLOCK.INF_BLOCK_ID = PAGE_AREA.INF_BLOCK_ID and
				PRG_MODULE.PRG_MODULE_ID = INF_BLOCK.PRG_MODULE_ID and
				PRG_MODULE.SYSTEM_NAME = :module_name and
				' . ( $block_id ? 'PAGE_AREA.INF_BLOCK_ID = :block_id and' : '' ) . '
				' . ( $is_main ? 'TEMPLATE_AREA.IS_MAIN = 1 and' : '' ) . '
				' . ( $this_site ? 'PAGE.SITE_ID = :site_id and' : '' ) . '
				PAGE.VERSION = :version and PAGE.LANG_ID = :lang_id and
				not( PAGE_AREA.PAGE_ID = :page_id and PAGE_AREA.TEMPLATE_AREA_ID = :area_id )',
			array(
				'module_name' => $module_name,
				'version' => $this -> env['version'], 'lang_id' => $this -> env['lang_id'],
				'page_id' => $this -> env['page_id'], 'area_id' => $this -> env['area_id'] ) +
				( $block_id ? array( 'block_id' => $block_id ) : array() ) +
				( $this_site ? array( 'site_id' => $this -> env['site_id'] ) : array() ) );
		
		if ( count( $page_area ) )
			$block_path = array( 'PATH' => $this -> get_url_by_page( $page_area[0]['PAGE_ID'], $script_name ), 'AREA' => $page_area[0]['TEMPLATE_AREA_ID'] );
		else
			$block_path = array( 'PATH' => '', 'AREA' => $this -> env['area_id'] );
		
		return $url_by_module_name_cache[$url_by_module_name_cache_key] = $block_path;
	}
	
	/**
	 * Метод возвращает путь к странице и идентификатор области расположения блока
	 * по системному имени модуля, имени параметра параметра и значению этого параметра
	 *
	 * @param string $module_name	системное имя модуля
	 * @param string $param_name	системное имя параметра модуля
	 * @param string $param_value	значение параметра модуля
	 * @param int $block_id			идентификатор блока
	 * @param bool $is_main			блок в главной области
	 * @param bool $this_site		блок на текущем сайте
	 * @param string $script_name	имя скрипта, если необходимо, чтобы он отличался от index.php
	 * @return array
	 */
	protected function get_url_by_module_param( $module_name, $param_name, $param_value, $block_id = '', $is_main = true, $this_site = true, $script_name = 'index.php' )
	{
		static $url_by_module_param_cache;
		
		$func_args = func_get_args();
		$url_by_module_param_cache_key = join( '|', $func_args + $this -> env );
		if ( isset( $url_by_module_param_cache[$url_by_module_param_cache_key] ) )
			return $url_by_module_param_cache[$url_by_module_param_cache_key];
		
		$param_value = db::sql_select( '
			select
				MODULE_PARAM.MODULE_PARAM_ID,
				PARAM_VALUE.PARAM_VALUE_ID
			from
				MODULE_PARAM,
				PARAM_VALUE,
				PRG_MODULE
			where
				PRG_MODULE.SYSTEM_NAME = :module_name and
				MODULE_PARAM.SYSTEM_NAME = :param_name and
				PARAM_VALUE.VALUE = :param_value and
				PARAM_VALUE.MODULE_PARAM_ID = MODULE_PARAM.MODULE_PARAM_ID and
				MODULE_PARAM.PRG_MODULE_ID = PRG_MODULE.PRG_MODULE_ID',
			array(
				'module_name' => $module_name,
				'param_name' => $param_name,
				'param_value' => $param_value ) );
		
		if ( count( $param_value ) )
		{
			$page_area = db::sql_select( '
				select
					PAGE_AREA.PAGE_ID,
					PAGE_AREA.TEMPLATE_AREA_ID
				from
					PAGE,
					PAGE_AREA,
					TEMPLATE_AREA,
					PAGE_AREA_PARAM
				where
					PAGE.PAGE_ID = PAGE_AREA.PAGE_ID and
					PAGE.VERSION = PAGE_AREA.VERSION and
					PAGE_AREA.TEMPLATE_AREA_ID = TEMPLATE_AREA.TEMPLATE_AREA_ID and
					PAGE_AREA_PARAM.PAGE_ID = PAGE_AREA.PAGE_ID and
					PAGE_AREA_PARAM.VERSION = PAGE_AREA.VERSION and
					PAGE_AREA_PARAM.TEMPLATE_AREA_ID = PAGE_AREA.TEMPLATE_AREA_ID and
					' . ( $block_id ? 'PAGE_AREA.INF_BLOCK_ID = :block_id and' : '' ) . '
					' . ( $is_main ? 'TEMPLATE_AREA.IS_MAIN = 1 and' : '' ) . '
					' . ( $this_site ? 'PAGE.SITE_ID = :site_id and' : '' ) . '
					PAGE_AREA_PARAM.MODULE_PARAM_ID = :module_param_id and
					PAGE_AREA_PARAM.VALUE = :param_value_id and
					PAGE.VERSION = :version and PAGE.LANG_ID = :lang_id and
					not( PAGE_AREA.PAGE_ID = :page_id and PAGE_AREA.TEMPLATE_AREA_ID = :area_id )',
				array(
					'version' => $this -> env['version'], 'lang_id' => $this -> env['lang_id'],
					'page_id' => $this -> env['page_id'], 'area_id' => $this -> env['area_id'], 
					'module_param_id' => $param_value[0]['MODULE_PARAM_ID'], 'param_value_id' => $param_value[0]['PARAM_VALUE_ID'] ) +
					( $block_id ? array( 'block_id' => $block_id ) : array() ) +
					( $this_site ? array( 'site_id' => $this -> env['site_id'] ) : array() ) );
		}
		
		if ( count( $page_area ) )
			$block_path = array( 'PATH' => $this -> get_url_by_page( $page_area[0]['PAGE_ID'], $script_name ), 'AREA' => $page_area[0]['TEMPLATE_AREA_ID'] );
		else
			$block_path = array( 'PATH' => '', 'AREA' => $this -> env['area_id'] );
		
		return $url_by_module_param_cache[$url_by_module_param_cache_key] = $block_path;
	}
	
	/**
	 * Метод позволяет обращаться к произвольному методу произвольного модуля в корректном окружении
	 *
	 * @param string $module_name	системное имя модуля
	 * @param string $method_name	имя метода
	 * @param array $params			массив параметров метода
	 * @param array $vars			массив полей модуля
	 * @return mix
	 */
	protected function call_module( $module_name, $method_name, $params = array(), $vars = array() )
	{
		$module_object = self::factory( $module_name );
		
		$module_object -> env = $this -> env;
		$module_object -> view_param = $this -> view_param;
		$module_object -> module_info = $this -> module_info;
		$module_object -> q_param = $this -> q_param;
		$module_object -> lang = $this -> lang;
		
		foreach ( $vars as $var_name => $var_value )
			$module_object -> $var_name = $var_value;
		
		return call_user_func_array( array( $module_object, $method_name ), $params );
	}

	////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Метод определяет факт участия модуля в таксономии.
	 * @return boolean
	 */
	protected function is_taxonomy()
	{
		static $is_taxonomy_cache;
		
		if ( isset( $is_taxonomy_cache[$this -> module_name] ) )
			return $is_taxonomy_cache[$this -> module_name];
		
		$taxonomy_allow = db::sql_select( '
				select SYSTEM_NAME from TE_OBJECT
				where SYSTEM_NAME = :system_name and
					TAXONOMY_ALLOWED = 1',
			array( 'system_name' => $this -> module_name ) );
		
		return $is_taxonomy_cache[$this -> module_name] = count( $taxonomy_allow ) == 1;
	}
	
	/**
	 * Метод возвращает список записей модуля при поиске по тегу
	 *
	 * @param string $select_str	запрашиваемые поля
	 * @param string $order_str		порядок сортировки
	 * @param string $limit_str		ограничение числа записей
	 * @param string $filter_str	параметры фильтрации
	 * @param string $filter_binds	переменные привязки
	 * @return array
	 */
	protected function get_tag_records( $select_str = '', $order_str = '', $limit_str = '', $filter_str = '', $filter_binds = array() )
	{
		$tag_search = db::sql_select( '
			select
				' . $select_str . '
			from
				' . $this -> get_module_from() . ', TE_OBJECT, TAG_OBJECT, TAG
			where
				' . $this -> module_table . '.' . $this -> module_table_pk . ' = TAG_OBJECT.OBJECT_ID and
				TAG_OBJECT.TE_OBJECT_ID = TE_OBJECT.TE_OBJECT_ID and
				TAG_OBJECT.TAG_ID = TAG.TAG_ID and
				TE_OBJECT.SYSTEM_NAME = :system_name and
				' . $this -> get_module_where() . '
				' . $filter_str . ' ' . $order_str . ' ' . $limit_str,
			array( 'system_name' => $this -> module_table ) + $this -> get_module_binds() + $filter_binds );
		
		return $tag_search;
	}
	
	/**
	 * Метод возвращает список тегов, привязанных к списку записей
	 *
	 * @param string $content_in	список идентификаторов записей
	 * @return array
	 */
	protected function get_tag_list( $content_in )
	{
		$tag_list = array();
		
		if ( $this -> is_taxonomy() )
		{
			$path_and_area = $this -> get_url_by_module_name( 'TAXONOMY', '', true );
			
			if ( $path_and_area['PATH'] )
			{
				$tag_list = db::sql_select( '
						select TAG_OBJECT.OBJECT_ID, TAG.TITLE, TAG.SYSTEM_NAME
						from TAG_OBJECT, TAG, TE_OBJECT
						where TAG_OBJECT.TE_OBJECT_ID = TE_OBJECT.TE_OBJECT_ID and
						TAG.TAG_ID = TAG_OBJECT.TAG_ID and
						TE_OBJECT.SYSTEM_NAME = :system_name and
						TAG_OBJECT.OBJECT_ID in ( ' . $content_in . ' )',
					array( 'system_name' => $this -> module_name ) );
				
				foreach ( $tag_list as $tag_index => $tag_item )
					$tag_list[$tag_index]['URL'] = $path_and_area['PATH'] . '?' . join( '&', array(
						'search_' . $path_and_area['AREA'] . '=' . urlencode( $tag_item['SYSTEM_NAME'] ),
						'view_mode_' . $path_and_area['AREA'] . '=' . 'tag_search',
						'view_module_' . $path_and_area['AREA'] . '=' . strtolower( $this -> module_name ) ) );
				
				$tag_list = lib::array_group( $tag_list, 'OBJECT_ID' );
			}
		}
		
		return $tag_list;
	}
	
	//////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Экспорт списка записей
	 */
	protected function export_content( $mode = 'rss' )
	{
		// Инициализация шаблонизатора
		$this -> tpl = new smarty_ee_module( $this );
		
		$this -> tpl -> assign( 'encoding', params::$params['encoding']['value'] );
		
		$this -> tpl -> assign( 'title', $this -> get_export_title() );
		$this -> tpl -> assign( 'description', $this -> get_export_description() );
		$this -> tpl -> assign( 'date', date( 'r', time() ) );
		$this -> tpl -> assign( 'link', 'http' . ( $_SERVER['HTTPS'] == 'on' ? 's' : '' ) . '://' . $_SERVER['HTTP_HOST'] );
		$this -> tpl -> assign( 'generator', 'RBC Contents 5.0' );
		
		$this -> tpl -> assign( 'list', $this -> get_export_list() );
		
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir . $mode . '.tpl' );
	}
	
	/**
	 * Заголовок экспорта списка записей
	 * 
	 * @return string
	 */
	protected function get_export_title()
	{
		return '';
	}
	
	/**
	 * Описание экспорта списка записей
	 * 
	 * @return string
	 */
	protected function get_export_description()
	{
		return '';
	}
	
	/**
	 * Список экспортируемых записей
	 * 
	 * @return array
	 */
	protected function get_export_list()
	{
		return array();
	}
}

// Раскладка параметров запроса по областям
module::make_q_param();
?>
