<?php
/**
 * Класс декоратор таблиц "Внешние данные (external)"
 *
 * @package		RBC_Contents_5_0
 * @subpackage	core
 * @copyright	Copyright (c) 2007 RBC SOFT
 */
abstract class table_external extends decorator
{
	/**
	 * Массив соответствий полей внешней и внутренней таблицы
	 */
	protected $fields_relation = array();
	
	/**
	 * Массив соответствия внутренних идентификаторов версий внешним
	 */
	protected $version_hash = array();
	protected $version_hash_reverse = array();
	
	/**
	 * Массив соответствия внутренних идентификаторов языков внешним
	 */
	protected $lang_hash = array();
	protected $lang_hash_reverse = array();
	
	/**
	 * Массив соответствия внутренних идентификаторов состояний внешним
	 */
	protected $state_hash = array();
	protected $state_hash_reverse = array();
	
	////////////////////////////////////////////////////////////////////////
	
	/**
	 * Конструктор. Заполняем путь к файлу-источнику данных
	 */
	function __construct( &$full_object, $decorators )
	{
		parent::__construct( $full_object, $decorators );
		
		$this -> version_hash_reverse = array_flip( $this -> version_hash );
		$this -> lang_hash_reverse = array_flip( $this -> lang_hash );
		$this -> state_hash_reverse = array_flip( $this -> state_hash );
	}
	
	////////////////////////////////////////////////////////////////////////
	
	/**
	 * Добавление записи
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		$inserted_id = $this -> inner_object -> exec_add( $raw_fields, $prefix );
		
		db::update_record( $this -> obj, array( 'EXTERNAL_IS_CHANGED' => 1 ), '', array( $this -> autoinc_name => $inserted_id ) );
		
		return $inserted_id;
	}
	
	/**
	 * Изменение записи
	 *
	 * @see table::exec_change()
	 */
	public function exec_change( $raw_fields, $prefix, $pk )
	{
		$this -> inner_object -> exec_change( $raw_fields, $prefix, $pk );
		
		if ( $this -> decorators['version'] )
		{
			// Устанавливаем флаг изменения только на активную версию
			$record = $this -> full_object -> get_change_record( $pk, true );
			$pk = array_merge( $pk, array( 'VERSION' => $record['VERSION'] ) );
		}
		
		db::update_record( $this -> obj, array( 'EXTERNAL_IS_CHANGED' => 1 ), '', $pk );
	}
	
	/**
	 * Удаление записи
	 * 
	 * Собственно удаление записи происходит в методе exec_delete_external().
	 * Для таблиц с декоратором "workflow" метод вызывается через resolve_content().
	 * Действия выделены в отдельный метод так данная операция может вызываться по разному:
	 * непосредственно кнопкой "Удалить" из списка записей и через карточку наложения резолюций.
	 * В этом случае метод exec_delete() не используется и удаление происходит через exec_resolve().
	 * 
	 * Аналогичный прием используется при публикации, снятии с публикации, отмене изменений и переводе записи.
	 * 
	 * @see table::exec_delete()
	 */
	public function exec_delete( $pk, $partial = false )
	{
		if ( $this -> decorators['workflow'] )
			$this -> inner_object -> exec_delete( $pk, $partial );
		else
			$this -> exec_delete_external( $pk, $partial );
	}
	
	/**
	 * Собственно удаление записи
	 */
	public function exec_delete_external( $pk, $partial = false, $resolution = '' )
	{
		$pk_where = $this -> full_object -> primary_key -> where_clause();
		$pk_bind = $this -> full_object -> primary_key -> bind_array( $pk );
		
		$records = db::sql_select( "select * from {$this -> obj} where {$pk_where}", $pk_bind );
		
		foreach ( $records as $record_index => $record_item )
			$records[$record_index]['EXTERNAL_IS_DELETED'] = 1;
		
		if ( $this -> decorators['workflow'] )
			$this -> inner_object -> resolve_content( $pk, $resolution );
		else
			$this -> inner_object -> exec_delete( $pk, $partial );
		
		lib::inserter( $this -> obj, $records );
	}
	
	/**
	 * Публикация записи
	 *
	 * @see table_version::exec_publish()
	 */
	public function exec_publish( $pk )
	{
		if ( $this -> decorators['workflow'] )
			$this -> inner_object -> exec_publish( $pk );
		else
			$this -> exec_publish_external( $pk );
	}
	
	/**
	 * Собственно публикация записи
	 */
	public function exec_publish_external( $pk, $resolution = '' )
	{
		$versions = $this -> full_object -> get_versions( $pk );
		
		// Версия-источник не должна быть помечена, как удаленная
		if ( $versions[0]['EXTERNAL_IS_DELETED'] )
			throw new Exception( $this -> te_object_name . ' (publish): ' . metadata::$lang['lang_external_record_is_deleted'] . ': (' . $this -> primary_key -> pk_to_string( $pk ) . ')' );
		
		$pk_version = array_merge( $pk, array( 'VERSION' => $versions[0]['VERSION'] ? 0 : 1 ) );
		
		// Удаляем прошлый вариант версии-назначения
		db::delete_record( $this -> obj, $pk_version );
		
		if ( $this -> decorators['workflow'] )
			$this -> inner_object -> resolve_content( $pk, $resolution );
		else
			$this -> inner_object -> exec_publish( $pk );
		
		// Устанавливаем флаг изменения на версию-назначение
		db::update_record( $this -> obj, array( 'EXTERNAL_IS_CHANGED' => 1 ), '', $pk_version  );
	}
	
	/**
	 * Снятие записи с публикации
	 *
	 * @see table_version::exec_unpublish()
	 */
	public function exec_unpublish( $pk )
	{
		if ( $this -> decorators['workflow'] )
			$this -> inner_object -> exec_unpublish( $pk );
		else
			$this -> exec_unpublish_external( $pk );
	}
	
	/**
	 * Собственно снятие записи с публикации
	 */
	public function exec_unpublish_external( $pk, $resolution = '' )
	{
		$versions = $this -> full_object -> get_versions( $pk );
		
		// Сохраняем рабочую версию записи
		if ( $versions[0]['VERSION'] == 0 || $versions[1]['VERSION'] == 0 )
			$unpublish_version = $versions[$versions[0]['VERSION']];
		else
			$unpublish_version = null;
		
		// Рабочая версия не должна быть помечена, как удаленная
		if ( $unpublish_version && $unpublish_version['EXTERNAL_IS_DELETED'] )
			throw new Exception( $this -> te_object_name . ' (unpublish): ' . metadata::$lang['lang_external_record_is_deleted'] . ': (' . $this -> primary_key -> pk_to_string( $pk ) . ')' );
		
		if ( $this -> decorators['workflow'] )
			$this -> inner_object -> resolve_content( $pk, $resolution );
		else
			$this -> inner_object -> exec_unpublish( $pk );
		
		// Возвращаем в таблицу рабочую версию, помеченную как "удаленная"
		if ( $unpublish_version )
		{
			$unpublish_version['EXTERNAL_IS_DELETED'] = 1;
			db::insert_record( $this -> obj, $unpublish_version );
		}
		
		// Если до операции тестовой версии не существовало, меняем ее внешний ключ
		if ( $versions[0]['VERSION'] == 0 )
		{
			$pk_version = array_merge( $pk, array( 'VERSION' => 1 ) );
			db::update_record( $this -> obj, array( 'EXTERNAL_IS_CHANGED' => 1 ), '', $pk_version  );
		}
	}
	
	/**
	 * Отмена изменений
	 *
	 * @see table_version::exec_undo()
	 */
	public function exec_undo( $pk )
	{
		if ( $this -> decorators['workflow'] )
			$this -> inner_object -> exec_undo( $pk );
		else
			$this -> exec_undo_external( $pk );
	}
	
	/**
	 * Собственно отмена изменений
	 */
	public function exec_undo_external( $pk, $resolution = '' )
	{
		$versions = $this -> full_object -> get_versions( $pk );
		
		// Сохраняем рабочую версию записи
		if ( $versions[0]['VERSION'] == 0 || $versions[1]['VERSION'] == 0 )
			$undo_version = $versions[$versions[0]['VERSION']];
		else
			$undo_version = null;
		
		// Рабочая версия не должна быть помечена, как удаленная
		if ( $undo_version && $undo_version['EXTERNAL_IS_DELETED'] )
			throw new Exception( $this -> te_object_name . ' (undo): ' . metadata::$lang['lang_external_record_is_deleted'] . ': (' . $this -> primary_key -> pk_to_string( $pk ) . ')' );
		
		if ( $this -> decorators['workflow'] )
			$this -> inner_object -> resolve_content( $pk, $resolution );
		else
			$this -> inner_object -> exec_undo( $pk );
		
		// Устанавливаем флаг изменения на тестовую версию
		if ( $undo_version )
		{
			$pk_version = array_merge( $pk, array( 'VERSION' => 1 ) );
			db::update_record( $this -> obj, array( 'EXTERNAL_IS_CHANGED' => 1 ), '', $pk_version  );
		}
	}
	
	/**
	 * Перевод на другой язык
	 *
	 * @see table_lang::exec_translate()
	 */
	public function exec_translate( $pk, $lang_id )
	{
		if ( $this -> decorators['workflow'] )
			$this -> inner_object -> exec_translate( $pk, $lang_id );
		else
			$this -> exec_translate_external( $pk, $lang_id );
	}
	
	/**
	 * Собственно перевод на другой язык
	 */
	public function exec_translate_external( $pk, $lang_id, $resolution = '' )
	{
		$record = $this -> full_object -> get_change_record( $pk, true );
		
		// Переводимая версия не должна быть помечена, как удаленная
		if ( $record['EXTERNAL_IS_DELETED'] )
			throw new Exception( $this -> te_object_name . ' (translate): ' . metadata::$lang['lang_external_record_is_deleted'] . ': (' . $this -> primary_key -> pk_to_string( $pk ) . ')' );
		
		$pk_lang = array_merge( $pk, array( 'LANG_ID' => $lang_id ) );
		
		if ( $this -> decorators['version'] )
			$pk_lang = array_merge( $pk_lang, array( 'VERSION' => $record['VERSION'] ) );
		
		// Удаляем прошлый вариант версии-перевода
		db::delete_record( $this -> obj, $pk_lang );

		if ( $this -> decorators['workflow'] )
			$this -> inner_object -> resolve_content( $pk, $resolution );
		else
			$this -> inner_object -> exec_translate( $pk, $lang_id );
		
		// Устанавливаем флаг изменения на версию-перевод
		db::update_record( $this -> obj, array( 'EXTERNAL_IS_CHANGED' => 1 ), '', $pk_lang  );
	}
	
	/**
	 * Движение контента по цепочке публикаций
	 * 
	 * Метод переопредлен для реализации гибкого поведения декоратора
	 * в зависимости от типа операции при наложении резолюции с использованием exec_resolve()
	 * 
	 * @see table_workflow::resolve_content()
	 */
	public function resolve_content( $pk, $resolution )
	{
		// Наложение резолюции удаления
		if ( $resolution['WF_STATE2_VERSIONS'] == 'no_version' )
		{
			$this -> exec_delete_external( $pk, false, $resolution );
		}
		// Наложение резолюции перевода
		elseif ( $resolution['LANG_ID'] )
		{
			$this -> exec_translate_external( $pk, $resolution['LANG_ID'], $resolution );
		}
		// Наложение резолюции публикации
		elseif ( $resolution['WF_STATE1_VERSIONS'] == 'test_version' )
		{
			if ( $resolution['WF_STATE2_VERSIONS'] == 'two_versions' )
				$this -> exec_publish_external( $pk, $resolution );
		}
		elseif ( $resolution['WF_STATE1_VERSIONS'] == 'two_versions' )
		{
			if ( $resolution['WF_STATE2_VERSIONS'] == 'two_versions' )
			{
				// Наложение резолюции публикации изменений
				if ( $resolution['MAIN_VERSION'] == 1 ) 
					$this -> exec_publish_external( $pk, $resolution );
				// Наложение резолюции отмены изменений
				elseif ( $resolution['MAIN_VERSION'] == 0 )
					$this -> exec_undo_external( $pk, $resolution );
			}
			// Наложение резолюции снятия с публикации
			else
				$this -> exec_unpublish_external( $pk, $resolution );
		}
		// Наложение резолюции обычной резолюции
		else
			$this -> inner_object -> resolve_content( $pk, $resolution );
		
		if ( $this -> decorators['lang'] && $resolution['LANG_ID'] )
			$pk = array_merge( $pk, array( 'LANG_ID' => $resolution['LANG_ID'] ) );
		
		// Фиксируем изменение состояния
		db::update_record( $this -> obj, array( 'EXTERNAL_IS_CHANGED' => 1,
			'WF_STATE_ID' => $resolution['LAST_STATE_ID'] ), '', $pk );
	}
	
	////////////////////////////////////////////////////////////////////////
	
	/**
	 * Действие - импорт данных
	 */
	public function action_import()
	{
		if ( metadata::$objects[$this -> obj]['allow_quick_sync'] )
		{
			$this -> external_import();
			$this -> url -> redirect();
		}
	}
	
	/**
	 * Действие - экспорт данных
	 */
	public function action_export()
	{
		if ( metadata::$objects[$this -> obj]['allow_quick_sync'] && metadata::$objects[$this -> obj]['is_updatable'] )
		{
			$this -> external_export();
			$this -> url -> redirect();
		}
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Если требуется, выводим кнопку быстрой сихронизации
	 * 
	 * @see table::get_index_operations()
	 */
	public function get_index_operations()
	{
		$operations = $this -> inner_object -> get_index_operations();
		
		if ( metadata::$objects[$this -> obj]['allow_quick_sync'] )
			$operations['import'] = array( 'name' => 'import',
				'alt' => metadata::$lang['lang_external_import'], 'url' => $this -> url -> get_url( 'import' ) );
		if ( metadata::$objects[$this -> obj]['allow_quick_sync'] && metadata::$objects[$this -> obj]['is_updatable'] )
			$operations['export'] = array( 'name' => 'export',
				'alt' => metadata::$lang['lang_external_export'], 'url' => $this -> url -> get_url( 'export' ) );
		
		return $operations;
	}
		
	/**
	 * Если не оговорено особо, запрещаем все операции над записями
	 * 
	 * @see table::is_applied_to()
	 */
	public function is_applied_to( $operation_name, $throw_exception = true )
	{
		if ( !metadata::$objects[$this -> obj]['is_updatable'] )
		{
			if ( $throw_exception )
				throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_operation_not_applied'] . ': "' . $operation_name . '"' );
			
			return false;
		}
		
		return $this -> inner_object -> is_applied_to( $operation_name, $throw_exception );
	}
	
	/**
	 * Добавляем в списке колонку "Изменения"
	 *
	 * @see table::ext_index_header()
	 */
	public function ext_index_header( $mode )
	{
		return array_merge( $this -> inner_object -> ext_index_header( $mode ),
			array( 'changes' => array( 'title' => metadata::$lang['lang_external_changes'] ) ) );
	}
	
	/**
	 * Заполняем колонку "Изменения"
	 *
	 * @see table::get_index_records()
	 */
	public function get_index_records( &$request, $mode, $list_mode, $include = 0, $exclude = array() )
	{
		$records = $this -> inner_object -> get_index_records( $request, $mode, $list_mode, $include, $exclude );
		
		if ( count( $records ) > 0 && $mode != 'select2' )
		{
			// Под "удаленными" подразумеваем те записи, у которых помечены на удаление ВСЕ версии
			// Под "измененными" подразумеваем те записи, у которых измененена или удалена хотя бы одна из версий
			$records_modified = db::sql_select( "select {$this -> autoinc_name},
				min( EXTERNAL_IS_DELETED ) as EXTERNAL_IS_DELETED,
				sum( EXTERNAL_IS_CHANGED + EXTERNAL_IS_DELETED ) as EXTERNAL_IS_CHANGED
				from {$this -> obj} where {$this -> autoinc_name} in ( {$this -> index_records_in} )
				group by {$this -> autoinc_name}" );
			
			$records_modified = lib::array_reindex( $records_modified, $this -> autoinc_name );
			
			foreach ( $records as $record_index => $record_item )
			{
				if ( $records_modified[$record_item[$this -> autoinc_name]]['EXTERNAL_IS_DELETED'] )
					$records[$record_index]['changes'] = metadata::$lang['lang_external_deleted'];
				else if ( $records_modified[$record_item[$this -> autoinc_name]]['EXTERNAL_IS_CHANGED'] )
					$records[$record_index]['changes'] = metadata::$lang['lang_external_changed'];
			}
		}
		
		return $records;
	}
	
	/**
	 * Запрет любых операций над удаленными записями
	 *
	 * @see object::is_permitted_to()
	 */
	public function is_permitted_to( $ep_type, $pk = '', $throw_exception = false )
	{
		$is_permitted = $this -> inner_object -> is_permitted_to( $ep_type, $pk, $throw_exception );
		
		if ( $pk != '' )
		{
			// Под "удаленными" подразумеваем те записи, у которых помечены на удаление ВСЕ версии
			$record_deleted = db::sql_select( "select {$this -> autoinc_name},
				min( EXTERNAL_IS_DELETED ) as EXTERNAL_IS_DELETED
				from {$this -> obj}	where {$this -> autoinc_name} = :{$this -> autoinc_name}
				group by {$this -> autoinc_name}",
				array( $this -> autoinc_name => $pk[$this -> autoinc_name] ) );
			
			$record_deleted = $record_deleted[0]['EXTERNAL_IS_DELETED'];
			
			if ( $record_deleted && $throw_exception )
			{
				$pk_message = $pk[$this -> autoinc_name] ? ': (' . $this -> primary_key -> pk_to_string( $pk ) . ')' : '';
				throw new Exception( $this -> te_object_name . ' (' . $ep_type . '): ' . metadata::$lang['lang_external_record_is_deleted'] . $pk_message );
			}
			
			$is_permitted &= !$record_deleted;
		}
		
		return (boolean) $is_permitted;
	}
	
	/**
	 * Массовый запрет любых операций над удаленными записями
	 *
	 * @see object::is_permitted_to_mass()
	 */
	public function is_permitted_to_mass( $ep_type, $ids = array(), $throw_exception = false, $additional_info = array() )
	{
		$not_allowed = $this -> inner_object -> is_permitted_to_mass( $ep_type, $ids, $throw_exception, $additional_info );
		
		$in = ( is_array( $ids ) && count( $ids ) > 0 ) ? join( ', ', $ids ) : 0;
		
		// Под "удаленными" подразумеваем те записи, у которых помечены на удаление ВСЕ версии
		$records_deleted = db::sql_select( "select {$this -> autoinc_name},
			min( EXTERNAL_IS_DELETED ) as EXTERNAL_IS_DELETED
			from {$this -> obj}	where {$this -> autoinc_name} in ( {$in} )
			group by {$this -> autoinc_name}" );
		
		// Запрещаем любые операции над "удаленными" записями
		foreach ( $records_deleted as $record_item )
			if ( $record_item['EXTERNAL_IS_DELETED'] && !in_array( $record_item[$this -> autoinc_name], $not_allowed ) )
				$not_allowed[] = $record_item[$this -> autoinc_name];
		
		if( count( $not_allowed ) > 0 && $throw_exception )
			throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_uncheckable_mass_operation_not_permitted_' . $ep_type] . ': ' . join( ', ', $not_allowed ) );
		
		return $not_allowed;
	}
	
	/**
	 * Модификация списка операций для удаленных записей
	 *
	 * @see table::get_index_ops()
	 */
	public function get_index_ops( $record )
	{
		$ops = $this -> inner_object -> get_index_ops( $record );
		$pk = $this -> primary_key -> get_from_record( $record );
		
		// Если нужно, добавляем ссылку на операцию просмотра
		if ( metadata::$objects[$this -> obj]['view'] )
			$ops['_ops'][] = array( 'name' => 'view', 'alt' => metadata::$lang['lang_view'],
				'url' => $this -> url -> get_url( 'view', array( 'pk' => $pk ) ) );
		
		// Для записей, удаленных частично, скрываем ссылки на некоторые операции
		if ( $record['EXTERNAL_IS_DELETED'] )
		{
			if ( is_array( $ops['_ops'] ) )
				foreach ( $ops['_ops'] as $op_index => $op_item )
					if ( $op_item['name'] == 'delete' || $op_item['name'] == 'translate' )
						unset( $ops['_ops'][$op_index] );
			unset( $ops['resolution'] );
		}
		
		return $ops;
	}
	
	/**
	 * Добавляем фильтр по измененным и удаленным записям
	 *
	 * @see table::get_index_query_components()
	 */
	public function get_index_query_components( &$request, $mode, $list_mode )
	{
		$components = $this -> inner_object -> get_index_query_components( $request, $mode, $list_mode );
		
		switch ( $request['_f_MODIFY'] )
		{
			case 'changed':
				$components[2] .= " and {$this -> obj}.EXTERNAL_IS_CHANGED = 1 && EXTERNAL_IS_DELETED <> 1"; break;
			case 'deleted':
				$components[2] .= " and {$this -> obj}.EXTERNAL_IS_DELETED = 1"; break;
		}
		
		return $components;
	}
	
	//////// Методы для работы с внутренним представлением внешних данных //////
	
	/**
	 * Упаковка идентификатора записи внешней таблицы во внутреннее представление
	 *
	 * @param string $pk					ключ внешней таблицы
	 * @return string
	 */
	public abstract function pack_external_key( $pk );
	
	/**
	 * Распаковка идентификатора записи внешней таблицы из внутреннего представления
	 *
	 * @param string $external_primary_key	внутреннее представление идентификатора внешней таблицы
	 * @return array
	 */
	public abstract function unpack_external_key( $external_primary_key );
	
	/**
	 * Преобразование значения записи внешней таблицы во внутреннее представление
	 *
	 * @param string $field_name				название поля внешней таблицы
	 * @param string $field_value				значение поля внешней таблицы
	 * @return string
	 */
	public function pack_external_field( $field_name, $field_value )
	{
		if ( $this -> fields_relation[$field_name] == 'VERSION' && $this -> decorators['version'] )
			return $this -> version_hash_reverse[$field_value];
		if ( $this -> fields_relation[$field_name] == 'LANG_ID' && $this -> decorators['lang'] )
			return $this -> lang_hash_reverse[$field_value];
		if ( $this -> fields_relation[$field_name] == 'WF_STATE_ID' && $this -> decorators['workflow'] )
			return $this -> state_hash_reverse[$field_value];
		
		return $field_value;
	}
	
	/**
	 * Преобразование значения записи внешней таблицы из внутреннего представления
	 *
	 * @param string $field_name				название поля внутренней таблицы
	 * @param string $field_value				значение поля внутренней таблицы
	 * @return string
	 */
	public function unpack_external_field( $field_name, $field_value )
	{
		if ( $field_name == 'VERSION' && $this -> decorators['version'] )
			return $this -> version_hash[$field_value];
		if ( $field_name == 'LANG_ID' && $this -> decorators['lang'] )
			return $this -> lang_hash[$field_value];
		if ( $field_name == 'WF_STATE_ID' && $this -> decorators['workflow'] )
			return $this -> state_hash[$field_value];
		
		return $field_value;
	}
	
	///////////////////////// Методы импорта-экспорта //////////////////////////
	
	/**
	 * Получение данных из внешнего источника
	 */
	public function external_import()
	{
		$this -> full_object -> is_permitted_to( 'view', '', true );
		
		$this -> external_open();
		
		// Определяем имя поля, ответственного за передачу информации о характере изменения данных
		$modified_field = array_search( 'EXTERNAL_IS_MODIFIED', $this -> fields_relation );
		
		while ( ( $row = $this -> external_read() ) !== false )
		{
			// Пропускаем пустые строки
			if ( join( '', $row ) === '' ) continue;
			
			// Упаковываем первичный ключ внешней таблицы
			$external_primary_key = $this -> pack_external_key( $row );
			
			if ( $external_primary_key == '' ) continue;
			
			// Формирование записи для вставки во внутреннюю таблицу
			$internal_record = array( 'EXTERNAL_PRIMARY_KEY' => $external_primary_key );
			foreach ( $this -> fields_relation as $external_field => $internal_field )
				if ( $external_field != $modified_field )
					$internal_record[$internal_field] = $this -> pack_external_field( $external_field, $row[$external_field] );
			
			// Считываем информацию о характере изменения данных
			$modified = ( $modified_field !== false ) ? $row[$modified_field] : '';
			
			// Формирование первичного ключа для идентификации записи во внутреннюю таблицу
			if ( preg_match( '/^\d+$/', $modified ) )
				$internal_primary_key = array( $this -> autoinc_name => $modified );
			else
				$internal_primary_key = array( 'EXTERNAL_PRIMARY_KEY' => $external_primary_key );
			
			// Расширение первичного ключа с учетом имеющихся декораторов
			if ( $this -> decorators['version'] )
				$internal_primary_key += array( 'VERSION' => $internal_record['VERSION'] );
			if ( $this -> decorators['lang'] )
				$internal_primary_key += array( 'LANG_ID' => $internal_record['LANG_ID'] );
			
			// Подготавливаем параметры sql-запроса для поиска записи во внутренней таблицы
			$internal_clause = array(); $internal_binds = array();
			foreach ( $internal_primary_key as $key_name => $key_value )
			{
				$internal_clause[] = $key_name . ' = :' . $key_name;
				$internal_binds[$key_name] = $key_value;
			}
			
			if ( $modified == 'deleted' )
			{
				// Удаляем запись во внутренней таблице
				db::delete_record( $this -> obj, $internal_binds );
			}
			else if ( $modified == 'changed' || preg_match( '/^\d+$/', $modified ) )
			{
				$internal_record['EXTERNAL_IS_CHANGED'] = $internal_record['EXTERNAL_IS_DELETED'] = 0;
				
				// Фиксируем изменения записи во внутренней таблице
				db::update_record( $this -> obj, $internal_record, '', $internal_binds );
			}
			else
			{
				// Ищем запись во внутренней таблице по полному совпадению первичного ключа
				$exists_record = db::sql_select( 'select * from ' . $this -> obj . '
					where ' . join( ' and ', $internal_clause ), $internal_binds );
				
				if ( count( $exists_record ) == 0 )
				{
					// Ищем запись во внутренней таблице по частичному совпадению первичного ключа
					$exists_record = db::sql_select( 'select * from ' . $this -> obj . '
						where EXTERNAL_PRIMARY_KEY = :external_primary_key', array( 'external_primary_key' => $external_primary_key ) );
					
					$internal_primary_key = ( count( $exists_record ) == 0 ) ? array() :
						array( $this -> autoinc_name => $exists_record[0][$this -> autoinc_name] );
					
					// Вставляем новую запись во внутреннюю таблицу
					db::insert_record( $this -> obj, $internal_record + $internal_primary_key );
				}
				else if ( !$exists_record[0]['EXTERNAL_IS_CHANGED'] && !$exists_record[0]['EXTERNAL_IS_DELETED'] )
				{
					// Обновляем существующую запись во внутренней таблице
					db::update_record( $this -> obj, $internal_record, '', $internal_binds );
				}
			}
		}
		
		$this -> external_close();
	}
	
	/**
	 * Отсылка данных во внешний источник
	 */
	public function external_export()
	{
		$this -> full_object -> is_permitted_to( 'view', '', true );
		
		if ( !metadata::$objects[$this -> obj]['is_updatable'] )
			throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_external_export_unupdatable_table'] );
		
		$table_rows = db::sql_select( 'select * from ' . $this -> obj . '
			where EXTERNAL_IS_DELETED = 1 or EXTERNAL_IS_CHANGED = 1' );
		
		$this -> external_open( 'w' );
		
		foreach ( $table_rows as $row )
		{
			// Формирование строки для записи во внешнюю таблицу
			$external_record = $this -> unpack_external_key( $row['EXTERNAL_PRIMARY_KEY'] );
			
			foreach ( $this -> fields_relation as $external_field => $internal_field )
			{
				if ( $internal_field == 'EXTERNAL_IS_MODIFIED' )
				{
					if ( $row['EXTERNAL_IS_CHANGED'] ) $row[$internal_field] = 'changed';
					if ( $row['EXTERNAL_IS_DELETED'] ) $row[$internal_field] = 'deleted';
					if ( $row['EXTERNAL_PRIMARY_KEY'] == '' )
						$row[$internal_field] = $row[$this -> autoinc_name];
				}
				
				$external_record[$external_field] = $this -> unpack_external_field( $internal_field, $row[$internal_field] );
			}
			
			// Вставляем новую запись во внутреннюю таблицу
			$this -> external_write( $external_record );
		}
		
		$this -> external_close();
	}
	
	/**
	 * Открытие внешнего источника данных
	 */
	public abstract function external_open( $mode = 'r' );
	
	/**
	 * Чтение строки из внешнего источника данных
	 */
	public abstract function external_read();
	
	/**
	 * Запись строки во внешний источник данных
	 */
	public abstract function external_write( $row );
	
	/**
	 * Закрытие внешнего источника данных
	 */
	public abstract function external_close();
}
?>
