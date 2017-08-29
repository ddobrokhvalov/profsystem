<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Листы подписки"
 *
 * @package    RBC_Contents_5_0
 * @subpackage app
 * @copyright  Copyright (c) 2006 RBC SOFT
 * 
 * @todo После выпуска очередного обновления переделать subscribe_common::post_mail на lib::post_mail
 * @todo После закрытия WCR #14149 нужно добавить в def-файл новостей списков поле "SEND_IT"
 * 			{{if ($modules.subscription.autosend) }}
 * 			"SEND_IT"=>		array("title"=>"lang_send_it","type" => "checkbox" ),
 * 			{{/if}}
 * Сейчас это сделать невозможно, потому что рушится импорт тестового контента. А поле пока добавлять вручную
 */

include_once(params::$params["adm_data_server"]["value"]."class/app/table/subscribe_list/subscribe_common.php");

class subscribe_list extends table
{
	/**
	 * Максимальное число прикрепленных файлов
	 */
	protected $max_attach_files = 5;
	
	/**
	 * Карточка отправки сообщения
	 */
	public function action_send()
	{
		$pk = $this -> primary_key -> get_from_request();
		$subscribe_record = $this -> full_object -> get_change_record( $pk );
		
		$form_name = html_element::get_next_form_name();
		
		$html_fields = html_element::html_fields(
			$this->get_form_fields("add", "_form_", $subscribe_record, "", $this -> get_send_form_fields( $pk ) ),
			$this->tpl_dir."core/html_element/html_fields.tpl",
			$this->field);
		
		$html_form = html_element::html_form( $html_fields,
			$this->url->get_hidden("sended", array("pk"=>$pk)), $this->tpl_dir."core/html_element/html_form.tpl", true);
		
		$operations = array();
		
		$operations['send'] = array("name"=>"group_publish", "alt"=>metadata::$lang["lang_subscription_send_message"], "onClick"=>"if ( CheckForm.validate( document.forms['{$form_name}'] ) ) { remove_unblock_record (); document.forms['{$form_name}'].action.value='sended'; document.forms['{$form_name}'].submit() }; return false");
		$operations['test_send'] = array("name"=>"group_publish", "alt"=>metadata::$lang["lang_subscription_test_message"], "onClick"=>"if ( CheckForm.validate( document.forms['{$form_name}'] ) ) { remove_unblock_record (); document.forms['{$form_name}'].action.value='test_sended'; document.forms['{$form_name}'].submit() }; return false");
		$operations['cancel'] = array("name"=>"cancel", "alt"=>metadata::$lang["lang_cancel"], "url"=>$this->url->get_url( "", array( "restore_params" => 1 ) ));
		
		$title = metadata::$lang['lang_subscription_send'];
		$card_title = $pk ? $this -> full_object -> get_record_title( $pk ) : $title;
		
		$this -> path_id_array[] = array( 'TITLE' => $card_title );
		
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'title', $card_title );
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir.'core/html_element/html_operation.tpl') );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir.'core/html_element/html_operation.tpl') );
		$tpl -> assign( 'form', $html_form );
		
		$this -> title = $title;
		$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' );
	}
	
	/**
	 * Действие - отправка письма подписчикам
	 */
	public function action_sended()
	{
		if ( $pk = $this -> primary_key -> get_from_request() )
			$subscribe_record= $this -> full_object -> get_change_record( $pk, true );
		
		$prepared_fields = array();
		foreach ( $this -> get_send_form_fields( $pk ) as $field_name => $field )
			$prepared_fields[$field_name] = $this -> field -> get_prepared( $_REQUEST["_form_" . $field_name], $field, "_form_" . $field_name );
		
		if ( $pk )
		{
			$subscriber_list = db::sql_select( '
				select SUBSCRIBER.*
				from SUBSCRIBER, SUBSCRIBER_SUBSCRIBE_LIST
				where SUBSCRIBER.SUBSCRIBER_ID = SUBSCRIBER_SUBSCRIBE_LIST.SUBSCRIBER_ID
					and SUBSCRIBER_SUBSCRIBE_LIST.SUBSCRIBE_LIST_ID = :SUBSCRIBE_LIST_ID
					and SUBSCRIBER.ACTIVE = 1 and SUBSCRIBER.ACTIVE IS NOT NULL',
				  array( 'SUBSCRIBE_LIST_ID' => $subscribe_record['SUBSCRIBE_LIST_ID'] ) );
		}
		else
		{
			$subscriber_list = db::sql_select( '
				select DISTINCT SUBSCRIBER.*
				from SUBSCRIBER, SUBSCRIBER_SUBSCRIBE_LIST, SUBSCRIBE_LIST, SUBSCRIBE_LIST_SITE
				where SUBSCRIBER.ACTIVE = 1 and SUBSCRIBER.ACTIVE IS NOT NULL and
					SUBSCRIBER.SUBSCRIBER_ID = SUBSCRIBER_SUBSCRIBE_LIST.SUBSCRIBER_ID and
					SUBSCRIBER_SUBSCRIBE_LIST.SUBSCRIBE_LIST_ID = SUBSCRIBE_LIST.SUBSCRIBE_LIST_ID and
					SUBSCRIBE_LIST.LANG_ID = :LANG_ID and
					SUBSCRIBE_LIST.SUBSCRIBE_LIST_ID = SUBSCRIBE_LIST_SITE.SUBSCRIBE_LIST_ID and
					SUBSCRIBE_LIST_SITE.SITE_ID = :SITE_ID',
				array( 'LANG_ID' => $prepared_fields['LANG_ID'], 'SITE_ID' => $prepared_fields['SITE_ID'] ) );
		}
		
		$attach_files = array();
		for ( $i = 1; $i <= 5; $i++ )
			if ( $prepared_fields['ATTACH_FILE_' . $i] )
				$attach_files[] = str_replace( params::$params['common_htdocs_http']['value'],
					params::$params['common_htdocs_server']['value'], $prepared_fields['ATTACH_FILE_' . $i] );
		
		if ( $pk )
			$signature = subscribe_common::get_signature( $subscribe_record['LANG_ID'], '', $subscribe_record['SUBSCRIBE_LIST_ID'] );
		else
			$signature = subscribe_common::get_signature( $prepared_fields['LANG_ID'], $prepared_fields['SITE_ID'] );
		
		$count = 0;
		
		foreach ( $subscriber_list as $recipient )
		{
			$to_address = $recipient['EMAIL'];
			$to_name = $recipient['FIO'];
			$from_address = $pk ? $subscribe_record['SENDER_EMAIL'] : $prepared_fields['SENDER_EMAIL'];
			$from_name = $pk ? $subscribe_record['SENDER_NAME'] : $prepared_fields['SENDER_NAME'];
			$subject = preg_replace( '/<SUBSCRIBER_NAME>/', $to_name, $prepared_fields['EMAIL_SUBJECT'] );
			$message = preg_replace( '/<SUBSCRIBER_NAME>/', $to_name,
				( $pk ? $subscribe_record['EMAIL_HEADER'] : '' ) . $prepared_fields['EMAIL_BODY'] . ( $pk ? $subscribe_record['EMAIL_FOOTER'] : '' ) );
			$encoding = params::$params['encoding']['value'];
			
			$signature_link = preg_replace( array( '/<EMAIL>/', '/<PASSWORD>/' ), array( $recipient['EMAIL'], $recipient['PASSWORD'] ), $signature );
			
			subscribe_common::post_mail( $to_address, $to_name, $from_address, $from_name, $subject, $message . $signature_link, $encoding, $attach_files );
			
			$count++;
		}
		
		db::insert_record( 'SUBSCRIBE_ARCHIVE', array(
			'SUBSCRIBE_LIST_ID' => $pk ? $subscribe_record['SUBSCRIBE_LIST_ID'] : '',
			'SITE_ID' => $pk ? '' : $prepared_fields['SITE_ID'],
			'LANG_ID' => $pk ? '' : $prepared_fields['LANG_ID'],
			'AUTH_USER_ID' => $this -> auth -> user_info['AUTH_USER_ID'],
			'EMAIL_SUBJECT' => $prepared_fields['EMAIL_SUBJECT'],
			'EMAIL_BODY' => ( $pk ? $subscribe_record['EMAIL_HEADER'] : '' ) . $prepared_fields['EMAIL_BODY'] . ( $pk ? $subscribe_record['EMAIL_FOOTER'] : '' ) . $signature,
			'EMAIL_DATETIME' => lib::pack_date(date('d.m.Y H:i'), 'long'),
			'EMAIL_ATTACH' => join( ",\n", array_map ( 'basename', $attach_files ) ) ) );
		
		foreach ( $attach_files as $file_name ) @unlink( $file_name );
		
		$this -> url -> redirect( 'complete', array( 'pk' => array( 'COUNT' => $count ) ) );
	}
	
	/**
	 * Действие - отправка тестового письма
	 */
	public function action_test_sended()
	{
		if ( $pk = $this -> primary_key -> get_from_request() )
			$subscribe_record = $this -> full_object -> get_change_record( $pk, true );
		
		$prepared_fields = array();
		foreach ( $this -> get_send_form_fields( $pk ) as $field_name => $field )
			$prepared_fields[$field_name] = $this -> field -> get_prepared( $_REQUEST["_form_" . $field_name], $field, "_form_" . $field_name );
		
		$admin_email = $this -> auth -> user_info['EMAIL'];
		if ( !$admin_email || !$this -> field -> check_email( $admin_email ) )
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_subscription_error_email"].($pk ? ": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")":""));
		
		$attach_files = array();
		for ( $i = 1; $i <= 5; $i++ )
			if ( $prepared_fields['ATTACH_FILE_' . $i] )
				$attach_files[] = str_replace( params::$params['common_htdocs_http']['value'],
					params::$params['common_htdocs_server']['value'], $prepared_fields['ATTACH_FILE_' . $i] );
		
		if ( $pk )
			$signature = subscribe_common::get_signature( $subscribe_record['LANG_ID'], '', $subscribe_record['SUBSCRIBE_LIST_ID'] );
		else
			$signature = subscribe_common::get_signature( $prepared_fields['LANG_ID'], $prepared_fields['SITE_ID'] );
		
		$to_address = $admin_email;
		$to_name = $this -> auth -> user_info['SURNAME'];
		$from_address = $pk ? $subscribe_record['SENDER_EMAIL'] : $prepared_fields['SENDER_EMAIL'];
		$from_name = $pk ? $subscribe_record['SENDER_NAME'] : $prepared_fields['SENDER_NAME'];
		$subject = preg_replace( '/<SUBSCRIBER_NAME>/', $to_name, $prepared_fields['EMAIL_SUBJECT'] );
		$message = preg_replace( '/<SUBSCRIBER_NAME>/', $to_name,
			( $pk ? $subscribe_record['EMAIL_HEADER'] : '' ) . $prepared_fields['EMAIL_BODY'] . ( $pk ? $subscribe_record['EMAIL_FOOTER'] : '' ) );
		$encoding = params::$params['encoding']['value'];
		
		subscribe_common::post_mail( $to_address, $to_name, $from_address, $from_name, $subject, $message . $signature, $encoding, $attach_files );
		
		foreach ( $attach_files as $file_name ) @unlink( $file_name );
		
		$this -> url -> redirect( 'complete', array( 'pk' => array( 'COUNT' => 1 ) ) );
	}
	
	/**
	 * Отображение результатов отправки
	 */
	public function action_complete()
	{
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'success_count', intval( $_REQUEST['COUNT'] ) );
		$tpl -> assign( 'status', array( 'success_message' => metadata::$lang["lang_subscription_messages_done"],
			'back_url' => $this -> url -> get_url() ) );
		
		$this -> title = metadata::$lang['lang_subscription_send'];
		$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/object/html_distributed_report.tpl' );
	}
	
	/**
	 * Оставляем в списке блоков только новости и списки
	 *
	 * @see table::action_m2m()
	 */
	public function action_m2m()
	{
		if ( $_REQUEST['m2m'] == 'SUBSCRIBE_LIST_INF_BLOCK' )
		{
			// Ограничиваем фильтр по модулю
			$prg_module_list = db::sql_select( "select PRG_MODULE_ID from PRG_MODULE where PRG_MODULE.SYSTEM_NAME = 'NEWS' or PRG_MODULE.SYSTEM_NAME = 'CONTENT_LIST'" );
			$prg_module_list = array_keys( lib::array_reindex( $prg_module_list, 'PRG_MODULE_ID' ) );
			metadata::$objects['INF_BLOCK']['fields']['PRG_MODULE_ID']['list_mode']['direct'] = $prg_module_list;
			
			// Ограничиваем список блоков
			$inf_block_list = db::sql_select( "select INF_BLOCK_ID from INF_BLOCK where PRG_MODULE_ID in ( " . lib::array_make_in( $prg_module_list ) . " )" );
			$inf_block_list = array_keys( lib::array_reindex( $inf_block_list, 'INF_BLOCK_ID' ) );
			metadata::$objects['SUBSCRIBE_LIST']['m2m']['SUBSCRIBE_LIST_INF_BLOCK']['list_mode']['direct'] = $inf_block_list;
		}
		
		parent::action_m2m();
	}
	
	/**
	 * Возвращает описанеи полей формы отправки сообщения
	 */
	public function get_send_form_fields( $pk )
	{
		$send_form_fields = array();
		
		if ( $pk )
		{
			$send_form_fields['SUBSCRIBE_LIST_ID'] = array("title"=>metadata::$lang["lang_subscribe_list"], "type"=>"select2", "fk_table"=>"SUBSCRIBE_LIST", "errors"=>_nonempty_);
		}
		else
		{
			$send_form_fields['SITE_ID'] = array("title"=>metadata::$lang["lang_site"], "type"=>"select2", "fk_table"=>"SITE", "errors"=>_nonempty_);
			$send_form_fields['LANG_ID'] = array("title"=>metadata::$lang["lang_lang"], "type"=>"select2", "fk_table"=>"LANG", "errors"=>_nonempty_);
			
			$send_form_fields['SENDER_EMAIL'] = array("title"=>metadata::$lang["lang_sender_email"], "type"=>"text", "errors"=>_nonempty_|_email_);
			$send_form_fields['SENDER_NAME'] = array("title"=>metadata::$lang["lang_sender_name"], "type"=>"text", "errors"=>_nonempty_);
		}
		
		$send_form_fields['EMAIL_SUBJECT'] = array("title"=>metadata::$lang["lang_email_subject"], "type"=>"text", "errors"=>_nonempty_);
		$send_form_fields['EMAIL_BODY'] = array("title"=>metadata::$lang["lang_email_text"], "type"=>"textarea", "errors"=>_nonempty_);
		
		for ( $i = 1; $i <= $this -> max_attach_files; $i++ )
			$send_form_fields['ATTACH_FILE_' . $i] = array("title"=>metadata::$lang["lang_file"] . " " . $i, "type"=>"file");
		
		return $send_form_fields;
	}
	
	/**
	 * Подготовка списка операций над записями
	 * 
	 * @see table::get_index_operations()
	 */
	public function get_index_operations()
	{
		$operations = parent::get_index_operations();
		
		$operations['send_all'] = array( 'name' => 'group_publish', 'alt' => metadata::$lang['lang_subscription_send_all'],
			'url' => $this -> url -> get_url( 'send' ) );
		
		return $operations;
	}
	
	/**
	 * Добавляем колонку с ссылками на отправку писем
	 *
	 * @see table::ext_index_header()
	 */
	public function ext_index_header( $mode )
	{
		$headers = parent::ext_index_header( $mode );
		
		$headers = array_merge( $headers,
			array( 'link_to_send' => array( 'title' => metadata::$lang['lang_subscription_send'], 'type'=>'_link' ) ) );
		
		return $headers;
	}
	
	/**
	 * Добавляем ссылки на отправку писем
	 *
	 * @see table::get_index_records()
	 */
	public function get_index_records( &$request, $mode, $list_mode, $include = 0, $exclude = array() )
	{
		$records = parent::get_index_records( &$request, $mode, $list_mode, $include, $exclude );
		
		if ( count( $records ) > 0 )
			foreach( $records as $record_index => $record )
				$records[$record_index]['link_to_send'] = array(
					'url' => $this -> url -> get_url( 'send', array( 'pk' => $this -> primary_key -> get_from_record( $record ) ) ) );
		
		return $records;
	}
}
?>
