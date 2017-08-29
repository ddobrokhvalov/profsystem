<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Альтернативное меню"
 *
 * @package    RBC_Contents_5_0
 * @subpackage app
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class alt_menu extends table_workflow
{
	/**
	 * В отличие от базового метода осуществляется дополнительные проверки
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		// Одно из полей 'Ссылка на раздел сайта' или 'Ссылка на внешний источник' должно быть заполнено
		if ( $raw_fields[$prefix.'PAGE_ID'] === '' && $raw_fields[$prefix.'URL'] === '' )
			throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_adding'] . ': ' . metadata::$lang['lang_alt_menu_mandatory_fields'] );
		
		// Поверка уникальности записи с указанной ссылкой на раздел сайта
		if ( $raw_fields[$prefix.'PAGE_ID'] !== '' )
			$this -> check_unique_item( $raw_fields[$prefix.'INF_BLOCK_ID'], $raw_fields[$prefix.'PAGE_ID'], $raw_fields[$prefix.'PARENT_ID'] );
		
		return parent::exec_add( $raw_fields, $prefix );
	}
	
	/**
	 * В отличие от базового метода осуществляется дополнительные проверки
	 *
	 * @see table::exec_change()
	 */
	public function exec_change( $raw_fields, $prefix, $pk )
	{
		// Одно из полей 'Ссылка на раздел сайта' или 'Ссылка на внешний источник' должно быть заполнено
		if ( $raw_fields[$prefix.'PAGE_ID'] === '' && $raw_fields[$prefix.'URL'] === '' )
			throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_alt_menu_mandatory_fields'] . ': "' . $this -> full_object -> get_record_title( $pk ) . '" (' . $this -> primary_key -> pk_to_string( $pk ) . ')' );
		
		// Проверка уникальности записи с указанной ссылкой на раздел сайта
		if ( $raw_fields[$prefix.'PAGE_ID'] !== '' )
		{
			$inf_block_list = $this -> get_alt_menu_inf_blocks( $pk['ALT_MENU_ID'] );
			for ( $i = 0; $i < count( $inf_block_list ); $i++ )
				$this -> check_unique_item( $inf_block_list[$i]['INF_BLOCK_ID'], $raw_fields[$prefix.'PAGE_ID'], $raw_fields[$prefix.'PARENT_ID'], $pk['ALT_MENU_ID'] );
		}
		
		parent::exec_change( $raw_fields, $prefix, $pk );
	}
	
	/**
	 * Метод осуществляет проверку уникальности записи с указанной ссылкой на раздел сайта
	 */
	protected function check_unique_item( $inf_block_id, $page_id, $parent_id, $alt_menu_id = '', $version = 1 )
	{
		// Поверка на уникальность запись с указанной ссылкой на раздел сайта
		$presents_page_id = db::sql_select('
			select count(*) as PAGE_COUNT
			from ALT_MENU, CONTENT_MAP
			where ALT_MENU.ALT_MENU_ID = CONTENT_MAP.CONTENT_ID
			and CONTENT_MAP.INF_BLOCK_ID = :inf_block_id
			and ALT_MENU.PAGE_ID = :page_id
			and ALT_MENU.PARENT_ID = :parent_id
			and ALT_MENU.VERSION = :version
			and CONTENT_MAP.CONTENT_ID <> :alt_menu_id', array(
				'inf_block_id' => intval( $inf_block_id ),
				'page_id' => intval( $page_id ),
				'parent_id' => intval( $parent_id ),
				'alt_menu_id' => intval( $alt_menu_id ),
				'version' => intval( $version ) ) );
		
		if ( $presents_page_id[0]['PAGE_COUNT'] )
		{
			if ( $alt_menu_id )
				throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_alt_menu_unique_page_id'] . ': "' . $this -> full_object -> get_record_title( array( 'ALT_MENU_ID' => $alt_menu_id ) ) . '" (' . $this -> primary_key -> pk_to_string( array( 'ALT_MENU_ID' => $alt_menu_id ) ) . ')' );
			else
				throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_adding'] . ': ' . metadata::$lang['lang_alt_menu_unique_page_id'] );
		}
	}
	
	/**
	 * Метод возвращает массив информационных блоков, содержащих данный пункт альтернативного меню
	 *
	 * @return array
	 */
	protected function get_alt_menu_inf_blocks( $alt_menu_id = '' )
	{
		return db::sql_select('
			select CONTENT_MAP.INF_BLOCK_ID
			from PRG_MODULE, INF_BLOCK, CONTENT_MAP
			where PRG_MODULE.SYSTEM_NAME = :system_name
			and INF_BLOCK.PRG_MODULE_ID = PRG_MODULE.PRG_MODULE_ID
			and CONTENT_MAP.INF_BLOCK_ID = INF_BLOCK.INF_BLOCK_ID
			and CONTENT_MAP.CONTENT_ID = :alt_menu_id',	array(
				'system_name' => 'ALT_MENU', 'alt_menu_id' => intval( $alt_menu_id ) ) );
	}
	
	/**
	 * Добавляем в конец таблицы скрипт с дополнительным обработчиком формы
	 *
	 * @see table::html_card()
	 */
	public function html_card($mode, &$request){
		list( $title, $html ) = parent::html_card($mode, $request);
		
		$lang_alt_menu_mandatory_fields = addslashes( metadata::$lang['lang_alt_menu_mandatory_fields'] );
		
		$html .= <<<HTML
<script type="text/javascript">
	CheckForm.validate_ext = function()
	{
		var oPageId = this.oForm['_form_PAGE_ID'];
		var oUrl = this.oForm['_form_URL'];
		
		if ( oPageId && oUrl && oPageId.value == '' && oUrl.value == '' )
		{
			alert( Dictionary.translate( '{$lang_alt_menu_mandatory_fields}' ) );
			try { oPageId.focus() } catch (e) {};
			return false;
		}
		
		return true;
	}
</script>
HTML;
		return array( $title, $html );
	}
}
?>