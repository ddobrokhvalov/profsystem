<?php
/**
 * Класс декоратор первичных ключей "Язык в первичном ключе"
 *
 * @package    RBC_Contents_5_0
 * @subpackage core
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class primary_key_lang extends decorator{
	
	/**
	 * Возвращает служебные поля записи для кляузы SELECT
	 *
	 * @see primary_key::get_select_clause_fields()
	 */
	
	public function get_select_clause_fields () {
		return array_merge($this->inner_object->get_select_clause_fields (), array('LANG_ID'));
	}

	/**
	 * Возвращает идентификатор записи в виде перечня условий для кляузы WHERE (с учетом использования переменных привязки)
	 *
	 * @see primary_key::where_clause()
	 */
	public function where_clause(){
		return $this->inner_object->where_clause()." AND {$this->obj}.LANG_ID=:pk_lang_id";
	}

	/**
	 * Возвращает переменные привязки для выборки записи по первичному ключу из стандартного хэша $pk
	 *
	 * @see primary_key::bind_array()
	 */
	public function bind_array($pk){
		return array_merge($this->inner_object->bind_array($pk), array("pk_lang_id"=>$pk["LANG_ID"]));
	}

	/**
	 * Возвращает идентификатор записи в виде стандартного хэша из самой записи
	 *
	 * @see primary_key::get_from_record()
	 */
	public function get_from_record($record){
		return array_merge($this->inner_object->get_from_record($record), array("LANG_ID"=>$record["LANG_ID"]));
	}

	/**
	 * Возвращает идентификатор записи в виде строки, используется как идентификатор в групповых операциях
	 *
	 * @see primary_key::get_string_from_record()
	 */
	public function get_string_from_record($record){
		return $this->inner_object->get_string_from_record($record)."_".$record["LANG_ID"];
	}

	/**
	 * Дополняет первичные ключи для групповых операций языками
	 *
	 * @see primary_key::get_group_from_request()
	 */
	public function get_group_from_request($full_set=false, $id_only=false){
		$group_pks=$this->inner_object->get_group_from_request($full_set);
		if(!$id_only){ // В противном случае достаточно того, что сделал базовый класс
			foreach($group_pks as $k=>$pk){
				$splitted_pk=split("_", $pk["pk"][$this->id]);
				$group_pks[$k]["pk"][$this->id]=$splitted_pk[0];
				$group_pks[$k]["pk"]["LANG_ID"]=$splitted_pk[1];
			}
		}
		return $group_pks;
	}

	/**
	 * Приводит первичный ключ к отображаемому формату для использования, например в сообщениях об ошибках
	 *
	 * @param array $pk					первичный ключ записи
	 * @return string
	 */
	public function pk_to_string($pk){
		return $pk[$this->id].", ".$pk["LANG_ID"];
	}

	/**
	 * Расширяет первичный ключ на выборку из зависимой таблицы
	 *
	 * @param string $secondary_table	название объекта вторичной таблицы
	 * @param array $pk					первичный ключ записи, из которой расширяется первичный ключ
	 * @return array
	 */
	public function ext_pk_for_children($secondary_table, $pk){
		// Если в зависимой таблице тоже есть декоратор "язык", то переносим язык на нее
		list($clause, $binds)=$this->inner_object->ext_pk_for_children($secondary_table, $pk);
		if(metadata::$objects[$secondary_table]["decorators"]["lang"]){
			$clause.=" AND {$secondary_table}.LANG_ID=:LANG_ID";
			$binds+=array("LANG_ID"=>$pk["LANG_ID"]);
		}
		return array($clause, $binds);
	}

	/**
	 * Возвращает список названий полей, дополняющих первичный ключ
	 *
	 * @return array
	 */
	public function ext_pk_fields(){
		return array_merge($this->inner_object->ext_pk_fields(), array("LANG_ID"));
	}
}
?>