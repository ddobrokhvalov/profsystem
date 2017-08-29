<?php

 include_once(params::$params["adm_data_server"]["value"]."class/te/tool/system_global_params/system_global_params.php");
 
/**
 * Класс для редактирования локальных параметров админки
 * Переписывает значениями из БД дефолтные значения параметров типа L (Local)
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @author Alexandr Vladykin <avladykin@rbc.ru>
 * @copyright  Copyright (c) 2007 RBC SOFT
 */

 class system_auth_user_params extends system_global_params {
	protected $parameter_type = 'LOCAL';
	
	/**
	 * Конструктор. Заполняет свойства объекта
	 *
	 * @param string $obj			Название конструируемого объекта
	 * @param string $full_object	Ссылка на полный объект, если ее нет, то в качестве такой ссылки используется сам конструируемый объект
	*/
	
	function __construct($obj, $full_object=""){
		parent::__construct($obj, $full_object);
		$this->load_params();
	}

	/**
	 * Проверяет, существует ли данный параметр в БД
	 * @param string $system_name - название параметра
	*/
	
	public function is_present_parameter_in_db ($system_name) {
		$res=$this->get_parameter_from_db($system_name);
		return sizeof ($res);
	}
		
	/**
	 * Выдает значение параметра в БД
	 * @param string $system_name - название параметра
	*/
	
	public function get_parameter_from_db ($system_name) {
		$res=db::sql_select(
			'SELECT 
				*
			 FROM 
				SYSTEM_AUTH_USER_PARAMS 
			 WHERE 
				SYSTEM_NAME=:system_name 
				AND 
				AUTH_USER_ID=:auth_user_id
			',
			array(
			 'system_name'=>$system_name, 
			 'auth_user_id'=>$_SESSION['AUTH_USER_ID']
			)
		);
		return $res[0];
	}

	/**
	 * Вносит значение параметра в БД
	 * @param string $system_name - название параметра
	 * @param string $new_value - новое значение параметра
	*/
	
	protected function insert_db_parameter($system_name, $new_value) {
		db::insert_record(
				'SYSTEM_AUTH_USER_PARAMS', 
					array(
						'SYSTEM_NAME'=>$system_name, 
						'VALUE'=>$new_value,
						'AUTH_USER_ID'=>$_SESSION['AUTH_USER_ID'],
					)
		 );
	}
	
	/**
	 * Обновляет значение параметра в БД
	 * @param string $system_name - название параметра
	 * @param string $new_value - новое значение параметра
	*/
	protected function update_db_parameter($system_name, $new_value) {
		db::update_record(
			'SYSTEM_AUTH_USER_PARAMS', 
			array(
				'VALUE'=>$new_value,
			), 
			'', 
			array(
				'SYSTEM_NAME'=>$system_name,
				'AUTH_USER_ID'=>$_SESSION['AUTH_USER_ID']
			)
		);
	}

	
	/**
	 * Доступ к настройкам должны иметь все пользователи
	 *
	 * @see	object::is_permitted_to
	 */
	 public function is_permitted_to($ep_type, $pk="", $throw_exception=false){
		return params::$params['params_access']['value'];
	}
 }
?>
