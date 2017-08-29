<?php
include_once(params::$params["adm_data_server"]["value"]."class/core/object/object_name.php");
include_once(params::$params["adm_data_server"]["value"]."class/core/auth/auth.php");
include_once(params::$params["adm_data_server"]["value"]."class/core/field/field.php");
include_once(params::$params["adm_data_server"]["value"]."class/core/field/field_prefix.php");
include_once(params::$params["adm_data_server"]["value"]."class/core/primary_key/primary_key.php");
include_once(params::$params["adm_data_server"]["value"]."class/core/url/url.php");
include_once(params::$params["adm_data_server"]["value"]."class/core/decorator/decorator.php");
include_once(params::$params["adm_data_server"]["value"]."class/core/html_element/html_element.php");
include_once(params::$params["adm_data_server"]["value"]."class/core/log/log.php");
include_once(params::$params["common_data_server"]["value"]."interface/module_interface.php");


// Приложение глобальных параметров сделано здесь, чтобы система успевала сделать подготовительные операции, например, создать таблицу из которой берутся эти самые параметры
system_params::apply_global_params_from_db();

/**
 * Абстрактный класс объектов административного интерфейса
 *
 * @package		RBC_Contents_5_0
 * @subpackage core
 * @copyright	Copyright (c) 2006 RBC SOFT
 * @todo добиться полной имплементации интерфейса module_interface и, наверно, перенести эту имплементацию в объект object_interface, где она собственно и нужна
 * @todo А может быть назвать его не object, а как-нибудь более неброско. А то есть некоторая вероятность, что в какой-нибудь будущей версии php класс с таким названием может стать системным. Вот в 6 версии php класса с таким названием нет
 * @todo Распределенные операции не перехватывают php-ошибки
 */
abstract class object extends object_name implements module_interface{
	/**
	 * Место, где хранятся шаблоны административного интерфейса
	 * @var string
	 */
	public $tpl_dir;

	/** 
	 * HTML-код контентной части страницы, заполняется методами action_, используется в html_common()
	 * @var string
	 */
	public $body;

	/**
	 * Название страницы, заполняется методами action_, используется в html_common()
	 * @var string
	 */
	public $title;

	/**
	 * Если приводится к true, то будет выводиться меню, используется в html_common()
	 * @var boolean
	 */
	public $is_menu=true;

	/**
	 * Поле, по которому в настоящий момент должна производиться сортировка
	 * @var string
	 */
	public $sort_field;

	/**
	 * Направление сортировки ("asc" или "desc")
	 * @var string
	 */
	public $sort_ord;

	/**
	 * Текущая страница листалки
	 * @var int
	 */
	public $from;

	/**
	 * Текущий родитель, или "", если это неприменимо к данной таблице
	 * @var int
	 */
	public $parent_id = "";

	/**
	 * Список пунктов меню, сгруппированный по идентификатору пункта
	 *
	 * @var array
	 */
	public $page_id_array = array();
	
	/**
	 * Список пунктов меню, сгруппированный по идентификатору родительского пункта
	 *
	 * @var array
	 */
	public $parent_id_array = array();
	
	/**
	 * Список пунктов меню - путь от корня до текущего раздела
	 *
	 * @var array
	 */
	public $path_id_array = array();
	
	/**
	 * Тип текущего системного раздела
	 *
	 * @var array
	 */
	public $section_type = '';
	
	/**
	 * Заголовок текущего системного раздела
	 *
	 * @var array
	 */
	public $section_title = '';
	
	/**
	 * Ссылка на текущий системный раздел
	 *
	 * @var array
	 */
	public $section_url = '';
	
	/**
	 * Количество записей на страницу
	 * @var int
	 */
	public $rows_per_page;

	/**
	 * Объект, заключающий в себе все необходимые методы для работы со значениями первичного ключа таблицы
	 * @var object	primary_key
	 */
	public $primary_key;

	/**
	 * Объект для работы со ссылками системы
	 * @var object url
	 */
	public $url;

	/**
	 * Объект для работы с разделением доступа
	 * @var object	auth
	 */
	public $auth;

	/**
	 * Объект для работы с полями
	 * @var object	field
	 */
	public $field;

	/**
	 * Ссылка на объект со всеми необходимыми декораторами
	 * 
	 * Если декораторов нет, то ссылается сам на себя.
	 * Необходим для придания возможности переопределения методов декоратором. Поэтому все методы класса
	 * должны вызываться не через $this->, а через $this->full_object->
	 * @var object	object
	 */
	public $full_object;

	/**
	 * Максимальное чиcло ошибок в распределенных операциях, попадающих в отчет
	 * @var int
	 */
	public $max_distributed_errors = 100;

	/**
	 * Идентификатор текущего языка интерфейса
	 * @var int
	 */
	public static $interface_lang_id = "";
	
	/**
	 * Системный раздел, описывающий данный объект (если есть)
	 * @var array
	 */
	public $auth_system_section = "";
	
	/**
	 * Массив допустимых декораторов
	 * @var array
	 */
	public static $allowed_decorators = array(
		"translate" => array(
			"table_decorators" => array("translate"=>1, "rights_inheritance"=>1),
			"pk_decorators" => array() ),
		"rights_inheritance" => array(
			"table_decorators" => array("lang"=>1, "version"=>1, "external"=>1, "translate"=>1, "rights_inheritance"=>1),
			"pk_decorators" => array("lang"=>1, "version"=>1, "external"=>1) ),
		"default" => array(
			"table_decorators" => array("lang"=>1, "version"=>1, "block"=>1, "workflow"=>1, "external"=>1),
			"pk_decorators" => array("lang"=>1, "version"=>1, "external"=>1) ) );
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Возвращает тело контентной части страницы
	 * 
	 * @return string
	 */
	public function get_body(){
		return $this->body;
	}

	/**
	 * Возвращает заголовок страницы
	 * 
	 * @return string
	 */
	public function get_title(){
		return $this->title;
	}

	/**
	 * Возвращает ключевые слова страницы
	 * 
	 * @return string
	 */
	public function get_keywords(){
		return "";
	}

	/**
	 * Возвращает описание страницы
	 * 
	 * @return string
	 */
	public function get_description(){
		return "";
	}

	/**
	 * Возвращает признак вывода меню
	 * 
	 * @return boolean
	 */
	public function is_menu(){
		return $this->is_menu;
	}

	/**
	 * Запуск работы модуля. В случае административного интерфейса ничего не делает (пока?)
	 */
	public function init($env, $view_params, &$module_info){
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Конструктор. Заполняет свойства объекта
	 *
	 * @param string $obj			Название конструируемого объекта
	 * @param string $full_object	Ссылка на полный объект, если ее нет, то в качестве такой ссылки используется сам конструируемый объект
	 * @todo сортировка работает некоррекно для полей с типом select2. В том числе поля переводимых таблиц
	 */
	function __construct(&$obj, &$full_object = null){
		parent::__construct($obj);
		
		// Настраиваем путь у шаблонам административного интерфейса
		$this->tpl_dir=params::$params["adm_data_server"]["value"]."tpl/";
		// Создаем объект для работы с первичным ключом
		$this->primary_key=self::get_decorated_object($this->obj, "primary_key", "primary_key");;
		// Создаем объект для работы с разделением доступа
		$this->auth=auth::singleton();
		// Ссылка на полный объект
		if(is_object($full_object)){
			$this->full_object=&$full_object;
		}else{
			$this->full_object=&$this;
		}
		// Создаем объект для работы с полями
		$this->field=field::factory($this->obj, $this->full_object);
	}

	/**
	 * Деструктор. Устраняет циклические ссылки объекта
	 *
	 * Должен вызываться явно, в противном случае объект будет оставаться в памяти - http://bugs.php.net/bug.php?id=33595
	 */
	function __destruct(){
		$this->primary_key->__destruct();
		unset($this->full_object);
	}
	
	/**
	 * Обработка вызова несуществующего метода объекта
	 *
	 * @param string $method	название перегружаемого метода
	 * @param array $vars		массив с параметрами метода
	 */
	public function __call( $method, $vars )
	{
		throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_method_not_exists'] . ': "' . $method . '"' );
	}
	
	/**
	 * Фабрика. Возвращает полностью готовый объект обработчика объекта административной системы
	 *
	 * @param string $obj	Название конструируемого объекта. Если не указано, то фабрика сама по http-запросу определяет, что ей конструировать
	 * @return object object
	 */
	static public final function factory($obj=""){
			// Запоминаем имя переданного объекта
			$param_obj = $obj;
			// Подключаются правильные параметры пользователя и метаданные
			auth::singleton();
			// Если название объекта не передано, пытаемся получить его из http-запроса
			if(!$obj){
				$obj=preg_replace("/[^a-z0-9_]+/i", "", $_REQUEST["obj"]);
				if($obj){
					$explicit_obj=true;
				}else if( !params::$params["client_mode"]["value"] ) {
					// Если "Избранное" не пустое, пытаемся перейти по первой в списке ссылке
					$favourite_list = db::sql_select( 'select * from FAVOURITE where AUTH_USER_ID = :auth_user_id order by FAVOURITE_ORDER',
						array( 'auth_user_id' => $_SESSION['AUTH_USER_ID'] ) );
					if ( count( $favourite_list ) && preg_match( '/obj=[a-z0-9_]+/i', $favourite_list[0]['URL'] ) ) {
						header( 'Location: ' . $favourite_list[0]['URL'] );
						exit();
					}
				}
			}elseif($obj=="_object_interface"){ // Если требуется вернуть объект для построения элементов интерфейса, то сразу его и возвращаем
				include_once(params::$params["adm_data_server"]["value"]."class/core/object/object_interface.php");
				return new object_interface($obj);
			}else{
				$explicit_obj=true;
			}
			// Если такой таблицы нет, то выбираем объект по умолчанию
			if(!is_array(metadata::$objects[$obj])){
				if($explicit_obj){ // Если объект был указан явно, но при этом он не описан в параметрах, то бросаем исключение
					throw new Exception(metadata::$lang["lang_object"]." '$obj' ".metadata::$lang["lang_not_found"]);
				}
				$obj=params::$params["default_object"]["value"];
			}
			// Подключаем класс типа объекта
			$type=metadata::$objects[$obj]["type"];
			include_once(dirname(__FILE__)."/{$type}.php");
			// Определяемся с названием класса объекта
			$class=(metadata::$objects[$obj]["class"] ? metadata::$objects[$obj]["class"] : $type);
		
			// Возвращаем готовый отдекорированный объект
			$object=self::get_decorated_object($obj, $class, "table"); // Утилиты тоже проходят здесь, но у них нет декораторов, поэтому "table" годится и для них
			
			// Если объект не был указан явно, прикладываем к нему данные из $_REQUEST
			if ( !$param_obj )
				$object -> apply_object_parameters( $_REQUEST );
			
			return $object;
	}
	
	/**
	 * Создает объект и наверчивает на него сверху необходимое число декораторов. Возвращает этот объект
	 *
	 * Применяется для создания объектов таблиц и первичных ключей
	 * @param string $obj				Системное название создаваемого объекта административной части
	 * @param string $class				Название класса создаваемого объекта (декорируемого объекта)
	 * @param string $decoration_type	Тип декорирования, может быть "table" или "primary_key"
	 * @return object
	 * @todo Сделать дополнительное наследование по требованию класса-расширителя
	 * @todo Когда будет сделан декоратор workflow, нужно сделать проверку зависимости других декораторов для него или убедиться, что таких зависимостей нет
	 */
	static private final function get_decorated_object($obj, $class, $decoration_type){
		// Если объекту нужны декораторы, то создаем цепочку декораторов 
		if(is_array(metadata::$objects[$obj]["decorators"])){
			// Допустимые декораторы. Для наличия переводимых таблиц набор декораторов урезан, потому что переводимые таблицы - системные, и им не нужны версии, воркфлоу и т.д.
			if(metadata::$objects[$obj]["decorators"]["translate"]){
				$table_decorators=	self::$allowed_decorators["translate"]["table_decorators"];
				$pk_decorators=		self::$allowed_decorators["translate"]["pk_decorators"];
			}elseif(metadata::$objects[$obj]["decorators"]["rights_inheritance"]){
				$table_decorators=	self::$allowed_decorators["rights_inheritance"]["table_decorators"];
				$pk_decorators=		self::$allowed_decorators["rights_inheritance"]["pk_decorators"];
			}else{
				$table_decorators=	self::$allowed_decorators["default"]["table_decorators"];
				$pk_decorators=		self::$allowed_decorators["default"]["pk_decorators"];
			}
			// Определим директорию, где лежат классы декораторов, а также набор допустимых декораторов
			if($decoration_type=="table"){
				$decorator_dir=params::$params["adm_data_server"]["value"]."class/core/object/decorator/";
				$allowed_decorators=$table_decorators;
			}else{
				$decorator_dir=params::$params["adm_data_server"]["value"]."class/core/primary_key/decorator/";
				$allowed_decorators=$pk_decorators;
			}
			// Собираем список допустимых декораторов в порядке от внутреннего к внешнему из того, что указано в def-файле, а также подключаем их классы
			$decorators=array();
			foreach($allowed_decorators as $name=>$one){
				if(isset(metadata::$objects[$obj]["decorators"][$name])){
					$decorators[]=$name;
					include_once("{$decorator_dir}{$decoration_type}_{$name}.php");
				}
			}
			// Обращаем список декораторов, чтобы дополнительный выбирался первым
			$decorators=array_reverse($decorators);

			// Подключаем спецкласс объекта
			$class=self::include_object_class($obj, $class, $decoration_type);
			
			$dec_c=0; // Номер текущего декоратора от внешнего к внутреннему
			$outer_object="decorator_{$dec_c}"; // Название самого внешнего объекта, который и будет возвращен фабрикой. Совпадает с названием внешнего декоратора или его наследника (если декораторы применяются)
			// Бежим по списку декораторов, создаем их объекты
			foreach($decorators as $decorator){
				$decorator_object="decorator_{$dec_c}"; // Название объекта текущего декоратора
				// Название класса текущего декоратора. Если класс объекта наследуется от декоратора, то для внешнего декоратора используется название самого этого объекта
				if($class!="table" && $decoration_type=="table" && $decorator_object==$outer_object){
					$decorator_class=$class;
				}else{
					$decorator_class="{$decoration_type}_{$decorator}";
				}
				// Создаем объект текущего декоратора, передавая ему ссылку на самого внешнего декоратора, а также "ключевой" хэш всех декораторов
				$$decorator_object=new $decorator_class($$outer_object, metadata::$objects[$obj]["decorators"]);
				$dec_c++;
			}

			// Создаем декорируемый объект самого нижнего уровня со ссылкой на самый внешний объект
			$object=new $decoration_type($obj, $$outer_object);
			// Пробегаем по списку декораторов от внутреннего к внешнему и назначаем для них декорируемые объекты
			for($i=$dec_c-1;$i>=0;$i--){
				$decorator_object="decorator_{$i}"; // Название объекта текущего декоратора
				$inner_object=($i==$dec_c-1 ? "object" : "decorator_".($i+1)); // Название декорируемого объекта для текущего декоратора
				$$decorator_object->apply_inner_object($$inner_object);
			}
			// Если же оказалось, что декораторы не нужны (например, контент в блоках - тогда у первичного ключа декораторов не будет), то меняем название внешнего объекта
			if($dec_c==0){
				$outer_object="object";
			}
		}else{ // Если декораторы не нужны, то просто создаем объект
			$class=self::include_object_class($obj, $class, $decoration_type);
			$outer_object="object";
			
			$$outer_object=new $class($obj);
		}
		return $$outer_object;
	}

	/**
	 * Подключает специальный класс для объекта, если это требуется, в противном случае ничего не делает
	 *
	 * Если при этом спецкласс должен подключить через subclass() еще какой-нибудь класс, то возвращается название этого нового класса, в противном случае возвращается тоже самое название, что и было передано в параметре $class.
	 * Все эти вещи делаются только для сборки наследника таблицы - первичный ключ не допускает модификации.
	 *
	 * @param string $obj				Системное название создаваемого объекта административной части
	 * @param string $class				Название класса создаваемого объекта (декорируемого объекта)
	 * @param string $decoration_type	Тип декорирования, может быть "table" или "primary_key"
	 * @return string
	 * @todo Сейчас мы это делаем всегда. Но если понадобится переопределить только field.php, то придется держать держать пустой основной класс. Надо подумать, чтобы тут такое сделать
	 * @todo пощупать это место и другие на предмет того сколько у нас создается объектов в системе, нет ли где избыточности
	 */
	static private final function include_object_class($obj, $class, $decoration_type){
		$object_level=metadata::$objects[$obj]["object_level"];
		$type=metadata::$objects[$obj]["type"];
		$spec_class=metadata::$objects[$obj]["class"];
		if($spec_class && $decoration_type=="table"){
			include_once(params::$params["adm_data_server"]["value"]."class/{$object_level}/{$type}/{$class}/{$spec_class}.php");
			$class=$spec_class;
		}
		// добавлен механизм подклассов, таким образом если в вызываемом подклассе существует
		// статический метод subclass, то он должен отдать название класса, объект которого нужно
		// создать. Подкласс должен храниться в том же каталоге что и первичный класс
		if (method_exists($class, 'subclass') && $decoration_type=="table") {
			$inner_class=call_user_func(array($class, 'subclass'));
			include_once(params::$params["adm_data_server"]["value"]."class/{$object_level}/{$type}/{$spec_class}/{$inner_class}.php");
			$class=$inner_class;
		}
		return $class;
	}

	/**
	 * Диспетчер. Смотрит на http-запрос и запускает соответствующего обработчика
	 * @todo понять, нужно ли перебрасывать на дефолтный метод, если запрашиваемого метода не существует
	 * @todo придумать красивое сообщение об отсутствии прав на объект
	 * @todo попробовать придумать решение проверки существования метода, которое не валилось бы на декораторах
	 * @todo ловить не только эксепшены, но и стандартные php-ошибки
	 */
	public function dispatcher(){
		$method_name="action_".preg_replace("/[^a-z0-9_]+/i", "", $_REQUEST["action"]);
		if($method_name=="action_"){
			$method_name="action_index"; // по умолчанию используется этот метод
		}
		
		// Проверка на доступ к объекту
		if(!$this->full_object->is_permitted_to("view")){
			throw new Exception(metadata::$lang["lang_permission_denied"]);
		}
		else {
		
global $bench;//
$bench->register(bench::bencher("all_parts"), "view perms", 1);//
bench::bencher("main_area");
			$this->full_object->$method_name();
		}

	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Собирает для текущего объекта описание формы редактирования/добавления для использования в html_form(), а также применяется для сбора фильтра
	 *
	 * @param string $mode			Тип формы - "add", "change", "filter", "filter_short", "copy"
	 * @param string $field_prefix	Префикс, которым будут дополнены названия полей в форме
	 * @param array $record			Запись, которую мы собираемся редактировать, или $_REQUEST для фильтра, может быть не указана, например, при добавлении
	 * @param string $record_prefix	Префикс, которым дополнены данные записи, например, _f_ для фильтра
	 * @param array $fields			Описание полей, аналогичное параметру таблицы "fields" в def-файле. Если $fields является массивом, то метод игнорирует свой объект и работает по описаниям полей из этого параметра
	 * @param array $escape			Делать ли htmlspecialchars для каждого поля
	 * @return array
	 * @todo Придумать извратный селект2 с аяксами ограничивающими набор значений
	 */
	public function get_form_fields($mode, $field_prefix, $record="", $record_prefix="", $fields="", $escape=true){
		// Выставляем способ проверки поля на недобавляемость/неизменяемость. Для фильтра проверка непосредственно в условии сборки полей делается
		if($mode=="add" || $mode=="copy"){
			$no_action="no_add";
		}elseif($mode=="change"){
			$no_action="no_change";
		}
		$descr=array();
		// Определяем откуда мы берем поля
		if(!is_array($fields)){
			$fields=metadata::$objects[$this->obj]["fields"];
		}
		// Размножаем переводимые поля
		$object_fields = array(); $object_record = array();
		foreach( $fields as $field_name => $field)
		{
			if ( $field['translate'] && is_array( $field['translate'] ) )
			{
				foreach( $field['translate'] as  $lang )
				{
					$object_fields[$field_name . '[' . $lang['LANG_ID'] . ']'] = array_merge( $field, array( 'translate' => $lang ) );
					$record[$record_prefix . $field_name . '[' . $lang['LANG_ID'] . ']'] = $record[$record_prefix . $field_name][$lang['LANG_ID']];
				}
			}
			else
				$object_fields[$field_name] = $field;
		}
		
		// Бежим по полям и обрабатываем их
		foreach($object_fields as $field_name=>$field){
			// При массовом перемещении оставляем в карточке только поле-родитель
			if ( $mode == 'group_move' && $field['type'] != 'parent' ) continue;
			
			// А режиме просмотра выкидываем поля с no_view = 1
			if ( $mode == 'view' && $field['no_view'] ) continue;
			
			// Собираем описание полей
			if(((!$field[$no_action] || $field["disabled"]) && $mode!="filter" && $mode!="filter_short")
				|| ( ( ($field["filter"] && $mode=="filter") || ($field["filter_short"] && $mode=="filter_short") ) &&
					in_array( $field["type"], array("text","textarea","int","checkbox","select1","select2","date","datetime") ) )
			){
				if ( $field["filter"] && $mode=="filter" && ( $field["type"] == "date" || $field["type"] == "datetime" ) )
					$object_record[$record_prefix.$field_name] = array(
						"from" => lib::pack_date( $record[$record_prefix.$field_name."_from"], $field["type"] == "datetime" ? "long" : "short" ),
						"to" => lib::pack_date( $record[$record_prefix.$field_name."_to"], $field["type"] == "datetime" ? "long" : "short" ) );
				else if ( $escape )
					$object_record[$record_prefix.$field_name] = htmlspecialchars($record[$record_prefix.$field_name], ENT_QUOTES);
				else
					$object_record[$record_prefix.$field_name] = $record[$record_prefix.$field_name];
				
				$descr[$field_name]=array(
					"title"=>$field["title"],
					"name"=>$field_prefix.$field_name,
					"type"=>$field["type"],
					"view_type"=>$field["view_type"],
					"value"=>$object_record[$record_prefix.$field_name],
					"prefix"=>$this->field->get_prefixes($field),
					"nonempty"=>(($field["errors"] & _nonempty_) ? 1 : 0),
					"disabled"=>($field[$no_action] ? $field["disabled"] : 0),
					"rows"=>( $field["rows"] ? $field["rows"] : 10 ),
					"editor"=>( $field["editor"] && !params::$params['client_mode']['value'] ? 1 : 0 ),
					"translate"=>( $field["translate"] ? $field["translate"] : '' ),
				);
				
 				// Массив дополнительных параметров поля
 				if ( isset( $field["vars"] ) )
 					$descr[$field_name]["vars"] = $field["vars"];
				
				if($field["type"]=="parent"){ // Иерархия
					$exclude = array();
					if($mode=="change"){
						$exclude[] = $this->primary_key->get_id_from_record($record);
					}elseif($mode=="group_move"){
						$group_pks = $this -> primary_key -> get_group_from_request();
						foreach( $group_pks as $pk )
							$exclude[] = $pk["pk"][$this->autoinc_name];
					}
					$descr[$field_name]["value_list"]=$this->full_object->get_index_records($empty_var, "select2", $field["list_mode"], 0, $exclude);
				}elseif($field["type"]=="select2"){ // select2
					$fk_table_obj = object::factory($field["fk_table"]);
					$fk_table_obj -> apply_object_parameters( $none = array() );
					$descr[$field_name]["value_list"]=$fk_table_obj->get_index_records($_REQUEST, "select2", $field["list_mode"]);
					$fk_table_obj -> __destruct();
				}elseif($field["type"]=="select1"){ // select1
					$value_list = array();
					foreach($field["value_list"] as $item){
						$value_list[]=array("_TITLE"=>$item["title"], "_VALUE"=>$item["value"]);
					}
					$descr[$field_name]["value_list"]=$value_list;
				}elseif($field["type"]=="order" && ($mode=="add" || $mode=="copy")){ // Порядок в случае добавления
					list($order_where, $order_joins, $order_binds)=$this->full_object->get_group_where($field["group_by"], $_REQUEST, "_f_");
					$max_order=db::sql_select("SELECT MAX({$field_name}) AS MAX_VALUE FROM {$this->obj} {$order_joins} WHERE {$order_where}", $order_binds);
					$descr[$field_name]["value"]=$max_order[0]["MAX_VALUE"]+10;
				}
			}
		}

		return $descr;
	}
	

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Определение прав текущего пользователя на операцию (здесь по системным разделам)
	 * 
	 * Возвращает bool - доступно ли пользователю запрашиваемое элементарное право, список которых указан в заголовке {@link table.php}.
	 * Этот метод является неким программным многие-ко-многим между элементарными правами и правами в таблице AUTH_PRIVILEGE.
	 * В случае обычного объекта разрешаем все, если есть доступ к текущему системному разделу, то есть на этом уровне $ep_type не используется.
	 * 
	 * @param string $ep_type			Элементарное право, доступ к которому проверяется
	 * @param array $pk					Первичный ключ записи (если это имеет смысл для данного действия). В некоторых случаях (добавление) здесь может передаваться не собственно первичный ключ, а информация о месте, куда добавляется запись - идентификатор родителя, идентификатор блока и другие данные, помогающие определить - есть права или нет.
	 * @param boolean $throw_exception	Бросать ли исключение, если прав нет
	 * @return boolean
	 * @todo Подумать над кэшированием проверок прав, потому что в некоторых случаях они могут производиться несколько раз подряд
	 */
	public function is_permitted_to($ep_type, $pk="", $throw_exception=false){
		// Чтобы не делать лишнего, сразу проверяем на главного администратора
		if($this->auth->is_main_admin){
			return true;
		}
		// Непосредственно проверка на доступ к системному разделу
		list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, "access", "auth_system_section", "AUTH_SYSTEM_SECTION");
		$rights=lib::array_reindex(db::sql_select("SELECT * FROM {$auth_tables}, TE_OBJECT WHERE {$auth_clause} AND TE_OBJECT.TE_OBJECT_ID = AUTH_SYSTEM_SECTION.TE_OBJECT_ID and TE_OBJECT.SYSTEM_NAME = :object_name", array_merge(array("object_name"=>$this->obj), $auth_binds)), "AUTH_SYSTEM_SECTION_ID");
		
		// Уточняем факт наличия прав на раздел с учетом параметров запроса
		$allow = count( $rights ) > 0 && in_array( $this -> auth_system_section['AUTH_SYSTEM_SECTION_ID'], array_keys( $rights ) );
		
		if(!$allow && $throw_exception){
			// Название записи в сообщении не выводится по соображениям защиты данных
			$pk_message=($pk[$this->autoinc_name] ? ": (".$this->primary_key->pk_to_string($pk).")" : "");
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_operation_not_permitted_".$ep_type].$pk_message);
		}
		
		return $allow;
	}

	/**
	 * Определение прав текущего пользователя на массовую операцию (здесь по системным разделам)
	 * 
	 * Возвращает array - список идентификаторов, на записи которых нет прав, или пустой массив, если права на все записи есть
	 * Право проверяется для записей, указанных в $ids. Если хотя бы на одну из записей прав нет и $throw_exception, то проверка провалена.
	 * По-хорошему этот метод должен быть в классе table, так как для других типов объектов смысла в нем нет, но пусть лежит здесь, рядом с is_permitted_to(), с которым он связан
	 * 
	 * @param string $ep_type			Элементарное право, доступ к которому проверяется
	 * @param array $ids				Список идентификаторов записей
	 * @param boolean $throw_exception	Бросать ли исключение, если прав нет
	 * @return array
	 * @todo Подумать над кэшированием проверок прав, потому что в некоторых случаях они могут производиться несколько раз подряд
	 */
	public function is_permitted_to_mass($ep_type, $ids=array(), $throw_exception=false){
		// Поскольку в системных разделах записи имеют одинаковые права в рамках одного системного раздела, то для простоты воспользуемся is_permitted_to()
		$verdict=$this->full_object->is_permitted_to("view", "");
		if(!$verdict && $throw_exception){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_mass_operation_not_permitted_".$ep_type].": ".join(", ", $ids));
		}
		return ($verdict ? array() : $ids);
	}

	/**
	 * Помогает подготовить отчет для is_permitted_to_mass()
	 * 
	 * Используется не в этом классе, а в некоторых других
	 * 
	 * @param array $ids				Список идентификаторов записей
	 * @param array $rights				Массив с правами (список записей с идентификаторами записей, на которые есть права)
	 * @param string $id_name			Название поля-идентификатора в $rights
	 * @return array
	 */
	public function is_permitted_to_mass_report($ids, $rights, $id_name){
		$allowed=array();
		foreach($rights as $right){
			$allowed[]=$right[$id_name];
		}
		return array_diff($ids, $allowed);
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Возвращает поле сортировки и направление сортировки - array("sort_field", "sort_ord)
	 *
	 * Вернет array("", ""), если для объекта не указано поле сортировки, и из $_REQUEST (в случае $is_default=false) тоже ничего не поймалось
	 *
	 * @param boolean $is_default	Возвращать сортировку по умолчанию (из def-файла) или воспользоваться сначала данными из $_REQUEST, а если их не оказалось, то тогда уже умолчальную
	 * @return array
	 */
	public final function get_sort_field_and_ord($is_default=false){
		if(!$is_default && metadata::$objects[$this->obj]["fields"][$_REQUEST["sort_field"]]["show"]){
			$sort_field=$_REQUEST["sort_field"];
			$sort_ord=$_REQUEST["sort_ord"];
		}else{
			if(is_array(metadata::$objects[$this->obj]["fields"])){
				foreach(metadata::$objects[$this->obj]["fields"] as $field_name=>$field){
					if($field["sort"]){
						$sort_field=$field_name;
						$sort_ord=$field["sort"];
					}
				}
			}
		}
		if($sort_field){
			if($sort_ord!="desc"){
				$sort_ord="asc";
			}
		}else{
			$sort_ord="";
		}
		return array($sort_field, $sort_ord);
	}

	/**
	 * Изменение видимости полей на карточках добавления/редактирования
	 *
	 * Применять вдумчиво и осторожно - изменяет метаданные.
	 * Применяется в том случае, если при каких-то условиях поле может менять свою заполняемость, например, в зависимости от типа записи.
	 * Параметры не boolean, а int для совместимости с def-файлами.
	 *
	 * @param array $field_names	список полей, которым нужно сменить заполняемость при добавлении и редактировании
	 * @param int $no_add			запретить добавление, значения - 1/0
	 * @param int $no_change		запретить редактирование, значения - 1/0
	 * @param int $disabled			делать поле выводимым на карточке, но потушенным
	 * @todo продумать копирование, хотя оно по идее должно как добавление работать
	 */
	public final function change_field_visibility($field_names, $no_add, $no_change, $disabled=0){
		foreach($field_names as $field_name){
			metadata::$objects[$this->obj]["fields"][$field_name]["no_add"]=$no_add;
			metadata::$objects[$this->obj]["fields"][$field_name]["no_change"]=$no_change;
			metadata::$objects[$this->obj]["fields"][$field_name]["disabled"]=$disabled;
		}
	}

	/**
	 * Добавление дополнительных проверок для полей
	 *
	 * Применять вдумчиво и осторожно - изменяет метаданные.
	 * Применяется в том случае, если при каких-то условиях поле может стать более жестко проверяемым, например, в зависимости от типа записи
	 *
	 * @param array $field_names	список полей, которым нужно добавить новые проверки
	 * @param int $error			новые проверки на неправильное заполнение
	 */
	public final function add_field_error($field_names, $error){
		foreach($field_names as $field_name){
			metadata::$objects[$this->obj]["fields"][$field_name]["errors"]|=$error;
		}
	}
	
	/**
	 * Удаление дополнительных проверок для полей
	 *
	 * Применять вдумчиво и осторожно - изменяет метаданные.
	 * Применяется в том случае, если при каких-то условиях поле может стать менее проверяемым, например, в зависимости от типа записи
	 *
	 * @param array $field_names	список полей, которым нужно добавить новые проверки
	 * @param int $error			новые проверки на неправильное заполнение
	 */
	public final function remove_field_error($field_names, $error){
		foreach($field_names as $field_name){
			metadata::$objects[$this->obj]["fields"][$field_name]["errors"]&=~$error;
		}
	}
	

	/**
	 * Возвращает html-код отчета об ошибках выполнения операции
	 *
	 * @param array $errors		Массив с ошибками - array(array("id"=>11, "error"=>"Error message))
	 * @return string
	 * @todo Подизайнить вывод списка. Типа "все хорошо, но вот такие-то записи не получилось..."
	 */
	public final function error_report($errors){
		$operations=array( array("name"=>"back", "alt"=>metadata::$lang["lang_back"], "url"=>$this->url->get_url()) );
		$table_header=html_element::html_operations( array( "operations" => $operations ), $this->tpl_dir."core/html_element/html_operation.tpl");
		$headers=array("id"=>array("title"=>metadata::$lang["lang_identifier"]), "error"=>array("title"=>metadata::$lang["lang_error_message"]));
		$table=html_element::html_table( array( "header" => $headers, "list" => $errors, "counter" => count( $errors ) ), $this->tpl_dir."core/html_element/html_table.tpl");
		return $table_header.$table;
	}

	/**
	 * Действие - распределенная операция
	 * 
	 * Метод _info должен возвращать массив, содержащий следующие поля:
	 * 		title				- заголовок страницы
	 * 		back_url			- url для ссылки "Вернуться"
	 *		total				- общее число операций или -1 если оно неизвестно
	 * 		for_once			- число операций, выполяемых за один раз
	 * 		exception_fatal		- флаг, означающий что операция прекращется при первом эксепшене
	 * 		success_message		- сообщение об успешном выполнении операции
	 * 		complete_message	- сообщение об успешном завершении всех операций
	 */
	public final function action_distributed()
	{
		$operation = htmlspecialchars( $_REQUEST['do_op'], ENT_QUOTES );
		$status = unserialize( base64_decode( $_REQUEST['do_status'] ) );
		if ( !$status ) $status = array();
		
		$info_method = $operation . '_info';
		
		$info = $this -> full_object -> $info_method( $status );
		
		$this -> title = $info['title'];
		
		if ( !$info['back_url'] )
			$info['back_url'] = $this -> url -> get_url( '' );
		
		$action_url = $this -> url -> get_url( 'service' ) . '&do_op=' . $operation;
		
		$status = array_merge( $status, $info );
		
		$_SESSION['distributed'][$operation] = array( 'status' => $status );
		
		$distr_tpl = new smarty_ee( metadata::$lang );
		
		$distr_tpl -> assign( 'total', $status['total'] );
		$distr_tpl -> assign( 'back_url', $status['back_url'] );
		$distr_tpl -> assign( 'action_url', $action_url );
		
		$this -> body = $distr_tpl -> fetch( $this -> tpl_dir . 'core/object/html_distributed.tpl' );
	}

	/**
	 * Обработчик команды 'distributed'. Распределенная операция
	 * 
	 * Метод _list должен возвращать массив, элементы которого будут последовательно передаваться методу _item
	 * Метод _item должен возвращать массив, содержащий следующие поля:
	 * 		message			- сообщение; строка, попадающая в отчет
	 * 
	 * @param string $mark - уникальный идентификатор команды
	 */
	public final function command_distributed( $mark = '' )
	{
		$operation = htmlspecialchars( $_REQUEST['do_op'], ENT_QUOTES );
		$status = $_SESSION['distributed'][$operation]['status'];
		if ( !$status ) $status = array();
		
		$list_method = $operation . '_list';
		$item_method = $operation . '_item';
		$commit_method = $operation . '_commit';
		
		$total = intval( $status['total'] );
		$for_once = intval( $status['for_once'] ? $status['for_once'] : 15 );
		$status['counter'] = intval( $status['counter'] );
		$counter=&$status['counter'];
		$exception_fatal = $status['exception_fatal'];
		
		$list = $this -> full_object -> $list_method( $status, $counter, $for_once );
		
		foreach( $list as $item )
		{
			try {
				$_SESSION['distributed'][$operation]['report'][] =
					$this -> full_object -> $item_method( $item, $status );
				$counter++;
			} catch( Exception $e ) {
				// В зависимости от $exception_fatal вызываем исключение или продолжаем операцию
				if ( $exception_fatal ) {
					throw $e;
				}

				$this->add_distributed_exception($operation, $e, $exception_fatal);		
				$counter++;
			}
		}
		
		$status['counter'] = $counter;
		
		$final = $counter == $total || count( $list ) == 0 || $exception_exit;
		
		if ( $final && !$exception_exit && method_exists( $this -> full_object, $commit_method ) ) {
			$this -> full_object -> $commit_method( $status );
		}

		$_SESSION['distributed'][$operation]['status'] = $status;
		return html_element::xml_response( '<items count="' . $counter . '"' . ( $final ? ' final="true"' : '' ) . '/>', $mark );
	}
	
	// 
	/**
	* Добавляет распределенную ошибку в сессию
	* @param string $operation название операции
	* @param object Exception $e объект исключения
	* @param int $exception_fatal является ли исключение ошибкой или предупреждением
	* @todo проверить на максимум и если больше - не писать уже ничего в массив
	*/
	
	public final function add_distributed_exception ($operation, &$e, $exception_fatal=0) {
		$_SESSION['distributed'][$operation]['report'][] = array(
					'error' => $exception_fatal ? 'error' : 'warning',
					'message' => $e -> getMessage()
		);
	}

	/**
	 * Действие - вывод отчета о распределенной операции
	 */
	public final function action_distributed_report()
	{
		$operation = htmlspecialchars( $_REQUEST['do_op'], ENT_QUOTES );
		$report = $_SESSION['distributed'][$operation]['report'];
		if ( !$report ) $report = array();
		
		$success_count = 0;
		foreach ( $report as $report_id => $report_item )
			if ( $report_item['error'] )
				$exception_list[] = $report_item['message'];
			else
				$success_count++;
		
		if ( count( $exception_list ) )
		{
			$log_type_id = log::$log_types['log_errors']['LOG_TYPE_ID'];
			$error_log_url = 
				"index.php?obj=LOG_RECORD&action=view&_f_LOG_TYPE_ID={$log_type_id}&LOG_RECORD_ID=" .
				$this -> log_register_distributed_error( $_SESSION['distributed'][$operation]['status']['title'], $exception_list );
		}
		
		$report_tpl = new smarty_ee( metadata::$lang );
		
		$report_tpl -> assign( 'report', $report );
		$report_tpl -> assign( 'success_count', $success_count );
		$report_tpl -> assign( 'status', $_SESSION['distributed'][$operation]['status'] );
		
		if ( $error_log_url )
			$report_tpl -> assign( 'error_log_url', $error_log_url );
		
		$this -> title = $_SESSION['distributed'][$operation]['status']['title'];
		$this -> body = $report_tpl -> fetch( $this -> tpl_dir . 'core/object/html_distributed_report.tpl' );
	}

	/**
	 * Действие - web-сервис (возвращает результат в виде XML)
	 *
	 * О том, какую именно команду нужно выполнять, понимает по $_REQUEST['command'], то есть выступает диспетчером.
	 * Делает прямой вывод в браузер (print) и завершает свое выполнение с помощью exit(), то есть действует в обход интерфейса административной системы.
	 * В указанный метод в обязательном порядке передается метка команды $_REQUEST['mark']. Метка команды должна присутствовать и в ответе сервера.
	 */
	public final function action_service()
	{
		header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT' );
		
		header( 'Content-Type: text/xml; charset=' . params::$params['encoding']['value'] );
		
		if ( $_REQUEST['command'] )
		{
			$command_method = 'command_' . htmlspecialchars( $_REQUEST['command'], ENT_QUOTES );
			if ( is_callable( array( $this -> full_object, $command_method ) ) )
				print $this -> full_object -> $command_method( htmlspecialchars( $_REQUEST['mark'], ENT_QUOTES ) );
		}
		
		exit();
	}
	
	/**
	 * Формирует меню системных разделов для "материалов", "настроек" и "утилит"
	 */
	public function get_system_menu()
	{
		$records = array(); $current_id = '';
		
		// Предворяем дерево справочников списком программных модулей
		if ( $this -> section_type == 'content' )
		{
			$prg_module_obj = object::factory( 'PRG_MODULE' );
			list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
				$prg_module_obj -> ext_field_selection( 'TITLE', 1 );
			$prg_module_obj -> __destruct();
			
			$prg_modules = db::replace_field(db::sql_select( "
				select PRG_MODULE.*, " . $dec_field . " as \"_TITLE\"
				from PRG_MODULE " . $dec_join[0] . " where IS_ELEMENTS = 1
				order by " . $dec_field, $dec_binds ), 'TITLE', '_TITLE');
			
			$records[] = array( 'AUTH_SYSTEM_SECTION_ID' => 'inf_block', 'PARENT_ID' => 0, 'TITLE' => metadata::$lang['lang_inf_block_table'], 'SYSTEM_NAME' => 'INF_BLOCK', 'SECTION_ORDER' => 10 );
			
			$module_array = array(); $section_order = 0;
			foreach ( $prg_modules as $module )
				$records[] = array( 'AUTH_SYSTEM_SECTION_ID' => -$module['PRG_MODULE_ID'], 'PARENT_ID' => 'inf_block', 'TITLE' => $module['TITLE'], 'SYSTEM_NAME' => $module['SYSTEM_NAME'], 'SECTION_ORDER' => $section_order = $section_order + 10 );
			
			// Определяем идентификатор текущего объекта в дереве материалов
			foreach ( $records as $record )
				if ( $record['SYSTEM_NAME'] == $this -> obj )
					$current_id = $record['AUTH_SYSTEM_SECTION_ID'];
		}
		
		// Получаем список системных разделов, на который данный пользователь имеет права
		$auth_system_section_obj = object::factory( 'AUTH_SYSTEM_SECTION' );
 		$system_sections = $auth_system_section_obj -> get_allow_system_sections( $this -> section_type );
 		$auth_system_section_obj -> __destruct();
 		
 		$records = array_merge( $records, $system_sections );
		
		$records = get_tree::get( $records, 'AUTH_SYSTEM_SECTION_ID', 'PARENT_ID', 'SECTION_ORDER' );
		
		// Строим глобальный массив, проиндексированный по номеру раздела
		$this -> page_id_array = lib::array_reindex( $records, 'AUTH_SYSTEM_SECTION_ID' );
		
		// Определяем идентификатор текущего объекта в дереве системных разделов
		if ( !$current_id )
			$current_id = $this -> auth_system_section['AUTH_SYSTEM_SECTION_ID'];
		
		// Специальным методом корректируем массивы открытых и закрытых веток
		$this -> tree_state_correction( $this -> section_type, $current_id );
		
		// Строим глобальный массив, проиндексированный по родителю.
		// В него входят все разделы, при этом из них некоторые помечаются как закрытые.
		$this -> parent_id_array = array();
		foreach( $records as &$rs )
		{
			$rs['COLLAPSED'] = ( $rs['TREE_DEEP'] >= params::$params['default_tree_depth']['value'] ||
				isset( $_SESSION['tree_collapsed'][$this -> section_type][$rs['AUTH_SYSTEM_SECTION_ID']] ) ) &&
				!isset( $_SESSION['tree_expanded'][$this -> section_type][$rs['AUTH_SYSTEM_SECTION_ID']] );
			$this -> parent_id_array[$rs['PARENT_ID']][] = $rs;
		}
		
		// На основании полученного массива строим дерево разделов
		$tree_list = $this -> get_tree_list( 0, $current_id );
		
		// Строим путь от текущего раздела до корня системного меню
		if ( $current_id )
		{
			$this -> path_id_array[] = $this -> page_id_array[$parent_id = $current_id];
			while( $parent_id = $this -> page_id_array[$parent_id]['PARENT_ID'] )
				$this -> path_id_array[] = $this -> page_id_array[$parent_id];
			
			foreach ( $this -> path_id_array as $tree_index => $tree_item )
				if ( $tree_item['SYSTEM_NAME'] )
					$this -> path_id_array[$tree_index]['URL'] = "index.php?obj={$tree_item['SYSTEM_NAME']}" . ( $tree_item['FILTER_PRESET'] ? "&{$tree_item['FILTER_PRESET']}" : "" ) . ( $tree_item['OBJECT_PARAM'] ? "&{$tree_item['OBJECT_PARAM']}" : "" );
		}
		
		// Выводим дерево разделов в шаблон
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'tree_list', $tree_list );
		$tpl -> assign( 'tree_param', "{ 'name': '" . $this -> section_type . "', 'url': 'index.php?obj=" . $this -> obj . "&action=service', 'standalone': true }" );
		
		$tpl -> assign( 'section_title', $this -> section_title );
		$tpl -> assign( 'section_icon', $this -> section_type . '.gif' );
		
		return $tpl -> fetch( $this -> tpl_dir.'core/object/html_tree_menu.tpl');
	}
	
	/**
	 * Метод получает и прикладывает к объекту данные его системного раздела
	 *
	 * @param array &$request	ссылка на $_REQUEST или его эмуляцию
	 */
	public function apply_auth_system_section( &$request )
	{
		// Получаем список параметров, переданных в запросе
		$query_array = explode( '&', http_build_query( $request ) );
		
		// Получаем список системных разделов, относящихся к данному объекту. Последними идут разделы без параметров
		$sections = db::sql_select( 'select * from AUTH_SYSTEM_SECTION where AUTH_SYSTEM_SECTION.TE_OBJECT_ID = :te_object_id
			order by OBJECT_PARAM desc', array( 'te_object_id' => object_name::$te_object_ids[$this -> obj]['TE_OBJECT_ID'] ) );
		
		// Определяем системный раздел, относящийся к текущему объекту
		foreach ( $sections as $section ) {
			$object_params = $section['OBJECT_PARAM'] ? explode( '&', $section['OBJECT_PARAM'] ) : array();
			if ( count( array_intersect( $object_params, $query_array ) ) == count( $object_params ) ) {
				$this -> auth_system_section = $section; break;
			}
		}
	}
	
	/**
	 * Метод прикладывает к объекту данные из $_REQUEST
	 *
	 * @param array &$request	ссылка на $_REQUEST или его эмуляцию
	 */
	public function apply_object_parameters( &$request )
	{
		// Получаем запись, описывающую системный раздел объекта
		$this -> apply_auth_system_section( $request );
		
		// Настраиваем правильную сортировку записей
		list($this->sort_field, $this->sort_ord)=$this->get_sort_field_and_ord(false);
		// Настраиваем правильную страницу листалки
		$this->from=intval($request["from"]);
		if($this->from<1){
			$this->from=1;
		}
		// Настраиваем родителя
		$parent=metadata::$objects[$this->obj]["parent_field"];
		if($parent){
			$this->parent_id=intval($request["_f_".$parent]);
		}else{
			$this->parent_id="";
		}
		// Определяемся с количеством записей на странице
		if($request["action"]=="m2m" && metadata::$objects[$request["linked_table"]]["m2m"][$request["m2m"]]["rows_per_page"]){
			$this->rows_per_page=metadata::$objects[$request["linked_table"]]["m2m"][$request["m2m"]]["rows_per_page"];
		}elseif(metadata::$objects[$this->obj]["rows_per_page"]){
			$this->rows_per_page=metadata::$objects[$this->obj]["rows_per_page"];
		}else{
			$this->rows_per_page=params::$params["rows_per_page"]["value"];
		}
		
		// Создаем объект для работы со ссылками
		$this->url=new url($this->obj, $this->sort_field, $this->sort_ord, $this->from, $this->parent_id, $this->auth_system_section['OBJECT_PARAM']);
		
		if ( $this -> obj == 'PAGE' )
			$this -> section_type = 'page';
		elseif ( $this -> obj == 'AUTH_SYSTEM_SECTION_TOOL' )
			$this -> section_type = $request['SECTION_TYPE'];
		elseif ( isset( $this -> auth_system_section['SECTION_TYPE'] ) )
			$this -> section_type = $this -> auth_system_section['SECTION_TYPE'];
		else
			$this -> section_type = 'content';
		
		switch ( $this -> section_type )
		{
			case 'page':
				$this -> section_title = metadata::$lang['lang_page_table'];
				$this -> section_url = 'index.php?obj=PAGE';
				break;
			case 'content':
				$this -> section_title = metadata::$lang['lang_content'];
				$this -> section_url = 'index.php?obj=AUTH_SYSTEM_SECTION_TOOL&SECTION_TYPE=content';
				break;
			case 'settings':
				$this -> section_title = metadata::$lang['lang_settings'];
				$this -> section_url = 'index.php?obj=AUTH_SYSTEM_SECTION_TOOL&SECTION_TYPE=settings';
				break;
			case 'utility':
				$this -> section_title = metadata::$lang['lang_utility'];
				$this -> section_url = 'index.php?obj=AUTH_SYSTEM_SECTION_TOOL&SECTION_TYPE=utility';
				break;
		}
	}
	
	/**
	 * Метод для взаимной коррекции массивов открытых и закрытых веток дерева
	 * 
	 * @param string $tree_name - уникальный идентификатор дерева
	 * @param string $current_id - идентификатор выделенной ветки дерева
	 */
	public function tree_state_correction( $tree_name = '', $current_id = '' )
	{
		// Создаем в случае надобности пустые массивы открытых и закрытых веток дерева
		if ( !is_array( $_SESSION['tree_expanded'][$tree_name] ) )
			$_SESSION['tree_expanded'][$tree_name] = array();
		if ( !is_array( $_SESSION['tree_collapsed'][$tree_name] ) )
			$_SESSION['tree_collapsed'][$tree_name] = array();
		
		// Корректируем массив открытых веток, чтобы в нем не оставалось потомков закрытых веток
		foreach ( $_SESSION['tree_expanded'][$tree_name] as $expanded_page_id => $expanded_page_item )
		{
			foreach ( $_SESSION['tree_collapsed'][$tree_name] as $collapsed_page_id => $collapsed_page_item )
			{
				$parent_id = $expanded_page_id;
				while( $parent_id = $this -> page_id_array[$parent_id]['PARENT_ID'] )
					if ( $parent_id == $collapsed_page_id )
						unset( $_SESSION['tree_expanded'][$tree_name][$expanded_page_id] );
			}
		}
		
		// Поднимаемся по дереву от текущего элемента до корня и запоминаем путь
		$path_to_current = array(); $path_to_current[] = $current_id;
		while( $current_id = $this -> page_id_array[$current_id]['PARENT_ID'] )
			$path_to_current[] = $current_id;
		
		// Для всех веток на этом пути убираем атрибут закрытости
		foreach ( $path_to_current as $current_id )
			unset( $_SESSION['tree_collapsed'][$tree_name][$current_id] );
		
		// Для всех веток на этом пути ставим атрибут открытости
		foreach ( $path_to_current as $current_id )
			$_SESSION['tree_expanded'][$tree_name][$current_id] = 1;
	}

	/**
	 * Рекурсивный метод постоения дерева системных разделов
	 * 
	 * @param int $parent_id - идентификатор страницы, с которой начинается постоение дерева
	 * @param int $current_id - идентификатор текущего объекта
	 */
	private function get_tree_list( $parent_id = 0, $current_id = 0 )
	{
		$children = array();
		if ( is_array( $this -> parent_id_array[$parent_id] ) )
			foreach( $this -> parent_id_array[$parent_id] as $child )
				$children[] = 
					"{ 'title': '" . htmlspecialchars( $child['TITLE'], ENT_QUOTES ) . "', " .
					( $child['SYSTEM_NAME'] ?
						"'object': window, " .
						"'method': 'redirect', " .
						"'param': { 'url': 'index.php?obj={$child['SYSTEM_NAME']}" . ( $child['FILTER_PRESET'] ? "&{$child['FILTER_PRESET']}" : "" ) . ( $child['OBJECT_PARAM'] ? "&{$child['OBJECT_PARAM']}" : "" ) . "' }, " : "" ) .
					"'parent_id': '" . $child['AUTH_SYSTEM_SECTION_ID'] . "', " .
					"'current_id': '" . $current_id . "', " .
					"'collapsed': " . ( $child['COLLAPSED'] ? 1 : 0 ) . ", " .
					"'is_children': " . ( is_array( $this -> parent_id_array[$child['AUTH_SYSTEM_SECTION_ID']] ) ? '1' : '0' ) .
					( is_array( $this -> parent_id_array[$child['AUTH_SYSTEM_SECTION_ID']] ) ? ", 'items': [ " . $this -> get_tree_list( $child['AUTH_SYSTEM_SECTION_ID'], $current_id ) . "]" : "" ) . " }";
		return join( ',', $children );
	}

	/**
	 * Обработчик команды 'tree_open'. Реакция на открытие ветки дерева
	 * 
	 * @param string $mark - уникальный идентификатор команды
	 */
	public function command_tree_open( $mark = '' )
	{
		$tree_name = $_REQUEST['name'];
		$parent_id = $_REQUEST['parent_id'];
		
		if ( !isset( $_SESSION['tree_expanded'][$tree_name][$parent_id] ) )
			$_SESSION['tree_expanded'][$tree_name][$parent_id] = 1;
		unset( $_SESSION['tree_collapsed'][$tree_name][$parent_id] );
		
		// Возвращаем пустой ответ
		return html_element::xml_response( '', $mark );
	}
	
	/**
	 * Обработчик команды 'tree_close'. Реакция на закрытие ветки дерева
	 * 
	 * @param string $mark - уникальный идентификатор команды
	 */
	public function command_tree_close( $mark = '' )
	{
		$tree_name = $_REQUEST['name'];
		$parent_id = $_REQUEST['parent_id'];
		
		if ( !isset( $_SESSION['tree_collapsed'][$tree_name][$parent_id] ) )
			$_SESSION['tree_collapsed'][$tree_name][$parent_id] = 1;
		unset( $_SESSION['tree_expanded'][$tree_name][$parent_id] );
		
		// Возвращаем пустой ответ
		return html_element::xml_response( '', $mark );
	}

	/**
	* Обработчик команды ping
	*
	* @param string $mark - уникальный идентификатор команды
	*/
	public function command_ping ( $mark = '' ) {
		// Возвращаем пустой ответ
		return html_element::xml_response( '', $mark );
	}
	
	/**
	* Обработчик команды unload - вызывается при уходе пользователя со страницы
	*
	* @param string $mark - уникальный идентификатор команды
	*/

	public function command_unload ( $mark = '' ) {
		// Возвращаем пустой ответ
		return html_element::xml_response( '', $mark );
	}


	/**
	* Ф-ия позволяющая запросить подтверждение у пользователя
	* @param string $msg	Текст вопроса
	* @param array $add_params	дополнительные параметры, которые необходимо передать скрипту
	*/ 

	public function confirm_action($msg, $add_params=array()) {
		if ($_POST['ca_Yes']) 
			return true;
		elseif ($_POST['ca_No'])
			return false;
		else
			echo html_element::html_post_form('CONFIRM_ACTION', array('message'=>$msg, 'GET'=>serialize($_GET), 'POST'=>serialize($_POST+$add_params)), $this->tpl_dir."core/html_element/html_form.tpl");
		
		exit;
	}
	
	/**
	* Функция регистрации ошибки распределенных операций в журнале ошибок
	*
	* @param $operation_name - Название операции
	* @array $exception_array - массив объектов Exception
	* @return int
	*/
	public function log_register_distributed_error($operation_name, $exception_list = array ()) {
		if (!log::is_enabled('log_errors')) return;
		
		if ( count( $exception_array ) > $this -> max_distributed_errors )
			$exception_list[] = metadata::$lang['lang_total_errors'] . ': ' . count( $exception_array );
		
		$extended_info = join( ", \n", $exception_list );
		
		$log_info = array (
			'msg' => metadata::$lang['lang_errors_in_distributed_operation'].' "'.$operation_name.'"'
		);
		
		return log::register('log_errors', 'distributed_error', $log_info, 0, 0, 0, "", $extended_info);
	}
	
	/**
	* Метод возвращает идентификатор текущего языка интерфейса
	*
	* @return int
	*/
	public function get_interface_lang()
	{
		if ( !self::$interface_lang_id )
		{
			$interface_lang = db::sql_select( '
					select LANG_ID from LANG where ROOT_DIR = :root_dir',
				array( 'root_dir' => params::$params['default_interface_lang']['value'] ) );
			self::$interface_lang_id = $interface_lang[0]['LANG_ID'];
		}
		return self::$interface_lang_id;
	}
	
	/**
	* Возвращает представление исключения в виде xml
	* @param object Exception $e Объект исключения
	* @return string XML
	*/
	public static function xml_error ($msg, $file, $line, $debug, $trace) {
		$response  = '<?xml version="1.0" encoding="' . params::$params['encoding']['value'] . '"?>';
		$response .= '<response mark="error">';
		$response .= '<msg><![CDATA['.nl2br(htmlspecialchars($msg, ENT_QUOTES)).']]></msg>';
		if (params::$params['debug_mode']['value']) {
			$response .= '<file><![CDATA['.htmlspecialchars($file, ENT_QUOTES).']]></file>';
			$response .= '<line><![CDATA['.htmlspecialchars($line, ENT_QUOTES).']]></line>';
			$response .= '<trace><![CDATA['.nl2br(htmlspecialchars($trace, ENT_QUOTES)).']]></trace>';
			$response .= '<debug><![CDATA['.nl2br(str_replace("\t", "&nbsp;&nbsp;", htmlspecialchars($debug, ENT_QUOTES))).']]></debug>';
		}
		$response .= '</response>';
		return $response;		
	}
	
	/**
	* Выводит информацию об ошибке в шаблоне
	* @param String $msg Сообщение
	* @param String $file Путь к файлу
	* @param String $line Строка в файле
	*/
	public static function html_error ($msg, $file, $line, $debug, $trace, $back_url=true) {
		$tpl = new smarty_ee(metadata::$lang);
		$tpl->assign('msg', nl2br(htmlspecialchars($msg, ENT_QUOTES)));
		if (params::$params['debug_mode']['value']) {
			$tpl->assign('file', htmlspecialchars($file, ENT_QUOTES));
			$tpl->assign('line', htmlspecialchars($line, ENT_QUOTES));
			$tpl->assign('trace', nl2br(htmlspecialchars($trace, ENT_QUOTES)));
			$tpl->assign('debug', nl2br(str_replace("\t", "&nbsp;&nbsp;", htmlspecialchars($debug, ENT_QUOTES))));
		}
		if ( $back_url )
			$tpl->assign('back_url', 'javascript:history.back()');
		return $tpl->fetch(params::$params["adm_data_server"]["value"]."tpl/core/object/html_error.tpl");
	}
	
	/**
	 * Возвращает идентификатор цепочки публикации по умолчанию
	 */
	public function get_default_workflow_id(){
		$workflow_default = db::sql_select( "select WF_WORKFLOW_ID from WF_WORKFLOW where IS_DEFAULT = 1" );
		if ( count( $workflow_default ) )
			return $workflow_default[0]["WF_WORKFLOW_ID"];
		return 0;
	}
}
?>
