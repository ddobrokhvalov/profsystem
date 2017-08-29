<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Пользователи"
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2006 RBC SOFT
 *
 * @todo Синхронизация и клонирование администраторов не должна работать работать в случае, если клиентская и административная часть работают на разных серверах. Пока эта возможность не реализована, в коде на соответствующих местах расставлены заглушки
 */
class cl_client extends table
{
	/**
	 * Дополнтиельная проверка корректности ввода пароля
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		$this -> adjust_record_fields();
		
		$this -> check_password( $raw_fields[$prefix . 'PASSWORD_MD5'] );
		
		return parent::exec_add( $raw_fields, $prefix );
	}
	
	/**
	 * Обновление соответствующей записи в таблице "Администраторы"
	 *
	 * @see table::exec_change()
	 */
	public function exec_change( $raw_fields, $prefix, $pk )
	{
		$client_record = $this -> full_object -> get_change_record( $pk, true );
		
		$this -> adjust_record_fields( false, params::$params['openid_mode']['value'] && $client_record['OPENID'] );
		
		if ( metadata::$objects['CL_CLIENT']['fields']['EMAIL'] && $raw_fields[$prefix . 'EMAIL'] === '' )
			unset( metadata::$objects['CL_CLIENT']['fields']['EMAIL']['group_by'] );
		
		if ( true )
		{
			// Выясняем наличие связанной записи в таблице "Администраторы"
			$is_admin_link = count( db::sql_select( 'select * from AUTH_USER where CL_CLIENT_ID = :CL_CLIENT_ID', $pk ) ) > 0;
			
			if ( $is_admin_link )
				$this -> check_admin_fields( $raw_fields, $prefix, $pk );
		}
		
		// Дополнительная проверка корректности ввода пароля
		if ( isset( $raw_fields[$prefix . 'PASSWORD_MD5'] ) && $raw_fields[$prefix . 'PASSWORD_MD5'] !== '' )
			$this -> check_password( $raw_fields[$prefix . 'PASSWORD_MD5'], $pk );
		
		parent::exec_change( $raw_fields, $prefix, $pk );
		
		if ( true && $is_admin_link )
		{
			$client_record = $this -> full_object -> get_change_record( $pk );
			
			// Обновление связанной записи в таблице "Администраторы"
			$admin_record = array( 'PASSWORD_MD5' => $client_record['PASSWORD_MD5'], 'SURNAME' => $client_record['SURNAME'],
				'NAME' => $client_record['NAME'], 'PATRONYMIC' => $client_record['PATRONYMIC'] );
			
			if ( metadata::$objects['CL_CLIENT']['fields']['LOGIN'] ) $admin_record['LOGIN'] = $client_record['LOGIN'];
			if ( metadata::$objects['CL_CLIENT']['fields']['EMAIL'] ) $admin_record['EMAIL'] = $client_record['EMAIL'];
			
			db::update_record( 'AUTH_USER', $admin_record, '', $pk );
		}
	}
	
	/**
	 * Отвязка пользователя от соответствующего администратора
	 *
	 * @see table::ext_finalize_delete()
	 */
	public function ext_finalize_delete( $pk, $partial = false )
	{
		parent::ext_finalize_delete( $pk, $partial );
		
		if ( true )
			db::update_record( 'AUTH_USER', array( 'CL_CLIENT_ID' => 0 ), '', $pk );
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Действие - карточка добавления
	 * 
	 * @see table::action_add()
	 */
	public function action_add()
	{
		$this -> adjust_record_fields();
		
		parent::action_add();
	}
	
	/**
	 * Действие - карточка рерактирования
	 * 
	 * @see table::action_change()
	 */
	public function action_change()
	{
		$pk = $this -> primary_key -> get_from_request();
		$client_record = $this -> full_object -> get_change_record( $pk, true );
		
		$this -> adjust_record_fields( false, params::$params['openid_mode']['value'] && $client_record['OPENID'] );
		
		parent::action_change();
	}
	
	/**
	 * Изменение заполняемости полей карточки редактирования записи
	 * 
	 * @param boolean	$is_add				добавление записи или изменение
	 * @param string	$is_openid_person	OpenID-пользователь
	 */
	protected function adjust_record_fields( $is_add = true, $is_openid_person = false )
	{
		if ( $is_add )
		{
			metadata::$objects[$this -> obj]['fields']['PASSWORD_MD5']['errors'] |= _nonempty_;
			
			metadata::$objects[$this -> obj]['fields']['OPENID']['no_add'] = true;
		}
		else
		{
			if ( $is_openid_person )
			{
				metadata::$objects[$this -> obj]['fields']['OPENID']['errors'] |= _nonempty_;
				
				metadata::$objects[$this -> obj]['fields']['EMAIL']['errors'] &= ~_nonempty_;
				metadata::$objects[$this -> obj]['fields']['SURNAME']['errors'] &= ~_nonempty_;
				
				$no_change_fields = array( 'PASSWORD_MD5', 'CLIENT_TYPE', 'LEGAL_PERSON', 'ADDRESS', 'INN',
					'R_ACCOUNT', 'BANK_NAME', 'K_ACCOUNT', 'BIK', 'CODE_OKPO', 'CODE_OKVED', 'KPP', 'FAX' );
				foreach( $no_change_fields as $no_change_field )
					metadata::$objects[$this -> obj]['fields'][$no_change_field]['no_change'] = true;
			}
			else
			{
				metadata::$objects[$this -> obj]['fields']['OPENID']['no_change'] = true;
			}
		}
	}
	
	/**
	 * Возвращает подготовленные к помещению в БД данные
	 *
	 * @metadatamod Если при редактировании поле "Пароль" не заполнено, то делаем это поле неизменяемым
	 */
	public function get_prepared_fields($raw_fields, $prefix, $mode){
		if(!$raw_fields[$prefix."PASSWORD_MD5"] && $mode=="change"){
			metadata::$objects[$this->obj]["fields"]["PASSWORD_MD5"]["no_change"]=1;
		}
		
		return parent::get_prepared_fields($raw_fields, $prefix, $mode);
	}
	
	/**
	 * Проверяет поле "Email" на корректность
	 */
	public function check_admin_fields( $raw_fields, $prefix, $pk )
	{
		if ( metadata::$objects['CL_CLIENT']['fields']['EMAIL'] )
		{
			// Проверяем уникальность email
			$admin_count = db::sql_select( '
					select count(*) as ADMIN_COUNT from AUTH_USER where EMAIL = :email and CL_CLIENT_ID <> :CL_CLIENT_ID',
				array( 'email' => $raw_fields[$prefix . 'EMAIL'] ) + $pk );
			if ( $admin_count[0]['ADMIN_COUNT'] )
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_client_not_unique_email"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")");
		}
	}
	
	/**
	 * Проверяет поле "Пароль" на корректность
	 */
	public function check_password( $password, $pk = '' )
	{
		if ( params::$params["min_password_length"]["value"] &&
				strlen( $password ) < params::$params["min_password_length"]["value"] )
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_min_password_length"]." (".params::$params["min_password_length"]["value"].")".
				($pk?": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")":""));
		
		if ( params::$params["password_letters_and_numbers"]["value"] &&
				( !preg_match( '/[0-9]/', $password ) || !preg_match( '/[A-Za-z]/', $password ) ) )
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_password_letters_and_numbers"].
				($pk?": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")":""));
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
		
	/**
	 * Дополняем записи информацией о наличие связанных записей в таблице "Администраторы"
	 *
	 * @see table::get_index_records()
	 */
	public function get_index_records( &$request, $mode, $list_mode, $include = 0, $exclude = array() )
	{
		$records = parent::get_index_records( $request, $mode, $list_mode, $include, $exclude );
		
		if ( true )
		{
			$client_admins = db::sql_select( '
					select CL_CLIENT.CL_CLIENT_ID, AUTH_USER.AUTH_USER_ID
					from CL_CLIENT left join AUTH_USER on CL_CLIENT.CL_CLIENT_ID = AUTH_USER.CL_CLIENT_ID
					where CL_CLIENT.CL_CLIENT_ID IN ( ' . $this -> index_records_in . ' )' );
			$client_admins = lib::array_reindex( $client_admins, 'CL_CLIENT_ID' );
			
			foreach ( $records as $record_index => $record_item )
				$records[$record_index]['AUTH_USER_ID'] = $client_admins[$record_item['CL_CLIENT_ID']]['AUTH_USER_ID'];
		}
		
		return $records;
	}
	
	/**
	 * Добавляем в списке колонку "Администратор"
	 *
	 * @see table::ext_index_header()
	 */
	public function ext_index_header( $mode )
	{
		$header = parent::ext_index_header( $mode );
		
		if ( true )
			$header = array_merge( $header, array( 'AUTH_USER_ID' => array( 'title' => metadata::$lang['lang_administrator'], 'type'=>'checkbox' ) ) );
		
		return $header;
	}
	
	/**
	 * Добавляем в случае необходимости фильтр по OpenID
	 *
	 * @see table::get_index_query_components()
	 */
	public function get_index_query_components( &$request, $mode, $list_mode )
	{
		$components = parent::get_index_query_components( $request, $mode, $list_mode );
		
		if ( params::$params['openid_mode']['value'] )
		{
			switch ( $request['_f_IS_OPENID'] )
			{
				case '1': // OpenID пользователь
					$components[2] .= ' and ( CL_CLIENT.OPENID <> \'\' and CL_CLIENT.OPENID is not null ) ';
					break;
				case '0'; // Обычный пользователь
					$components[2] .= ' and not ( CL_CLIENT.OPENID <> \'\' and CL_CLIENT.OPENID is not null ) ';
					break;
			}
		}
		
		return $components;
	}
	
	/**
	 * Проверка корректности введенного пароля на стороне браузера
	 *
	 * @see table::html_card()
	 */
	public function html_card( $mode, &$request )
	{
		list( $title, $html ) = parent::html_card( $mode, $request );
		
		if ( params::$params["min_password_length"]["value"] || params::$params["password_letters_and_numbers"]["value"] )
		{
			$html .= <<<HTM
			
<script type="text/javascript">
	CheckForm.validate_ext = function()
	{
		var oPassword = this.oForm['_form_PASSWORD_MD5'];
HTM;
			if ( $min_password_length = params::$params["min_password_length"]["value"] )
				$html .= <<<HTM
		
		if ( oPassword && oPassword.value.length > 0 && oPassword.value.length < {$min_password_length} )
		{
			alert( Dictionary.translate( 'lang_min_password_length' ) + ': ' + {$min_password_length} );
			try { oPassword.focus() } catch (e) {};
			return false;
		}
HTM;
			if ( params::$params["password_letters_and_numbers"]["value"] )
				$html .= <<<HTM
		
		if ( oPassword && oPassword.value.length > 0 &&
			( !( /[0-9]/.test( oPassword.value ) ) || !( /[A-Za-z]/.test( oPassword.value ) ) ) )
		{
			alert( Dictionary.translate( 'lang_password_letters_and_numbers' ) );
			try { oPassword.focus() } catch (e) {};
			return false;
		}
HTM;
			$html .= <<<HTM
		
		return true;
	}
</script>
HTM;
		}
		
		return array( $title, $html );
	}
}
?>
