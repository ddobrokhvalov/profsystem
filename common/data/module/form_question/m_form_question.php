<?php
/**
 * Модуль "Форма обратной связи"
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class m_form_question extends module
{
	/**
	 * "Ключевой" хэш декораторов. Исключен декоратор 'lang'
	 * @var array
	 */
	protected $decorators = array( 'version' => 1, 'block' => 1 );
	
	/**
	* Объект шаблонизатора модуля
	*/
	protected $tpl;
	
	/**
	* Диспетчер модуля
	*/
	protected function content_init()
	{
		// Необходимо для работы каптчи
		if ( $this -> view_param['captcha'] == 'yes' )
			session_start();
		
		// Проверяется совпадение введенной фразы коду на изображении
		$captcha_error = $this -> view_param['captcha'] == 'yes' && $this -> q_param['done'] &&
			!captcha::check( $this -> q_param['captcha_id'], $this -> q_param['captcha_value'] );
		
		// Инициализация шаблонизатора
		$this -> tpl = new smarty_ee_module( $this );
		
		// Обработка переданных из формы данных
		if ( $this -> q_param['done'] && !$captcha_error )
			$this -> mode_done();
		
		if ( $this -> q_param['result'] )
		{
			// Отображение результата заполнения формы
			$tpl_file = 'result.tpl'; $this -> tpl -> assign( 'result', $this -> q_param['result'] );
		}
		else
		{
			// Отображение полей формы
			$tpl_file = 'form.tpl'; $this -> mode_fields( $captcha_error );
		}
		
		$this -> tpl -> assign( 'form_name', $this -> view_param['form_name'] );
		$this -> tpl -> assign( 'form_description', $this -> view_param['form_description'] );
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir.$tpl_file );
	}
	
	/**
	* Отображение полей формы
	*/
	protected function mode_fields( $captcha_error )
	{
		$query_items = $this -> get_module_sql( $this -> module_table.'.*', '', 'order by QUESTION_ORDER' );
		$content_items = db::sql_select( $query_items, $this -> get_module_binds() );
		
		foreach( $content_items as & $item )
		{
			if ( in_array( $item['QUESTION_TYPE'], array( 'select', 'radio_group', 'radio_group_alt', 'checkbox_group', 'checkbox_group_alt' ) ) )
			{
				$item['OPTIONS'] = db::sql_select(
					'select * from FORM_OPTIONS where FORM_QUESTION_ID = :form_question_id order by OPTIONS_ORDER',
					array( 'form_question_id' => intval( $item['FORM_QUESTION_ID'] ) ) );
				
				// Если код на изображении введен ошибочно, заполняем форму данными из REQUEST
				if ( $captcha_error && isset( $this -> q_param['f_'.$item['FORM_QUESTION_ID']] ) )
				{
					if ( in_array( $item['QUESTION_TYPE'], array( 'select', 'radio_group', 'radio_group_alt' ) ) )
					{
						foreach ( $item['OPTIONS'] as $option_index => $option_item )
							$item['OPTIONS'][$option_index]['IS_DEFAULT'] =
								$option_item['FORM_OPTIONS_ID'] == $this -> q_param['f_'.$item['FORM_QUESTION_ID']];
						
						if ( $item['QUESTION_TYPE'] == 'radio_group_alt' )
						{
							$item['IS_DEFAULT'] = $this -> q_param['f_'.$item['FORM_QUESTION_ID']] == '_alt_';
							if ( $this -> q_param['f_'.$item['FORM_QUESTION_ID']] == '_alt_' && isset( $this -> q_param['alt_f_'.$item['FORM_QUESTION_ID']] ) )
								$item['DEFAULT_VALUE'] = $this -> q_param['alt_f_'.$item['FORM_QUESTION_ID']];
						}
					}
					else
					{
						foreach ( $item['OPTIONS'] as $option_index => $option_item )
							$item['OPTIONS'][$option_index]['IS_DEFAULT'] =
								in_array( $option_item['FORM_OPTIONS_ID'], $this -> q_param['f_'.$item['FORM_QUESTION_ID']] );
						
						if ( $item['QUESTION_TYPE'] == 'checkbox_group_alt' )
						{
							$item['IS_DEFAULT'] = in_array( '_alt_', $this -> q_param['f_'.$item['FORM_QUESTION_ID']] );
							if ( in_array( '_alt_', $this -> q_param['f_'.$item['FORM_QUESTION_ID']] ) && isset( $this -> q_param['alt_f_'.$item['FORM_QUESTION_ID']][0] ) )
								$item['DEFAULT_VALUE'] = $this -> q_param['alt_f_'.$item['FORM_QUESTION_ID']][0];
						}
					}
				}
			}
			else
			{
				// Если код на изображении введен ошибочно, заполняем форму данными из REQUEST
				if ( $captcha_error && isset( $this -> q_param['f_'.$item['FORM_QUESTION_ID']] ) )
					$item['DEFAULT_VALUE'] = $this -> q_param['f_'.$item['FORM_QUESTION_ID']];
			}
		}
		
		$this -> tpl -> assign( 'fields', lib::array_htmlspecialchars( $content_items ) );
		$this -> tpl -> assign( 'captcha_error', $captcha_error );
		
		if ( $this -> view_param['captcha'] == 'yes' )
			$this -> tpl -> assign( 'captcha_id', captcha::generate() );
	}
	
	/**
	* Обработка переданных из формы данных
	*/
	protected function mode_done()
	{
		$query_items = $this -> get_module_sql( $this -> module_table.'.*', '', 'order by QUESTION_ORDER' );
		$content_items = db::sql_select( $query_items, $this -> get_module_binds() );
		
		if ( !count( $content_items ) )
			$this -> go_to_result_page( false );
		
		db::insert_record( 'FORM_ANSWER', array(
			'INF_BLOCK_ID' => $this -> env['block_id'], 'IP' => $_SERVER['REMOTE_ADDR'], 'ANSWER_TIME' => lib::pack_date( date( 'd.m.Y H:i', time() ), 'long' ) ) );
		$form_answer_id = db::last_insert_id( 'FORM_ANSWER_SEQ' );
		
		foreach( $content_items as & $item )
		{
			switch ( $item['QUESTION_TYPE'] )
			{
				case 'string': case 'int': case 'float': case 'email': case 'date': case 'checkbox': case 'textarea':
					if ( $this -> q_param['f_'.$item['FORM_QUESTION_ID']] !== '' )
					{
						if ( $item['QUESTION_TYPE'] == 'checkbox' )
							$this -> q_param['f_'.$item['FORM_QUESTION_ID']] = $this -> q_param['f_'.$item['FORM_QUESTION_ID']] ?
								$this -> lang['sysw_yes'] : $this -> lang['sysw_no'];
						db::insert_record( 'FORM_VALUES', array(
							'FORM_ANSWER_ID' => $form_answer_id, 'FORM_QUESTION_ID' => $item['FORM_QUESTION_ID'],
							'ANSWER_VALUE' => $this -> q_param['f_'.$item['FORM_QUESTION_ID']] ) );
						$item['VALUE'] = $this -> q_param['f_'.$item['FORM_QUESTION_ID']];
					}
					else
					{
						db::insert_record( 'FORM_VALUES', array(
							'FORM_ANSWER_ID' => $form_answer_id, 'FORM_QUESTION_ID' => $item['FORM_QUESTION_ID'], 'ANSWER_VALUE' => null ) );
						$item['VALUE'] = '';
					}
					break;
				case 'select': case 'radio_group': case 'radio_group_alt':
					if ( $this -> q_param['f_'.$item['FORM_QUESTION_ID']] !== '' && $this -> q_param['f_'.$item['FORM_QUESTION_ID']] !== '_alt_' )
					{
						$options_row = db::sql_select(
							'select TITLE from FORM_OPTIONS where FORM_OPTIONS_ID = :form_options_id',
							array( 'form_options_id' => intval( $this -> q_param['f_'.$item['FORM_QUESTION_ID']] ) ) );
						if ( count( $options_row ) )
						{
							db::insert_record( 'FORM_VALUES', array(
								'FORM_ANSWER_ID' => $form_answer_id, 'FORM_QUESTION_ID' => $item['FORM_QUESTION_ID'],
								'FORM_OPTIONS_ID' => $this -> q_param['f_'.$item['FORM_QUESTION_ID']] ) );
							$item['VALUE'] = $options_row[0]['TITLE'];
						}
					}
					else if ( $this -> q_param['f_'.$item['FORM_QUESTION_ID']] == '_alt_' && $this -> q_param['alt_f_'.$item['FORM_QUESTION_ID']] !== '' )
					{
						db::insert_record( 'FORM_VALUES', array(
							'FORM_ANSWER_ID' => $form_answer_id, 'FORM_QUESTION_ID' => $item['FORM_QUESTION_ID'],
							'ANSWER_VALUE' => $this -> q_param['alt_f_'.$item['FORM_QUESTION_ID']] ) );
						$item['VALUE'] = $this -> q_param['alt_f_'.$item['FORM_QUESTION_ID']];
					}
					else
					{
						db::insert_record( 'FORM_VALUES', array(
							'FORM_ANSWER_ID' => $form_answer_id, 'FORM_QUESTION_ID' => $item['FORM_QUESTION_ID'], 'ANSWER_VALUE' => null ) );
						$item['VALUE'] = '';
					}
					break;
				case 'checkbox_group':case 'checkbox_group_alt':
					$item['VALUE'] = array();
					if ( is_array( $this -> q_param['f_'.$item['FORM_QUESTION_ID']] ) )
					{
						foreach( $this -> q_param['f_'.$item['FORM_QUESTION_ID']] as $option )
							if ( $option !== '' && $option != '_alt_' )
							{
								$options_row = db::sql_select(
									'select TITLE from FORM_OPTIONS where FORM_OPTIONS_ID = :form_options_id',
									array( 'form_options_id' => intval( $option ) ) );
								if ( count( $options_row ) )
								{
									db::insert_record( 'FORM_VALUES', array(
										'FORM_ANSWER_ID' => $form_answer_id, 'FORM_QUESTION_ID' => $item['FORM_QUESTION_ID'],
										'FORM_OPTIONS_ID' => $option ) );
									$item['VALUE'][] = $options_row[0]['TITLE'];
								}
							}
							else if ( $option == '_alt_' && is_array( $this -> q_param['alt_f_'.$item['FORM_QUESTION_ID']] ) && $this -> q_param['alt_f_'.$item['FORM_QUESTION_ID']][0] !== '' )
							{
								db::insert_record( 'FORM_VALUES', array(
									'FORM_ANSWER_ID' => $form_answer_id, 'FORM_QUESTION_ID' => $item['FORM_QUESTION_ID'],
									'ANSWER_VALUE' => $this -> q_param['alt_f_'.$item['FORM_QUESTION_ID']][0] ) );
								$item['VALUE'][] = $this -> q_param['alt_f_'.$item['FORM_QUESTION_ID']][0];
							}
					}
					else
					{
						db::insert_record( 'FORM_VALUES', array(
							'FORM_ANSWER_ID' => $form_answer_id, 'FORM_QUESTION_ID' => $item['FORM_QUESTION_ID'], 'ANSWER_VALUE' => null ) );
					}
			}
		}
		
		$this -> tpl -> assign( 'fields', lib::array_htmlspecialchars( $content_items ) );
		
		$message = $this -> tpl -> fetch( $this -> tpl_dir.'email.tpl' );
		
		// Если указаны е-mail для уведомления, отправляем письма
		if ( preg_match_all( '/[a-z0-9_\.-]+@[a-z0-9_\.-]+\.[a-z]{2,}/i', $this -> view_param['form_email'], $matches ) )
		{
			$site_sql = db::sql_select( 'select * from SITE where SITE_ID = :site_id',
				array( 'site_id' => intval( $this -> env['site_id'] ) ) );
			
			foreach( $matches[0] as $email )
				lib::post_mail( $email, $this -> lang['sysw_administrator'], 'admin@' . $_SERVER['SERVER_NAME'],
					$this -> view_param['form_name'], $this -> lang['sysw_form_question_message_from_site'] . ' "' . $site_sql[0]['TITLE'] . '"', $message );
		}
		
		$this -> go_to_result_page( true );
	}
	
	/**
	* Обработка переданных из формы данных
	*/
	protected function go_to_result_page( $result = true )
	{
		header( 'Location: '.lib::make_request_uri( array( 'result_'.$this -> env['area_id'] => $result ? 'ok' : 'error' ) ) );
		exit();
	}
	
	/**
	 * Оключаем кэширование, если включена проверка каптчей
	 */
	protected function get_hash_code()
	{
		return ( $this -> view_param['captcha'] != 'yes' ) ? parent::get_hash_code() : false;
	}
}
?>