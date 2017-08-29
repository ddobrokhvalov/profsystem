<?php
/**
 * Класс для работы со значениями первичного ключа записи
 * 
 * Класс может получать первичный ключ из разных источников,
 * преобразовывать его к разным нужным видам - для GET-запроса, sql-запроса и др.
 * Стандартным хэшом для первичного ключа, которым обмениваются методы классов
 * является array("FIELD"=>"Значение")
 *
 * @package    RBC_Contents_5_0
 * @subpackage core
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class primary_key extends object_name{

	/**
	 * Название идентификатора
	 * @var string
	 */
	public $id;

	/**
	 * Ссылка на объект со всеми необходимыми декораторами
	 * 
	 * Если декораторов нет, то ссылается сам на себя.
	 * Необходим для придания возможности переопределения методов декоратором. Поэтому все методы класса
	 * должны вызываться не через $this->, а через $this->full_object->
	 * @var object	object
	 */
	public $full_object;

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Конструктор
	 *
	 * @see object::__construct()
	 */
	function __construct($obj, &$full_object=""){
		parent::__construct($obj);
		$this->id=$this->obj."_ID";
		// Ссылка на полный объект
		if(is_object($full_object)){
			$this->full_object=&$full_object;
		}else{
			$this->full_object=$this;
		}
	}

	/**
	 * Деструктор. Устраняет циклические ссылки объекта
	 *
	 * Должен вызываться явно, в противном случае объект будет оставаться в памяти - http://bugs.php.net/bug.php?id=33595
	 */
	function __destruct(){
		unset($this->full_object);
	}

	/**
	 * Возвращает служебные поля записи в виде перечня полей для кляузы SELECT
	 *
	 * @return string
	 */
	public function select_clause(){
		return $this->obj.'.'.implode (', '.$this->obj.'.', $this->full_object->get_select_clause_fields());
	}
	
	
	/**
	* Возвращает массив полей записи, которые необходимы для кляузы SELECT
	*
	* @return array
	*/
	
	public function get_select_clause_fields () {
		$res = array($this->id);
		if(metadata::$objects[$this->obj]["parent_field"])
			$res[] = metadata::$objects[$this->obj]["parent_field"];
		
		return $res;
	}
	
	/**
	* Входит ли передаваемое поле в список, выдаваемый для кляузы SELECT
	* @param string $field_name
	*
	* @return boolean
	*/
	
	public function is_field_in_select_clause($field_name) {
		return in_array($field_name, $this->full_object->get_select_clause_fields());
	}

	/**
	 * Возвращает идентификатор записи в виде перечня условий для кляузы WHERE (с учетом использования переменных привязки)
	 *
	 * @return string
	 */
	public function where_clause(){
		return $this->obj.".".$this->id."=:pk_id";
	}

	/**
	 * Возвращает переменные привязки для выборки записи по первичному ключу из стандартного хэша $pk
	 *
	 * @return array
	 */
	public function bind_array($pk){
		return array("pk_id"=>$pk[$this->id]);
	}

	/**
	 * Возвращает название автоинкрементного идентификатора записи
	 *
	 * @return string
	 */
	public function get_autoincrement_name(){
		return $this->id;
	}

	/**
	 * Возвращает идентификатор записи в виде стандартного хэша из самой записи, либо из $_REQUEST или других источников, если их подать на вход этому методу
	 *
	 * @param array $record		запись
	 * @return array
	 */
	public function get_from_record($record){
		return array($this->id=>$record[$this->id]);
	}

	/**
	 * Возвращает идентификатор записи в виде строки, используется как идентификатор в групповых операциях
	 *
	 * @param array $record		запись
	 * @return string
	 */
	public function get_string_from_record($record){
		return $record[$this->id];
	}

	/**
	 * Возвращает автоинкреметный идентификатор записи в виде числа из самой записи
	 *
	 * @param array $record		запись
	 * @return int
	 */
	public function get_id_from_record($record){
		return $record[$this->id];
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Возвращает идентификатор записи в виде стандартного хэша из $_REQUEST
	 *
	 * Вернет false, если в $_REQUEST неверные параметры в том числе если такой записи не существует.
	 * В случае неуспеха вызывает исключение, если $throw_exception
	 *
	 * @param boolean $throw_exception	бросать исключение, если такой записи нет
	 * @return mixed
	 */
	public function get_from_request($throw_exception=false){
		$pk=$this->full_object->get_from_record($_REQUEST);
		if(!$this->full_object->is_record_exists($pk, $throw_exception)){
			return false;
		}else{
			return $pk;
		}
	}

	/**
	 * Примерно тоже самое, что и {@link primary_key::get_from_request()} только для групповых операций
	 *
	 * Отличия:<br>
	 * 1. возвращает массив первичных ключей для групповой операции (может быть и пустым). Формат - array("pk"=>array("TABLE_ID"=>1), "value"=>1)<br>
	 * 2. не проверяет существование записей и является ли числом идентификатор, потому что такая проверка встроена в элементарные операции (как минимум так должно быть)
	 *
	 * @param boolean $full_set		возвращать все первичные ключи, а не только прочеканные
	 * @param boolean $id_only		возвращать не первичный ключ целиком, а только автоинкрементное поле - идентификатор
	 * @return array
	 */
	public function get_group_from_request($full_set=false, $id_only=false){
		$group_pks=array();
		foreach($_REQUEST as $name=>$value){
			if(preg_match("/^group_id_(.*)/", $name, $matches)){
				if($value==="1" || ($value==="0" && $full_set)){
					$group_pks[]=array("pk"=>array($this->id=>$matches[1]), "value"=>(int)$value);
				}
			}
		}
		return $group_pks;
	}

	/**
	 * Проверяет наличие записи в БД, в случае неуспеха вызывает исключение, если $throw_exception
	 *
	 * @param array $pk					первичный ключ записи
	 * @param boolean $throw_exception	бросать исключение, если такой записи нет
	 * @return boolean
	 */
	public function is_record_exists($pk, $throw_exception=false){
		$counter=db::sql_select("SELECT COUNT(*) AS COUNTER FROM {$this->obj} WHERE ".$this->full_object->where_clause(),$this->full_object->bind_array($pk));
		if($counter[0]["COUNTER"]>0){
			return true;
		}elseif($throw_exception){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_record_not_found"].": (".$this->full_object->pk_to_string($pk).")");
		}else{
			return false;
		}
	}

	/**
	 * Приводит первичный ключ к отображаемому формату для использования, например в сообщениях об ошибках
	 *
	 * @param array $pk					первичный ключ записи
	 * @return string
	 */
	public function pk_to_string($pk){
		return $pk[$this->id];
	}

	/**
	 * Проверяет наличие идентификаторов в БД, возвращает массив отсутствующих записей
	 * Если $throw_exception, в случае обнаружения отсутствующих записей вызывает исключение
	 *
	 * @param array $ids				список идентификаторов
	 * @param boolean $throw_exception	бросать исключение, если найдены отсутствующие записи
	 * @return array
	 */
	public function are_ids_exist($ids, $throw_exception=false){
		// Собираем IN
		$in=(is_array($ids) && count($ids)>0 ? join(", ", $ids) : 0);
		// Проверяем, чтобы нашлось ровно столько записей, сколько было передано идентификаторов
		$counter=db::sql_select("SELECT DISTINCT {$this->obj}.{$this->id} FROM {$this->obj} WHERE {$this->obj}.{$this->id} IN({$in})");
		$ne_ids=array_diff( $ids, array_keys( lib::array_reindex( $counter, $this->id ) ) );
		
		if ( count( $ne_ids ) && $throw_exception )
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_records_not_found"].": ".join(", ", $ne_ids));
		
		return $ne_ids;
	}

	/**
	 * Расширяет первичный ключ на выборку из зависимой таблицы
	 *
	 * @param string $secondary_table	название объекта вторичной таблицы
	 * @param array $pk					первичный ключ записи, из которой расширяется первичный ключ
	 * @return array
	 */
	public function ext_pk_for_children($secondary_table, $pk){
		// Для обычной таблицы расширение не требуется
		return array("", array());
	}

	/**
	 * Возвращает список названий полей, дополняющих первичный ключ
	 *
	 * @return array
	 */
	public function ext_pk_fields(){
		return array();
	}
}
?>