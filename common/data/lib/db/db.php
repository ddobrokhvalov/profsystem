<?php
include_once(dirname(__FILE__)."/db_access.php");
/**
 * Класс синглтон-обертка над {@link object db_access db_access}
 *
 * Для обычной работы с БД должен использоваться этот класс.<br>
 * Дублирует публичные методы {@link object db_access db_access}. Объект {@link object db_access db_access} не нужно специально инстанцировать -
 * инстанцирование происходит автоматечески при первом вызове любого из методов db на базе настроек из 
 * {@link params}. У программиста остается возможность самостоятельного инстанцирования {@link object db_access db_access}, если, например,
 * нужно присоединиться к БД, не описанной в {@link params}.
 *
 * @package    RBC_Contents_5_0
 * @subpackage lib
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class db extends lib_abstract{

	/**
	 * Объект доступа к БД
	 * @var object db_access
	 */
	private static $db_access;

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Создает объект $db_access, если он еще не существует, затем его возвращает
	 *
	 * @return object db_access
	 */
	private static function singleton(){
		if(!is_object(self::$db_access)){
			self::$db_access=db_access::factory(params::$params["db_type"]["value"], params::$params["db_server"]["value"], params::$params["db_name"]["value"], params::$params["db_user"]["value"], params::$params["db_password"]["value"]);
		}
		return self::$db_access;
	}
	
	/**
	* Возвращает объект БД
	* @return object db_access
	*/
	public static function get_db_object () {
		return self::singleton();
	}

	/**
	 * Выборка данных из БД, либо исполнение любого произвольного запроса
	 *
	 * @see db_access::sql_select()
	 */
	public static function sql_select($query, $fields=array(), $special=array()){
		return self::singleton()->sql_select($query, $fields, $special);
	}

	/**
	 * Исполнение любого произвольного запроса
	 *
	 * @see db_access::sql_query()
	 */
	public static function sql_query($query, $fields=array(), $special=array()){
		return self::singleton()->sql_query($query, $fields, $special);
	}

	/**
	 * Помещение записи в таблицу
	 *
	 * @see db_access::insert_record()
	 */
	public static function insert_record($table, $fields=array(), $special=array()){
		return self::singleton()->insert_record($table, $fields, $special);
	}

	/**
	 * Получение последнего автоинкрементного идентификатора
	 *
	 * @see db_access::last_insert_id()
	 */
	public static function last_insert_id($sequence_name){
		return self::singleton()->last_insert_id($sequence_name);
	}

	/**
	 * Изменение записи или записей в таблице с ограничением по значениям некоторых полей
	 *
	 * @see db_access::update_record()
	 */
	public static function update_record($table, $fields=array(), $special=array(), $where=array()){
		return self::singleton()->update_record($table, $fields, $special, $where);
	}

	/**
	 * Удаление записи или записей из таблицы с ограничением по значениям некоторых полей
	 *
	 * @see db_access::delete_record()
	 */
	public static function delete_record($table, $where=array()){
		return self::singleton()->delete_record($table, $where);
	}

	/**
	 * Экранирование данных против SQL-инъекций
	 *
	 * @see db_access::db_quote()
	 */
	public static function db_quote($content){
		return self::singleton()->db_quote($content);
	}

	/**
	 * Базонезависимая конкатенация произвольного количества полей
	 *
	 * @see db_access::concat_clause()
	 */
	public static function concat_clause($fields, $delimiter){
		return self::singleton()->concat_clause($fields, $delimiter);
	}
	
	/*
	* Заменяет в результате функции поля $search_fields на $replace_fields
	* Стала необходима, из-за того что Oracle не поддерживает нескольких одинаковых полей в выборке вложенного запроса
	* @param array $sql_result Результат sql-запроса, возвращенный sql_select
	* @param mixed $search_field Название или массив названий исходных полей
	* @param mixed $replace_field Название или массив названий полей на которые нужно заменить исходные. 
	*
	* @return array
	*/
	
	public static function replace_field ($sql_result, $search_field, $replace_field)  {
		if (is_array($sql_result) && sizeof($sql_result)) 
			foreach ($sql_result as &$row) 
				if (is_array($search_field) && is_array($replace_field) && (sizeof($search_field)==sizeof($replace_field)))
					for ($i=0, $n=sizeof($search_field); $i<$n; $i++) {
						$row[$search_field[$i]] = $row[$replace_field[$i]];
						unset($row[$replace_field[$i]]);
					}
				elseif (is_scalar($search_field) && is_scalar($replace_field)) {
					$row[$search_field] = $row[$replace_field];
					unset($row[$replace_field]);
				}
		
		return $sql_result;
	}
}
?>