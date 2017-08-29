<?php
/**
* Класс для управления тегами
*
* @package		RBC_Contents_5_0
* @subpackage te
* @copyright	Copyright (c) 2007 RBC SOFT
*/
class tag extends table
{
	/**
	 * Заполнение полей SYSTEM_NAME и NUM_LINKS
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		$tag_id = parent::exec_add( $raw_fields, $prefix );
		
		db::update_record( $this -> obj, array( 'SYSTEM_NAME' => mb_strtolower( $raw_fields[$prefix.'TITLE'], params::$params["encoding"]["value"] ), 'NUM_LINKS' => 0 ), '', array( 'TAG_ID' => $tag_id ) );
	}
	
	/**
	 * Заполнение поля SYSTEM_NAME
	 *
	 * @see table::exec_change()
	 */
	public function exec_change( $raw_fields, $prefix, $pk )
	{
		parent::exec_change( $raw_fields, $prefix, $pk );
		
		db::update_record( $this -> obj, array( 'SYSTEM_NAME' => mb_strtolower( $raw_fields[$prefix.'TITLE'], params::$params["encoding"]["value"] ) ), '', $pk );
	}
	
	/**
	 * Скрываем поля SYSTEM_NAME и NUM_LINKS
	 *
	 * @see table::action_change()
	 */
	public function action_add()
	{
		metadata::$objects[$this->obj]['fields']['SYSTEM_NAME']['disabled'] = false;
		metadata::$objects[$this->obj]['fields']['NUM_LINKS']['disabled'] = false;
		
		parent::action_add();
	}}
?>
