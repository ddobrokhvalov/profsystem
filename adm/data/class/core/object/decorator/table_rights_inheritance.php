<?php
/**
 * Класс декоратор таблиц с наследуемыми правами
 *
 * @package		RBC_Contents_5_0
 * @subpackage	core
 * @copyright	Copyright (c) 2007 RBC SOFT
 */
class table_rights_inheritance extends decorator
{
	/**
	 * Родительская таблица, от которой наследуются права
	 * 
	 * @var string
	 */
	public $rights_inheritance_table;
	
	/**
	 * Поле-указатель на запись в родительской таблице
	 * 
	 * @var string
	 */
	public $rights_inheritance_field;
	
	/**
	 * Недопущение более одного уровня наследования прав
	 *
	 * @todo В автотесте def-файла нужно проверять
	 * 		1. Наличие параметра rights_inheritance
	 *		2. Существование данного поля в таблице
	 *		3. Поле должно иметь тип select2
	 *		4. Поле должно быть обязательным
	 *		5. Существование родительской таблицы fk_table
	 *		6. Родительская таблица не должна иметь декоратор rights_inheritance
	 * 
	 * @see decorator::apply_inner_object
	 */
	public function apply_inner_object( &$inner_object )
	{
		parent::apply_inner_object( $inner_object );
		
		$this -> rights_inheritance_field = metadata::$objects[$this -> obj]['rights_inheritance'];
		$this -> rights_inheritance_table = metadata::$objects[$this -> obj]['fields'][$this -> rights_inheritance_field]['fk_table'];
	}
	
	/**
	 * Перед добавлением записи проверяем права на изменение записи в родительской таблице
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		$pk = array( $this -> rights_inheritance_field => $raw_fields[$prefix . $this -> rights_inheritance_field] );
		if ( $this -> decorators['lang'] )
			$pk += array( 'LANG_ID' => $raw_fields[$prefix . 'LANG_ID'] );
		
		// Проверка права на изменение записи в родительской таблице
		$this -> full_object -> is_permitted_to( 'add', $pk, true );
		
		// Собственно добавление
		return $this -> inner_object -> exec_add( $raw_fields, $prefix );
	}
	
	/**
	 * Принимаем решение в зависимости от прав на записи в родительской таблице
	 *
	 * @see object::is_permitted_to()
	 */
	public function is_permitted_to( $ep_type, $pk = '', $throw_exception = false )
	{
		// Главный администратор может все
		if ( $this -> auth -> is_main_admin )
		{
			$is_permitted = true;
		}
		else
		{
			$parent_object = object::factory( $this -> rights_inheritance_table );
			
			if ( $ep_type == 'view' || ( $ep_type == 'add' && !$pk[$this -> rights_inheritance_field] ) )
			{
				// Проверяем наличие права на просмотр у родительской таблицы
				$is_permitted = $parent_object -> is_permitted_to( 'view' );
			}
			else if ( in_array( $ep_type, array( 'add', 'change', 'delete', 'publish', 'unpublish', 'undo' ) ) )
			{
				if ( $ep_type == 'add' )
				{
					$pk_parent = array( $parent_object -> autoinc_name => $pk[$this -> rights_inheritance_field] );
				}
				else
				{
					$record = $this -> full_object -> get_change_record( $pk );
					$pk_parent = array( $parent_object -> autoinc_name => $record[$this -> rights_inheritance_field] );
				}
				
				if ( $parent_object -> decorators['lang'] )
				{
					if ( $pk['LANG_ID'] )
					{
						// Проверяем наличие права на изменение текущей языковой версии родительской записи
						$is_permitted = $parent_object -> is_permitted_to( 'change', $pk_parent + array( 'LANG_ID' => $pk['LANG_ID'] ) );
					}
					else
					{
						// Проверяем наличие права на изменение любой из языковых версий родительской записи
						foreach ( $parent_object -> get_other_langs( $pk_parent + array( 'LANG_ID' => 0 ) ) as $parent_record )
							$is_permitted |= $parent_object -> is_permitted_to( 'change', $pk_parent + array( 'LANG_ID' => $parent_record['LANG_ID'] ) );
					}
				}
				else
				{
					// Проверяем наличие права на изменение родительской записи
					$is_permitted = $parent_object -> is_permitted_to( 'change', $pk_parent );
				}
			}
			
			$parent_object -> __destruct();
		}
		
		if(!$is_permitted && $throw_exception){
			// Название записи в сообщении не выводится по соображениям защиты данных
			$pk_message=($pk[$this->autoinc_name] ? ": (".$this->primary_key->pk_to_string($pk).")" : "");
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_operation_not_permitted_".$ep_type].$pk_message);
		}
		
		return $is_permitted;
	}
	
	/**
	 * Принимаем решение в зависимости от прав на записи в родительской таблице
	 *
	 * @see object::is_permitted_to_mass()
	 */
	public function is_permitted_to_mass( $ep_type, $ids = array(), $throw_exception = false )
	{
		$not_allowed = array();
		
		if ( $this -> auth -> is_main_admin )
		{
			// Главный администратор может все
		}
		else
		{
			// Далаем запрос к текущей таблице
			$query = "select {$this -> autoinc_name}, {$this -> rights_inheritance_field} from {$this -> obj}
				where {$this -> autoinc_name} in ( " . lib::array_make_in( $ids ) . " ) " . $this -> full_object -> ext_index_query();
			$records = db::sql_select( $query, $this -> full_object -> ext_index_query_binds() );
			
			// Выделяем идентификаторы родительских записей
			$parent_ids = array_keys( lib::array_group( $records, $this -> rights_inheritance_field ) );
			
			// Проверяем наличие прав на изменение родительских записей
			$parent_object = object::factory( $this -> rights_inheritance_table );
			$parent_not_allowed = $parent_object -> is_permitted_to_mass( 'change', $parent_ids );
			$parent_object -> __destruct();
			
			// Составляем список запрещенных записей текущей таблицы
			foreach ( $records as $record )
				if ( in_array( $record[$this -> rights_inheritance_field], $parent_not_allowed ) )
					$not_allowed[] = $record[$this -> autoinc_name];
			
			$is_permitted = !(bool) count( $not_allowed );
			if( !$is_permitted && $throw_exception )
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_mass_operation_not_permitted_".$ep_type].": ".join(", ", $not_allowed));
		}
		
		return $not_allowed;
	}
	
	/**
	 * Возвращает кляузу для выборки только тех записей, которые можно смотреть данному пользователю
	 *
	 * @return array
	 */
	public function get_auth_clause_and_binds()
	{
		if ( $this -> auth -> is_main_admin )
		{
			return array( '', array() );
		}
		else
		{
			$parent_object = object::factory( $this -> rights_inheritance_table );
			$parent_autoinc_name = $parent_object -> autoinc_name;
			$parent_ext_index_query = $parent_object -> ext_index_query();
			$parent_ext_index_query_binds = $parent_object -> ext_index_query_binds();
			list( $parent_auth_clause, $parent_auth_binds ) = $parent_object -> get_auth_clause_and_binds();
			$parent_object -> __destruct();
			
			return array( " and {$this -> obj}.{$this -> autoinc_name} in (
				select {$this -> obj}.{$this -> autoinc_name}
				from {$this -> obj}, {$this -> rights_inheritance_table}
				where {$this -> rights_inheritance_table}.{$parent_object -> autoinc_name} = {$this -> obj}.{$this -> rights_inheritance_field}
					{$parent_auth_clause} {$parent_ext_index_query}
			)", $parent_auth_binds + $parent_ext_index_query_binds );
		}
	}
}
?>