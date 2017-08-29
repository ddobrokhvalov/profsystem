<?php
/**
 * Класс декоратор таблиц "Переводимая таблица"
 *
 * @package		RBC_Contents_5_0
 * @subpackage	core
 * @copyright	Copyright (c) 2007 RBC SOFT
 */
class table_translate extends decorator
{
	/**
	 * После добавления записи в переводимую таблицу, заносим в таблицу TABLE_TRANSLATE значения переводов
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		// Вызываем родной метод добавления переводимой таблицы
		$inserted_id = $this -> inner_object -> exec_add( $this -> get_plain_raw_fields( $raw_fields, $prefix ), $prefix );
		
		// Если на предыдущем этапе не произошло исключения, вносим изменения в TABLE_TRANSLATE
		$this -> change_translate_table( $raw_fields, $prefix, $inserted_id );
		
		return $inserted_id;
	}
	
	/**
	 * После копирования записи в переводимую таблицу, заносим в таблицу TABLE_TRANSLATE значения переводов
	 *
	 * @see table::exec_copy()
	 */
	public function exec_copy( $raw_fields, $prefix, $pk )
	{
		// Вызываем родной метод добавления переводимой таблицы
		$inserted_id = $this -> inner_object -> exec_copy($raw_fields, $prefix, $pk );
		
		// Если на предыдущем этапе не произошло исключения, вносим изменения в TABLE_TRANSLATE
		$this -> change_translate_table( $raw_fields, $prefix, $inserted_id );
		
		return $inserted_id;
	}
	
	/**
	 * После изменения записи в переводимой таблице, заносим в таблицу TABLE_TRANSLATE значения переводов
	 *
	 * @see table::exec_change()
	 */
	public function exec_change( $raw_fields, $prefix, $pk )
	{
		// Вызываем родной метод обновления переводимой таблицы
		$this -> inner_object -> exec_change( $this -> get_plain_raw_fields( $raw_fields, $prefix ), $prefix, $pk );
		
		// Если на предыдущем этапе не произошло исключения, вносим изменения в TABLE_TRANSLATE
		$this -> change_translate_table( $raw_fields, $prefix, $pk[$this -> autoinc_name] );
	}

///////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Действие - карточка добавления
	 *
	 * @see table::action_add()
	 */
	public function action_add()
	{
		$this -> translate_lang_info();
		
		$this -> inner_object -> action_add();
	}

	/**
	 * Действие - карточка копирования
	 *
	 * @see table::action_change()
	 */
	public function action_copy()
	{
		$this -> translate_lang_info();
		
		$this -> inner_object -> action_copy();
	}
	
	/**
	 * Действие - карточка изменения
	 *
	 * @see table::action_change()
	 */
	public function action_change()
	{
		$this -> translate_lang_info();
		
		$this -> inner_object -> action_change();
	}

///////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Финализация удаления
	 *
	 * После удаления записи в переводимой таблице, удаляем из таблицы TABLE_TRANSLATE значения переводов
	 *
	 * @see table::ext_finalize_delete()
	 */
	public function ext_finalize_delete( $pk, $partial = false )
	{
		$this -> inner_object -> ext_finalize_delete( $pk, $partial );
		
		foreach ( metadata::$objects[$this -> obj]['fields'] as $field_name => $field_value )
			if ( metadata::$objects[$this -> obj]['fields'][$field_name]['translate'] )
				db::delete_record( 'TABLE_TRANSLATE', array( 'TE_OBJECT_ID' => $this -> te_object_id,
					'CONTENT_ID' => $pk[$this -> autoinc_name], 'FIELD_NAME' => $field_name ) );
	}

	/**
	 * Получение переводимых полей для переводимых таблиц
	 *
	 * Собираются значения на текущем языке интерфейса, а если их нет, то на дефолтном.
	 * Если же и на дефолтном нет, то будет пустая строка.
	 *
	 * @see table::ext_field_selection()
	 */
	public function ext_field_selection($field_name, $f_counter, $table_postfix=""){
		$interface_lang=$this->full_object->get_interface_lang();
		$main_interface_lang=db::sql_select("SELECT LANG_ID FROM LANG WHERE PRIORITY=1 AND IN_ADMIN=1");
		if(metadata::$objects[$this->obj]["fields"][$field_name]["translate"]){
			$return=array(
				"TT_{$f_counter}.VALUE",
				array("TT_{$f_counter}.VALUE LIKE :like_{$f_counter}"),
				array("LEFT JOIN TABLE_TRANSLATE TT_{$f_counter} ON
					TT_{$f_counter}.TE_OBJECT_ID=:tt_{$f_counter}_te_object_id AND
					(
						TT_{$f_counter}.LANG_ID=:tt_{$f_counter}_lang_id
						OR (
							TT_{$f_counter}.LANG_ID=:tt_{$f_counter}_main_lang_id AND NOT EXISTS (
								SELECT '1' FROM TABLE_TRANSLATE T WHERE
									T.LANG_ID=:tt_{$f_counter}_lang_id2 AND
									T.TE_OBJECT_ID=:tt_{$f_counter}_te_object_id2 AND
									T.CONTENT_ID={$this->obj}{$table_postfix}.{$this->autoinc_name} AND
									T.FIELD_NAME=:tt_{$f_counter}_field_name2
							)
						)
					) AND
					TT_{$f_counter}.CONTENT_ID={$this->obj}{$table_postfix}.{$this->autoinc_name} AND
					TT_{$f_counter}.FIELD_NAME=:tt_{$f_counter}_field_name 
				"),
				array(
					"tt_{$f_counter}_te_object_id"=>$this->te_object_id,
					"tt_{$f_counter}_te_object_id2"=>$this->te_object_id,
					"tt_{$f_counter}_lang_id"=>$interface_lang,
					"tt_{$f_counter}_lang_id2"=>$interface_lang,
					"tt_{$f_counter}_main_lang_id"=>$main_interface_lang[0]["LANG_ID"],
					"tt_{$f_counter}_field_name"=>$field_name,
					"tt_{$f_counter}_field_name2"=>$field_name,
				));
		}else{
			$return=array(array(), array(), array(), array());
		}
		return $return;
	}

	/**
	 * Для переводимых полей вместо обычного значения поля, возвращается массив переводов,
	 * проидексированный по языку (сделано для простоты использовния массива в шаблоне формы)
	 *
	 * @see table::get_change_record()
	 */
	public function get_change_record( $pk, $throw_exception = false )
	{
		$record = $this -> inner_object -> get_change_record( $pk, $throw_exception );
		
		foreach ( metadata::$objects[$this -> obj]['fields'] as $field_name => $field_value )
		{
			if ( $field_value['translate'] )
			{
				$translate_values = db::sql_select( '
					select * from TABLE_TRANSLATE
					where
						TE_OBJECT_ID = :te_object_id and CONTENT_ID = :content_id and
						FIELD_NAME = :field_name and LANG_ID in ( select LANG_ID from LANG where IN_ADMIN = 1 )',
					array(
						'te_object_id' => $this -> te_object_id, 'content_id' => $pk[$this -> autoinc_name], 'field_name' => $field_name ) );
				
				$record[$field_name] = array();
				foreach ( $translate_values as $translate_value )
					$record[$field_name][$translate_value['LANG_ID']] = $translate_value['VALUE'];
			}
		}
		
		return $record;
	}

///////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Метод заменяет значения переводимых полей в запросе на их значения на текущем языке интерфейса
	 * 
	 * @param array $raw_fields	Сырые данные, например, $_REQUEST
	 * @param string $prefix	Префикс, которым дополнены сырые данные, например, _form_ для формы
	 * @return array
	 */
	public function get_plain_raw_fields( $raw_fields, $prefix )
	{
		foreach ( metadata::$objects[$this -> obj]['fields'] as $field_name => $field_value )
			if ( metadata::$objects[$this -> obj]['fields'][$field_name]['translate'] )
				$raw_fields[$prefix.$field_name] = $raw_fields[$prefix.$field_name][$this -> get_interface_lang()];
		return $raw_fields;
	}

	/**
	 * Метод вносит изменения в таблицу TABLE_TRANSLATE
	 * 
	 * @param array $raw_fields	Сырые данные, например, $_REQUEST
	 * @param string $prefix	Префикс, которым дополнены сырые данные, например, _form_ для формы
	 * @param int $content_id	Идентификатор изменяемой записи в переводимой таблице
	 */
	public function change_translate_table( $raw_fields, $prefix, $content_id )
	{
		// Проходимся по всем полям переводимой таблицы
		foreach ( metadata::$objects[$this -> obj]['fields'] as $field_name => $field_value )
		{
			// Проверяем, является ли поле переводимым и является ли соответствующее ему содержимое запроса массивом
			if ( metadata::$objects[$this -> obj]['fields'][$field_name]['translate'] && is_array( $raw_fields[$prefix.$field_name] ) )
			{
				// Проходимся по проиндексированному по языку массиву переводов записи
				foreach ( $raw_fields[$prefix.$field_name] as $lang_id => $field_value )
				{
					// Собираем ключ для внесения изменений в таблицу TABLE_TRANSLATE
					$translate_pk = array( 'TE_OBJECT_ID' => $this -> te_object_id, 'LANG_ID' => $lang_id, 'CONTENT_ID' => $content_id, 'FIELD_NAME' => $field_name );
					// Подготавливаем данные перед их непосредственным внесением в таблицу
					$translate_value = $this -> field -> get_prepared( $field_value, metadata::$objects[$this->obj]['fields'][$field_name] );
					
					// В обязательном порядке проверяем наличие соответствующих записей в таблице
					if ( lib::is_record_exists( 'TABLE_TRANSLATE', $translate_pk ) )
						db::update_record( 'TABLE_TRANSLATE', array( 'VALUE' => $translate_value ), '', $translate_pk );
					else
						db::insert_record( 'TABLE_TRANSLATE', array_merge( $translate_pk, array( 'VALUE' => $translate_value ) ) );
				}
			}
		}
	}

	/**
	 * Метод дополняет метаданные переводимых полей информацией о языках системы
	 */
	public function translate_lang_info()
	{
		// Собираем информацию о языках системы
		$lang_info = db::sql_select( 'select * from LANG where IN_ADMIN = 1 order by PRIORITY DESC, TITLE' );
		
		// Дополняем полученную информации путями к картинкам языковых флажков
		foreach( $lang_info as $lang_key => $lang_item )
			$lang_info[$lang_key]['IMAGE'] = file_exists( params::$params['common_htdocs_server']['value'] . 'adm/img/lang/' . $lang_item['ROOT_DIR'] . '.gif' ) ? $lang_item['ROOT_DIR'] : 'default';
		
		// Изменяем метаданные переводимых полей
		foreach ( metadata::$objects[$this -> obj]['fields'] as $field_name => $field_value )
			if ( metadata::$objects[$this -> obj]['fields'][$field_name]['translate'] )
				metadata::$objects[$this -> obj]['fields'][$field_name]['translate'] = $lang_info;
	}
	
	/**
	* Реализовываем экспорт переводимых полей записи
	* @todo Перенести static в какой-нить суперглобальный параметр, эта инфа имхо везде нужна. Или в класс LANG
	* @todo А зачем мы везде передаем $_REQUEST? Да еще и со ссылкой. Например здесь это вообще не нужно. 
	* И зачем всегда нужен $list_mode? Почему не сделать по умолчанию?
	*/
	
	public function get_export_field_values($pk) {
		$ret_fields = $this->inner_object->get_export_field_values($pk);
		
		static $langs;
		if (!$langs) {
			$lang_obj = object::factory('LANG');
			$req=array();
			$langs = lib::array_reindex(db::sql_select( 'select * from LANG where IN_ADMIN = 1 order by PRIORITY DESC, TITLE' ), 'LANG_ID');
			$lang_obj->__destruct();
		}
		
		foreach (array_keys($ret_fields) as $field_name) {
			if ( metadata::$objects[$this->obj]['fields'][$field_name]['translate'] )
				$lang_data = metadata::$objects[$this->obj]['fields'][$field_name]['translate'];
				if (is_array($ret_fields[$field_name][0]['value'])) {
					$lang_value = $ret_fields[$field_name][0]['value'];
					$ret_fields[$field_name]=array();
					foreach ($langs as $lang_id=>$lang_data) {
						$value = $lang_value[$lang_id]?$lang_value[$lang_id]:'';
						if (metadata::$objects[$this->obj]['fields'][$field_name]['type']=='textarea') {
							$value = base64_decode($value);
							if ($import_data['info_data']['BASE64_CONTENT_ENCODING']!=params::$params['encoding']['value'])
								$value=iconv($import_data['info_data']['BASE64_CONTENT_ENCODING'], params::$params['encoding']['value'], $value);
						}
						$ret_fields[$field_name][] = array ('LANG_NAME'=>$langs[$lang_id]['ROOT_DIR'], 'value'=>$value);
					}
				}
		}
		
		return $ret_fields;
	}
	
	/**
	* Возвращает значение для конкретного поля для вставки в таблицу - унаследованный метод от table
	* В случае если поле переводимое то импортируем соответствующим образом
	* @param string $field_name Название поля
	* @param array $field_children Данные обо всех потомках данного поля массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return mixed Значение, которое вставляется в БД (еще возможно подменить, @see get_import_field_values)
	*/
	
	public function get_import_field_value($field_name, $field_children, &$import_data) {
		if (! metadata::$objects[$this->obj]['fields'][$field_name]['translate'] )
			return $this->inner_object->get_import_field_value($field_name, $field_children, $import_data);
			
		$return = array();
		
		if (is_array($field_children))
			while ($field_value = array_shift($field_children)) {
				$lang = $field_value['attributes']['LANG_NAME'];
				// если в админке такого языка нет, то пропускаем (раньше вызывали ошибку, сделано в процессе разработки инсталлятора)
				if (!$import_data['langs_in_admin'][$lang]) {
					continue;
				}
				
				$return[$import_data['langs_in_admin'][$lang]['LANG_ID']] = $this->inner_object->get_import_field_value($field_name, array($field_value), $import_data);
			}

		return $return;
	}
}
?>