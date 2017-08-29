<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Администраторы"
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2006 RBC SOFT
 *
 * @todo Синхронизация и клонирование администраторов не должна работать работать в случае, если клиентская и административная часть работают на разных серверах. Пока эта возможность не реализована, в коде на соответствующих местах расставлены заглушки
 */
class auth_user extends table{

	/**
	 * Не позволяем добавлять системного пользователя
	 */
	public function exec_add($raw_fields, $prefix){
		if($raw_fields[$prefix."LOGIN"]=="system"){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_cannot_operate_user_system"]);
		}
		
		// Дополнительная проверка корректности ввода пароля
		$this -> check_password( $raw_fields, $prefix );
		
		$id=parent::exec_add($raw_fields, $prefix);
		
		$this->add_default_favourites($id);
		
		return $id;
	}
	
	/**
	 * Не позволяем редактировать системного пользователя. Обновление зависимой записи в таблице CL_CLIENT
	 */
	public function exec_change($raw_fields, $prefix, $pk)
	{
		$this->full_object->check_user_system($pk, true);
		
		if ( true and isset( metadata::$objects['CL_CLIENT'] ) )
		{
			// Выясняем наличие связанной записи в таблице "Пользователи"
			$admin_record = $this -> full_object -> get_change_record( $pk );
			
			if ( $admin_record['CL_CLIENT_ID'] )
				$this -> check_client_fields( $raw_fields, $prefix, $pk, $admin_record['CL_CLIENT_ID'] );
		}
		
		// Дополнительная проверка корректности ввода пароля
		$this -> check_password( $raw_fields, $prefix, $pk );
		
		parent::exec_change($raw_fields, $prefix, $pk);
		
		if ( true and isset( metadata::$objects['CL_CLIENT'] ) and $admin_record['CL_CLIENT_ID'] )
		{
			$admin_record = $this -> full_object -> get_change_record( $pk );
			
			// Обновление связанной записи в таблице "Пользователи"
			$client_record = array( 'PASSWORD_MD5' => $admin_record['PASSWORD_MD5'], 'SURNAME' => $admin_record['SURNAME'],
				'NAME' => $admin_record['NAME'], 'PATRONYMIC' => $admin_record['PATRONYMIC'] );
			
			if ( metadata::$objects['CL_CLIENT']['fields']['LOGIN'] ) $client_record['LOGIN'] = $admin_record['LOGIN'];
			if ( metadata::$objects['CL_CLIENT']['fields']['EMAIL'] ) $client_record['EMAIL'] = $admin_record['EMAIL'];
			
			db::update_record( 'CL_CLIENT', $client_record, '', array( 'CL_CLIENT_ID' => $admin_record['CL_CLIENT_ID'] ) );
		}
	}

	/**
	 * После удаления пользователя пытаемся удалить его индивидуальную роль, если это можно. Не позволяем удалять системного пользователя
	 * 
	 * Роль удаляется вне финализации удаления потому что этот класс заведомо больше никем не наследуется и роль может удалиться только в
	 * случае отсутствия пользователя, к которому она привязана.
	 */
	public function exec_delete($pk){
		$this->full_object->check_user_system($pk, true);
		parent::exec_delete($pk);
		$auth_system_role_obj = object::factory("AUTH_SYSTEM_ROLE");
		$auth_system_role_obj->delete_ind_role($pk["AUTH_USER_ID"]);
		$auth_system_role_obj->__destruct();
	}
	
	/**
	 * Удаление зависимой записи из таблицы CL_CLIENT
	 *
	 * @see table::ext_finalize_delete()
	 */
	public function ext_finalize_delete( $pk, $partial = false )
	{
		parent::ext_finalize_delete( $pk, $partial );
		
		if ( true and isset( metadata::$objects['CL_CLIENT'] ) )
		{
			$admin_record = $this -> full_object -> get_change_record( $pk );
			
			if ( $admin_record['CL_CLIENT_ID'] )
				object::factory( 'CL_CLIENT' ) -> exec_delete( array( 'CL_CLIENT_ID' => $admin_record['CL_CLIENT_ID'] ) );
		}
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Действие - карточка добавления
	 *
	 * @metadatamod При добавлении делаем поле "Пароль" обязательным
	 */
	public function action_add(){
		metadata::$objects[$this->obj]["fields"]["PASSWORD_MD5"]["errors"]|=_nonempty_;
		parent::action_add();
	}

	/**
	 * Не позволяем смотреть на карточку системного пользователя
	 */
	public function action_change(){
		$pk=$this->primary_key->get_from_request();
		$this->full_object->check_user_system($pk, true);
		parent::action_change();
	}

	/**
	 * Не позволяем привязывать ничего к системному пользователю
	 *
	 * Только на уровне интерфейса, потому что такая привязка ничем системному пользователю не помешает
	 */
	public function action_m2m(){
		$pk=$this->primary_key->get_from_request();
		$this->full_object->check_user_system($pk, true);
		parent::action_m2m();
	}
	
	/**
	 * Клонирование записи об администраторе в таблицу CL_CLIENT
	 */
	public function action_set_client()
	{
		$pk = $this -> primary_key -> get_from_request( true );
		$this -> full_object -> check_user_system( $pk, true );
		
		if ( true and isset( metadata::$objects['CL_CLIENT'] ) )
		{
			$admin_record = $this -> full_object -> get_change_record( $pk );
			
			$this -> check_client_fields( $admin_record, '', $pk );
			
			$client_record = array( 'PASSWORD_MD5' => $admin_record['PASSWORD_MD5'], 'CLIENT_TYPE' => 0,
				'SURNAME' => $admin_record['SURNAME'], 'NAME' => $admin_record['NAME'], 'PATRONYMIC' => $admin_record['PATRONYMIC'] );
			
			if ( metadata::$objects['CL_CLIENT']['fields']['LOGIN'] ) $client_record['LOGIN'] = $admin_record['LOGIN'];
			if ( metadata::$objects['CL_CLIENT']['fields']['EMAIL'] ) $client_record['EMAIL'] = $admin_record['EMAIL'];
			
			db::insert_record( 'CL_CLIENT', $client_record ); $client_id = db::last_insert_id( 'CL_CLIENT_SEQ' );
			
			db::update_record( 'AUTH_USER', array( 'CL_CLIENT_ID' => $client_id ), '', $pk );
		}
		
		$this -> url -> redirect();
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
	 * Проверяет поля "Логин" и "Email" на корректность
	 */
	public function check_client_fields( $raw_fields, $prefix, $pk, $client_id = '' )
	{
		if ( metadata::$objects['CL_CLIENT']['fields']['LOGIN'] )
		{
			// Проверяем заполненность логина
			if ( ( metadata::$objects['CL_CLIENT']['fields']['LOGIN']['errors'] & _nonempty_ ) && $raw_fields[$prefix . 'LOGIN'] == '' )
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_client_empty_login"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")");
				
			// Проверяем уникальность логина
			$client_count = db::sql_select( '
					select count(*) as CLIENT_COUNT from CL_CLIENT where LOGIN = :login and CL_CLIENT_ID <> :CL_CLIENT_ID',
				array( 'login' => $raw_fields[$prefix . 'LOGIN'], 'CL_CLIENT_ID' => $client_id ) );
			if ( $client_count[0]['CLIENT_COUNT'] )
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_client_not_unique_login"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")");
		}
		
		if ( metadata::$objects['CL_CLIENT']['fields']['EMAIL'] )
		{
			// Проверяем заполненность email
			if ( ( metadata::$objects['CL_CLIENT']['fields']['EMAIL']['errors'] & _nonempty_ ) && $raw_fields[$prefix . 'EMAIL'] == '' )
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_client_empty_email"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")");
				
			// Проверяем уникальность email
			$client_count = db::sql_select( '
					select count(*) as CLIENT_COUNT from CL_CLIENT where EMAIL = :email and CL_CLIENT_ID <> :CL_CLIENT_ID',
				array( 'email' => $raw_fields[$prefix . 'EMAIL'], 'CL_CLIENT_ID' => $client_id ) );
			if ( $client_count[0]['CLIENT_COUNT'] )
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_client_not_unique_email"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")");
		}
	}
	
	/**
	 * Проверяет поле "Пароль" на корректность
	 */
	public function check_password( $raw_fields, $prefix, $pk = '' )
	{
		if ( params::$params["min_password_length"]["value"] && $raw_fields[$prefix . 'PASSWORD_MD5'] !== '' &&
				strlen( $raw_fields[$prefix . 'PASSWORD_MD5'] ) < params::$params["min_password_length"]["value"] )
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_min_password_length"]." (".params::$params["min_password_length"]["value"].")".
				($pk?": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")":""));
		
		if ( params::$params["password_letters_and_numbers"]["value"] && $raw_fields[$prefix . 'PASSWORD_MD5'] !== '' &&
				( !preg_match( '/[0-9]/', $raw_fields[$prefix . 'PASSWORD_MD5'] ) || !preg_match( '/[A-Za-z]/', $raw_fields[$prefix . 'PASSWORD_MD5'] ) ) )
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_password_letters_and_numbers"].
				($pk?": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")":""));
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Дополняем записи информацией о наличие связанных записей в таблице "Пользователи"
	 *
	 * @see table::get_index_records()
	 */
	public function get_index_records( &$request, $mode, $list_mode, $include = 0, $exclude = array() )
	{
		$records = parent::get_index_records( $request, $mode, $list_mode, $include, $exclude );
		
		if ( true and isset( metadata::$objects['CL_CLIENT'] ) )
		{
			$admin_clients = db::sql_select( '
					select AUTH_USER.AUTH_USER_ID, CL_CLIENT.CL_CLIENT_ID
					from AUTH_USER left join CL_CLIENT on AUTH_USER.CL_CLIENT_ID = CL_CLIENT.CL_CLIENT_ID
					where AUTH_USER.AUTH_USER_ID IN ( ' . $this -> index_records_in . ' )' );
			$admin_clients = lib::array_reindex( $admin_clients, 'AUTH_USER_ID' );
			
			foreach ( $records as $record_index => $record_item )
				$records[$record_index]['CL_CLIENT_ID'] = $admin_clients[$record_item['AUTH_USER_ID']]['CL_CLIENT_ID'];
		}
		
		return $records;
	}
	
	/**
	 * Добавляем операцию клонирования администратора в пользователи
	 *
	 * @see table::get_index_ops()
	 */
	public function get_index_ops( $record )
	{
		$ops = parent::get_index_ops( $record );
		
		if ( true and isset( metadata::$objects['CL_CLIENT'] ) )
		{
			$pk = $this -> primary_key -> get_from_record( $record );
			
			if ( $this -> full_object -> is_applied_to( 'change', false ) && $this -> is_ops_permited( 'change', $pk[$this -> autoinc_name] ) && !$record['CL_CLIENT_ID'] )
				$ops['_ops'][] = array( 'name' => 'set_client', 'alt' => metadata::$lang['lang_set_client'],
					'url' => $this -> url -> get_url( 'set_client', array( 'pk' => $pk ) ), 'confirm' => true, 'confirm_question' => metadata::$lang['lang_set_client_confirm'] );
		}
		
		return $ops;
	}
	
	/**
	 * Добавляем в списке колонку "Пользователи"
	 *
	 * @see table::ext_index_header()
	 */
	public function ext_index_header( $mode )
	{
		$header = parent::ext_index_header( $mode );
		
		if ( true and isset( metadata::$objects['CL_CLIENT'] ) )
			$header = array_merge( $header, array( 'CL_CLIENT_ID' => array( 'title' => metadata::$lang['lang_user'], 'type'=>'checkbox' ) ) );
		
		return $header;
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
	
////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Проверяет, является ли указанный пользователь системным
	 *
	 * @param array $pk					Массив с первичным ключом записи
	 * @param boolean $throw_exception	Бросить исключение в случае системного пользователя
	 * @return boolean
	 */
	public function check_user_system($pk, $throw_exception){
		$admin=$this->full_object->get_change_record($pk);
		if($admin["LOGIN"]=="system"){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_cannot_operate_user_system"]);
		}
		return $admin["LOGIN"]=="system";
	}
	
	/**
	* Добавляет стандартные FAVOURITES к добавленному пользователю
	*/
	
	public function add_default_favourites($id) {
		$default_lang = params::$params['default_interface_lang']['default_value'];
		if (!$default_lang) $default_lang = params::$params['default_interface_lang']['value'];
		
		$names=parse_ini_file(params::$params['adm_data_server']['value'].'/lang/'.$default_lang.'/te/default_favourite.ini');
		
		$fav_obj = object::factory('FAVOURITE');
		
		// разделы
		if (params::$params['install_cms']['value'])
			$fav_obj -> exec_add(
				array (
					'TITLE' => $names['lang_uncheckable_default_favourite_page'], 
					'URL' => '/index.php?obj=PAGE', 
					'AUTH_USER_ID' => $id,
					'FAVOURITE_ORDER' => 10
				), ''
			);
		
		// административный интерфейс
		$fav_obj -> exec_add(
			array (
				'TITLE' => $names['lang_uncheckable_default_favourite_system_auth_params_tool'], 
				'URL' => '/index.php?obj=SYSTEM_AUTH_USER_PARAMS_TOOL', 
				'AUTH_USER_ID' => $id,
				'FAVOURITE_ORDER' => 20
			), ''
		);
		
		// Личные данные
		$fav_obj -> exec_add(
			array (
				'TITLE' => $names['lang_uncheckable_default_favourite_personal_info_tool'], 
				'URL' => '/index.php?obj=PERSONAL_INFO_TOOL', 
				'AUTH_USER_ID' => $id,
				'FAVOURITE_ORDER' => 30
			), ''
		);
	}
}
?>
