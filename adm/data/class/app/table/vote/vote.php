<?php
/**
 * Класс для реализации нестандартного поведения главной таблицы модуля "Опросы"
 *
 * @package    RBC_Contents_5_0
 * @subpackage app
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class vote extends table_workflow
{
	/**
	* Дополняем данные для экспорта информацией о вариантах ответа
	*/		
	public function get_export_add_data_xml($pk)
	{
		$xml=$this->call_parent('get_export_add_data_xml', array($pk));
		
		$vote_answer_obj = object::factory('VOTE_ANSWER');
		$answers=db::sql_select('
			SELECT '.
				$vote_answer_obj->primary_key->select_clause().' 
			FROM 
				VOTE_ANSWER 
			WHERE 
				VOTE_ID=:vote_id', 
			array('vote_id'=>$pk['VOTE_ID'])
		);
		if (sizeof($answers)) {
			$xml .= "<LINKS>\n";
			
			for ($i=0, $n=sizeof($answers); $i<$n; $i++) 
				$xml .= preg_replace('/^/m', '  ', $vote_answer_obj->get_export_xml($answers[$i]));
			
			$xml .= "</LINKS>\n";
		}
		
		$vote_answer_obj->__destruct();
		
		return $xml;
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Добавляем в случае необходимости фильтр по опросам
	 *
	 * @see table::get_index_query_components()
	 */
	public function get_index_query_components( &$request, $mode, $list_mode )
	{
		$components = $this -> call_parent( 'get_index_query_components', array( &$request, $mode, $list_mode ) );
		
		if ( $request['_f_ACTIVE'] != '' )
		{
			$today_date = date( 'YmdHis', time() );
			
			switch ( $request['_f_ACTIVE'] )
			{
				case 'active': // активный
					$components[2] .= ' and VOTE.START_DATE <= :start_date and VOTE.END_DATE >= :end_date';
					$components[3] += array( 'start_date' => $today_date, 'end_date' => $today_date );
					break;
				case 'schedule'; // планируемый
					$components[2] .= ' and VOTE.START_DATE > :start_date';
					$components[3] += array( 'start_date' => $today_date );
					break;
				case 'complete': // завершенный
					$components[2] .= ' and VOTE.END_DATE < :end_date';
					$components[3] += array( 'end_date' => $today_date );
					break;
			}
		}
		
		return $components;
	}
}
?>
