<?php
/**
 * Модуль "Опросы"
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class m_vote extends module
{
	/**
	 * "Ключевой" хэш декораторов. Исключен декоратор 'lang'
	 * @var array
	 */
	protected $decorators = array( 'version' => 1, 'block' => 1 );
	
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
		
		if ( $this -> view_param['view_mode'] == 'archives' )
		{
			if ( $this -> q_param['vote'] )
			{
				// Вывод результатов опроса
				$tpl_file = 'results.tpl'; $this -> results();
			}
			else
			{
				// Вывод архива опросов
				$tpl_file = 'archives.tpl'; $this -> archives();
			}
		}
		else if ( $this -> q_param['vote'] )
		{
			// Обработка голоса
			$this -> register_vote();
		}
		else
		{
			// Вывод формы для голосования
			$tpl_file = 'form.tpl'; $this -> form();
		}
		
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir.$tpl_file );
	}
	
	/**
	 * Вывод формы для голосования
	 */
	protected function form()
	{
		// Получаем информацию о ближайшем опросе
		$today_date = date( 'YmdHis', time() );
		
		$query_item = $this -> get_module_sql(
			$this -> module_table.'.*',
			'and START_DATE <= :start_date and END_DATE >= :end_date',
			'order by START_DATE desc',
			'limit 1' );
		$vote_item = db::sql_select( $query_item, $this -> get_module_binds() +
			array( 'start_date' => $today_date, 'end_date' => $today_date ) );
		
		if ( !count( $vote_item ) ) return;
		
		if( $_COOKIE['vote_'.$vote_item[0]['VOTE_ID']] )
			$this -> tpl -> assign( 'IS_VOTED', 1 );
		
		$path_and_area = $this -> get_url_by_module_param( 'VOTE', 'view_mode', 'archives', $this -> env['block_id'] );
		if ( $path_and_area['PATH'] )
		{
			$this -> tpl -> assign( 'archives_link', $path_and_area['PATH'] );
			if( $vote_item[0]['VOTE_RESULT'] == 'before' || ( $_COOKIE['vote_'.$vote_item[0]['VOTE_ID']] && $vote_item[0]['VOTE_RESULT'] == 'show' ) )
				$this -> tpl -> assign( 'result_link', lib::make_request_uri( array( 'vote_' . $path_and_area['AREA'] => $vote_item[0]['VOTE_ID'] ), $path_and_area['PATH'] ) );
		}
		
		$this -> tpl -> assign( $vote_item[0] );
		
		$answer_list = db::sql_select( 'select * from VOTE_ANSWER where VOTE_ID = :vote_id order by ANSWER_ORDER asc',
			array( 'vote_id' => $vote_item[0]['VOTE_ID'] ) );
		$this -> tpl -> assign( 'ANSWER_LIST', $answer_list );
	}
	
	/**
	 * Обработка голоса
	 */
	protected function register_vote()
	{
		// Получаем информацию о выбранном опросе
		$query_item = $this -> get_module_sql(
			$this -> module_table.'.*', 'and VOTE_ID = :vote_id' );
		$vote_item = db::sql_select( $query_item, $this -> get_module_binds() +
			array( 'vote_id' => intval( $this -> q_param['vote'] ) ) );
		
		if ( !count( $vote_item ) ) return;
		
		if ( isset( $this -> q_param['vote'] ) && is_array( $this -> q_param['answer'] ) && !$_COOKIE['vote_'.$vote_item[0]['VOTE_ID']] )
		{
			foreach( $this -> q_param['answer'] as $answer )
			{
				$answer_row = db::sql_select(
					'select ANSWER_COUNT from VOTE_ANSWER where VOTE_ANSWER_ID = :vote_answer_id',
					array( 'vote_answer_id' => intval( $answer ) ) );
				if ( count( $answer_row ) )
					db::update_record( 'VOTE_ANSWER', array( 'ANSWER_COUNT' => $answer_row[0]['ANSWER_COUNT'] + 1 ), null,
						array( 'VOTE_ANSWER_ID' => intval( $answer ) ) );
			}
			db::update_record( $this -> module_table, array( 'FILL_COUNT' => $vote_item[0]['FILL_COUNT'] + 1 ), null,
				array( 'VOTE_ID' => intval( $this -> q_param['vote'] ), 'VERSION' => $this -> env['version'] ) );
			
			setcookie( 'vote_'.$vote_item[0]['VOTE_ID'], '1', time() + 24 * 60 * 60, '/', $_SERVER['HTTP_HOST'] );
		}
		
		if ( $vote_item[0]['VOTE_RESULT'] != 'hide' && $this -> view_param['result_in_form_page'] != 'yes' )
		{
			$path_and_area = $this -> get_url_by_module_param( 'VOTE', 'view_mode', 'archives', $this -> env['block_id'] );
			if ( !$path_and_area['PATH'] )
				header( 'Location: '.$_SERVER['HTTP_REFERER']);
			else
				header( 'Location: '.lib::make_request_uri( array( 'vote_' . $path_and_area['AREA'] => $vote_item[0]['VOTE_ID'] ), $path_and_area['PATH'] ) );
		}
		else
			header( 'Location: '.$_SERVER['HTTP_REFERER']);
			
		exit;
	}
	
	/**
	 * Вывод архива опросов
	 */
	protected function archives()
	{
		// Получаем общее число опросов
		$today_date = date( 'YmdHis', time() );
		
		$query_count = $this -> get_module_sql(
			'count(*) as VOTE_COUNT', 'and END_DATE < :today_date', 'order by END_DATE desc' );
		$content_count = db::sql_select( $query_count, $this -> get_module_binds() + array( 'today_date' => $today_date ) );
		$vote_count = $content_count[0]['VOTE_COUNT'];
		
		if ( !$vote_count ) return;
		
		$limit_str = ''; $items_per_page = max( intval( $this -> view_param['items_per_page'] ), 1 );
		
		// Подготавливаем навигационную стороку и строку ограничения числа записей
		if ( $vote_count > $items_per_page )
		{
			$from = ( max( intval( $this -> q_param['from'] ), 1 ) - 1 ) * $items_per_page;
			$this -> tpl -> assign( 'navigation',
				lib::page_navigation( $items_per_page, $vote_count, 'from_'.$this -> env['area_id'], $this -> tpl_dir.'navigation.tpl' ) );
			$limit_str = "limit $from, $items_per_page";
		}
		
		// Получаем содержимое архива опросов
		$query_vote = $this -> get_module_sql(
			$this -> module_table.'.*', 'and END_DATE < :today_date', 'order by END_DATE desc',	$limit_str );
		$content_vote = db::sql_select( $query_vote, $this -> get_module_binds() + array( 'today_date' => $today_date ) );
		
		// Дополнительно выводим в шаблон информацию о датах опроса и ссылках на его результаты
		foreach ( $content_vote as & $item )
		{
			$item['START_DATE'] = lib::unpack_date( $item['START_DATE'], 'short' );
			$item['END_DATE'] = lib::unpack_date( $item['END_DATE'], 'short' );
			
			$item['URL'] = lib::make_request_uri( array( 'vote_'.$this -> env['area_id'] => $item['VOTE_ID'] ) );
		}
		
		$this -> tpl -> assign( 'content', $content_vote );
	}
	
	/**
	 * Вывод результатов опроса
	 */
	protected function results()
	{
		// Получаем информацию о выбранном опросе
		$query_item = $this -> get_module_sql(
			$this -> module_table.'.*', 'and VOTE_ID = :vote_id' );
		$vote_item = db::sql_select( $query_item, $this -> get_module_binds() +
			array( 'vote_id' => intval( $this -> q_param['vote'] ) ) );
		
		if ( !count( $vote_item ) ) return;
		
		$today_date = date( 'YmdHis', time() );
		if ( ( $today_date <= $vote_item[0]['END_DATE'] && $vote_item[0]['VOTE_RESULT'] == 'show' && !$_COOKIE['vote_'.$vote_item[0]['VOTE_ID']] ) ||
			( $today_date <= $vote_item[0]['END_DATE'] && $vote_item[0]['VOTE_RESULT'] == 'hide' ) )
				return;
		
		$this -> tpl -> assign( $vote_item[0] );
		
		$answer_list = db::sql_select( 'select * from VOTE_ANSWER where VOTE_ID = :vote_id order by ANSWER_ORDER asc',
			array( 'vote_id' => $vote_item[0]['VOTE_ID'] ) );
		
		$answer_sum = 0; $answer_max = 0;
		foreach ( $answer_list as & $answer_item )
		{
			if ( $answer_item['ANSWER_COUNT'] > $answer_max )
				$answer_max = $answer_item['ANSWER_COUNT'];
			$answer_sum += $answer_item['ANSWER_COUNT'];
		}
		
		$line_width = max( intval( $this -> view_param['line_width'] ), 1 );
		$answer_sum = ( $vote_item[0]['VOTE_TYPE'] == 'single' ) ? $answer_sum : $vote_item[0]['FILL_COUNT'];
		
		foreach ( $answer_list as & $answer_item )
		{
			$answer_item['PERCENT'] = ( $answer_sum > 0 ) ? round( 100 * $answer_item['ANSWER_COUNT'] / $answer_sum, 2 ) : '0';
			$answer_item['WIDTH'] = ( $answer_max > 0 ) ? round( $line_width * $answer_item['ANSWER_COUNT'] / $answer_max ) : '1';
		}
		
		$this -> tpl -> assign( 'ANSWER_SUM', $answer_sum );
		$this -> tpl -> assign( 'ANSWER_LIST', $answer_list );
		
		$this -> tpl -> assign( 'archives_link', lib::make_request_uri( array( 'vote_'.$this -> env['area_id'] => '' ) ) );
	}
	
	/**
	* Дополняем хеш данными о пройденных пользователем голосованиях
	*/
	protected function ext_get_hash_code()
	{
		$vote_cookie = array();
		foreach ( $_COOKIE as $cookie_name => $cookie_value )
		if ( preg_match( '/^vote_(\d+)$/', $cookie_name) )
			$vote_cookie[] = $cookie_name . '|' . $cookie_value;
		return join( '|', $vote_cookie );
	}
}
?>
