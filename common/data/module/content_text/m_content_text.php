<?php
/**
 * Модуль "Простой текст"
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class m_content_text extends module
{
	/**
	* Объект шаблонизатора модуля
	* 
	* @todo Не перенести ли его в module.php? И не создавать ли его в init()?
	*/
	protected $tpl;
	
	/**
	* Диспетчер модуля
	*/
	protected function content_init()
	{
		// Инициализация шаблонизатора
		$this -> tpl = new smarty_ee_module( $this );
		
		$query_item = 'select '.$this -> module_table.'.* from '.$this -> get_module_from().' where '.$this -> get_module_where();
		$content_item = db::sql_select( $query_item, $this -> get_module_binds() );
		
		if ( count( $content_item ) )
		{
			$this -> title = $content_item[0]['TITLE'];
			
			if( $this -> view_param['header'] == 'no' )
				$title = $content_item[0]['TITLE'] = '';
			
			$tag_list = $this -> get_tag_list( $content_item[0]['CONTENT_TEXT_ID'] );
			$content_item[0]['TAG_LIST'] = $tag_list[$content_item[0]['CONTENT_TEXT_ID']];
			
			$this -> tpl -> assign( $content_item[0] );
		}
		
		// Выводим в случае необходимости ссылку на страницу с версией для печати
		if ( $this -> view_param['show_print_url'] == 'yes' && !$this -> env['is_print'] )
			$this -> tpl -> assign( 'print_url', lib::make_request_uri( array( 'print' => 1 ), 'printable.php' ) );
		
		// Выводим в шаблон содержимое текстового блока
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir.'text.tpl' );
	}
	
	////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Метод возвращает список записей модуля при поиске по тегу
	 */
	protected function get_tag_records( $select_str = '', $order_str = '', $limit_str = '', $filter_str = '', $filter_binds = array() )
	{
		return parent::get_tag_records( $select_str ? $select_str : 'CONTENT_TEXT.*, CONTENT_TEXT.CONTENT_TEXT_ID as CONTENT_ID',
			'order by CONTENT_TEXT.CONTENT_TEXT_ID desc',$limit_str, $filter_str, $filter_binds );
	}
	
	/**
	 * Метод возвращает ссылку на элемент контента
	 */
	protected function get_tag_content_url( $content_id )
	{
		$path_and_area = $this -> get_url_by_module_content( 'CONTENT_TEXT', $content_id, false, false );
		
		return $path_and_area['PATH'];
	}
}
?>
