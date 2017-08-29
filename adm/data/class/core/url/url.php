<?php
/**
 * Класс для формирования ссылок в системе, а также хидденов для форм системы
 *
 * @package    RBC_Contents_5_0
 * @subpackage core
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class url extends object_name{

	/**
	 * Поле, по которому в настоящий момент должна производиться сортировка
	 * @var string
	 */
	protected $sort_field;

	/**
	 * Направление сортировки ("asc" или "desc")
	 * @var string
	 */
	protected $sort_ord;

	/**
	 * Текущая страница листалки
	 * @var int
	 */
	protected $from;

	/**
	 * Текущий родитель, или "", если это неприменимо к данной таблице
	 * @var int
	 */
	protected $parent_id;

	/**
	 * Параметры текущего системного раздела
	 * @var string
	 */
	protected $object_params;

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Конструктор. Заполняет свойства объекта
	 *
	 * @param string $obj			системное название объекта, ссылки для которого строятся
	 * @param string $sort_field	поле, по которому в настоящий момент должна производиться сортировка
	 * @param string $sort_ord		направление сортировки ("asc" или "desc")
	 * @param int $from				текущая страница листалки
	 * @param int $parent_id		текущий родитель, или "", если это неприменимо к данной таблице
	 * @param string $object_params	параметры текущего системного раздела
	 */

	function __construct($obj, $sort_field, $sort_ord, $from, $parent_id, $object_params){
		parent::__construct($obj);
		$this->sort_field=$sort_field;
		$this->sort_ord=$sort_ord;
		$this->from=$from;
		$this->parent_id=$parent_id;
		$this->object_params=$object_params;
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Возвращает относительную ссылку для указанного действия. ДОЛЖНА использоваться для формирования ВСЕХ ссылок системы!
	 *
	 * @param string $action	действие для которого строится ссылка. "", если просто список
	 * @param mixed $info		дополнительная информация, в случае если передан параметр add_params с массивом, 
	 *                          то параметры просто вставляются в результирующий url
	 * @return string
	 */
	 
	public function get_url($action="", $info=""){
		$params=$this->get_params_hash($action, $info);
		
		$url = array();
		foreach($params as $key=>$value){
			if (!is_array($value)) {
					$url[]="{$key}=".urlencode($value);
			}
			else {
				foreach ($value as $value_key=>$value_value) {
					$url[]="{$key}[{$value_key}]=".urlencode($value_value);
				}
			}
		}
		return "index.php?" . join( '&', $url );
	}

	/**
	 * Возвращает html-код скрытых полей формы для указанного действия. ДОЛЖНА использоваться во ВСЕХ формах!
	 *
	 * @param string $action	действие на который будет уводить форма. "", если просто список
	 * @param mixed $info		дополнительная информация
	 * @return string
	 */
	public function get_hidden($action="", $info=""){
		$params=$this->get_params_hash($action, $info);
		foreach($params as $key=>$value){
			$hidden.="<input type=\"hidden\" name=\"{$key}\" value=\"".htmlspecialchars($value, ENT_QUOTES)."\">\n";
		}
		return $hidden;
	}

	/**
	 * Перенаправляет пользователя после обработки POST-формы. ДОЛЖНА использоваться для осуществления ВСЕХ редиректов системы!
	 *
	 * @param string $action	действие на который будет уводить редирект. "", если просто список
	 * @param mixed $info		дополнительная информация
	 */
	public function redirect($action="", $info=""){
		header("Location: ".$this->get_url($action, $info));
		exit();
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Формирует хэш параметров для get_url() и get_hidden()
	 *
	 * @param string $action	действие
	 * @param mixed $info		дополнительная информация
	 * @return array
	 * @todo Задокументировать варианты использования $action и $info
	 * @todo Разобраться, почему даже в сокращенном списке сюда поступают запросы на ссылку сортировки для целой кучи полей
	 * @todo Метод многократно вызывается из action_index( конкретно из get_index_ops ), что при большм числе записей приводит к заметным тормозам на стадии "records done" (bug 225, 226, 229). Число вызовов возрастает с каждым навешеным декоратором из-за добавления специфических операций. Более того, в разделах добавляюются еще две операции (bug 228). Частичное кэширование результатов работы метода немного ускоряет вывод, но сильно усложняет и без того непростой код.
	 */
	protected function get_params_hash($action, $info){
		// Ссылки в другую таблицу отрабатываем по особому
		if($action=="link"){
			$secondary_field=($info["link"]["secondary_field"] ? $info["link"]["secondary_field"] : $info["autoinc_name"]);
			$params["obj"]=$info["link"]["secondary_table"];
			$params["_f_".$secondary_field]=$info["id"];
		// m2m тоже нестандартный
		}elseif($action=="m2m"){
			$params["obj"]=$info["obj"];
			$params["action"]="m2m";
			$params["m2m"]=$info["m2m_name"];
			if($info["pk"]){
				$params=array_merge($params,$info["pk"]);
			}
		}else{ // Обычное формирование параметров
			if($_REQUEST["m2m"]){ // Работа через м2м, то есть от лица объекта - первичной таблицы, а не текущего
				$params["obj"]=$_REQUEST["obj"];
			}else{ // Нормальная работа
				$params["obj"]=$this->obj;
			}
			if($action){
				$params["action"]=$action;
			}
			if($info["pk"]){
				$params=array_merge($params, $info["pk"]);
			}
			
			// Фильтр (если это не фильтр и не шаг по иерархии)
			if(!$info["filter"] && (!isset($info["parent_id"]) || $info["m2m"])){
				if(isset($_REQUEST["search"])){
					$params["search"]=$_REQUEST["search"];
				}
				if(isset($_REQUEST["display_fields"])){
					$params["display_fields"]=$_REQUEST["display_fields"];
				}
				// Собственно сбор значений полей фильтрации
				if(sizeof(metadata::$objects[$this->obj]["fields"])){
					foreach(metadata::$objects[$this->obj]["fields"] as $field_name=>$field){
						if(($field["filter"] && !$info["short"]) || ($field["filter_short"] && $info["short"])){
							if(isset($_REQUEST["_f_".$field_name])){
								$params["_f_".$field_name]=$_REQUEST["_f_".$field_name];
							}
							if( $field["type"]=="date" || $field["type"]=="datetime" )
							{
								if(isset($_REQUEST["_f_".$field_name."_from"])){
									$params["_f_".$field_name."_from"]=$_REQUEST["_f_".$field_name."_from"];
								}
								if(isset($_REQUEST["_f_".$field_name."_to"])){
									$params["_f_".$field_name."_to"]=$_REQUEST["_f_".$field_name."_to"];
								}
							}
						}
					}
				}
				// Листалка (опять же кроме фильтра, а еще кроме сортировок)
				if(!$info["sort_field"] && !$info["no_from"]){
					$params["from"]=$this->from;
				}
				// Для иерархических таблиц переносим в параметры флаг "Все уровни"
				if(isset($_REQUEST["_f__ALL_LEVELS"])){
					$params["_f__ALL_LEVELS"]=$_REQUEST["_f__ALL_LEVELS"];
				}
			}
			// Сортировка
			if($info["sort_field"]){ // Вначале отрабатываем случай формирования ссылки сортировки
				$sort_field=$info["sort_field"];
				if($info["sort_field"]==$this->sort_field){
					$sort_ord=($this->sort_ord=="asc" ? "desc" : "asc");
				}else{
					$sort_ord="asc";
				}
			}elseif($this->sort_field){ // Потом проверяем, нет ли уже сейчас сортировки
				$sort_field=$this->sort_field;
				$sort_ord=$this->sort_ord;
			}
			if($sort_field){
				$params["sort_field"]=$sort_field;
				$params["sort_ord"]=$sort_ord;
			}
			// Группа параметров для организации м2м, если это требуется
			if($info["m2m"] || $info["filter"] && $_REQUEST["m2m"]){
				$params["m2m"]=$_REQUEST["m2m"];
				$obj = object::factory();
				$params=array_merge($params, $obj->primary_key->get_from_request());
				$obj -> __destruct();
				$params["action"]="m2m";
			}
		}
		
		// Родитель
		if ( $this -> parent_id !== '' && !$info['no_parent'] )
		{
			$params["_f_".metadata::$objects[$this->obj]["parent_field"]] =
				isset( $info["parent_id"] ) ? $info["parent_id"] : $this -> parent_id;
			
			if ( isset($info["from"]) )
				$params["from"]=((int)$info["from"]>=1 ? (int)$info["from"] : 1);
			if ( isset($info["prev_from"]) )
				$params["prev_from"]=((int)$info["prev_from"]>=1 ? (int)$info["prev_from"] : 1);
		}
		
		// Если нет особых пожеланий, параметры исходной страницы переносим без изменений
		if ( $_REQUEST['prev_params'] && !$info['clear_prev_params'])
			$params['prev_params'] = $_REQUEST['prev_params']; 
		
		// Запоминаем параметры исходной страницы
		if ( $info['save_params'] ) {
			$params['prev_params'] = base64_encode( serialize( array_merge( $_GET, $_POST ) ) );
		}
		
		// Восстанавливаем параметры исходной страницы
		if ( $info['restore_params'] && $_REQUEST['prev_params'] )
			$params = unserialize( base64_decode( $_REQUEST['prev_params'] ) );
		
		// Восстанавливаем дополнительные параметры
		if ( $info['add_params'] && is_array($info['add_params'])) 
			foreach ($info['add_params'] as $key=>$value) 
				$params[$key]=urlencode($value);
		
		// Дополняем хэш параметрами текущего системного объекта
		if ( $this -> object_params ) {
			foreach ( explode( '&', $this -> object_params ) as $object_param ) {
				list( $key, $value ) = explode( '=', $object_param ); $params[$key] = urlencode( $value );
			}
		}
		
		return $params;
	}
}
?>
