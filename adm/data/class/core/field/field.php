<?php
/**
 * Класс работы с полями
 *
 * Содержит пять групп методов<br>
 * index_		- группа методов, формирующих html-код для отображения тех или иных типов полей в списке<br>
 * change_		- группа методов, формирующих html-код для отображения тех или иных типов полей в форме добавления или изменения записи<br>
 * check_type_	- группа методов, проверяющих корректность данных, переданных формой добавления/редактирования записи для укладки в БД<br>
 * check_		- группа методов, проверяющих корректность данных по префиксам (параметру errors)<br>
 * prepare_		- группа методов, преобразующих данные, переданных формой добавления/редактирования записи, к виду пригодному для хранения в БД
 *
 * @package    RBC_Contents_5_0
 * @subpackage core
 * @copyright  Copyright (c) 2006 RBC SOFT
 *
 * @todo Оказалось, что методы check_type_ нигде кроме типа text не нужно, так как проверки на число и так делаются автоматически. Надо бы сделать этот метод необязательным
 */
class field extends object_name{

	/**
	 * Описания типов ошибок (префиксов) и их обработчиков
	 * @var array
	 */
	protected $err_info;

	/**
	 * Таблица сответствий типов полей и префиксов. Эти префиксы будут автоматически прикладываться к полям соответствующих типов, даже если в дефах эти префиксы не указаны
	 * @var array
	 */
	protected $errors_map;

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Фабрика. Возвращает полностью готовый объект для работы с полями
	 *
	 * @param string $obj		название конструируемого объекта
	 * @param string $object	ссылка на родительский объект
	 * @return object field
	 */
	static public function factory($obj){
		if(metadata::$objects[$obj]["class"]){
			$class=metadata::$objects[$obj]["class"];
			$file_name=params::$params["adm_data_server"]["value"]."class/".metadata::$objects[$obj]["object_level"]."/".metadata::$objects[$obj]["type"]."/{$class}/field_{$class}.php";
			if(file_exists($file_name)){
				include_once($file_name);
				$class_name="field_{$class}";
			}else{
				$class_name="field";
			}
		}else{
			$class_name="field";
		}
		return new $class_name($obj);
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Конструктор. Заполняет $err_info и $errors_map
	 *
	 * @param string $obj	название конструируемого объекта
	 * @param string $object	ссылка на родительский объект
	 */
	function __construct($obj){
		parent::__construct($obj);

		$this->err_info=array(
			_nonempty_=>	array('NAME'=>'_nonempty_',		'FUNC'=>'check_nonempty',		'MESS'=>metadata::$lang["lang_nonempty_"]),
			_email_=>		array('NAME'=>'_email_',		'FUNC'=>'check_email',			'MESS'=>metadata::$lang["lang_email_"]),
			_date_=>		array('NAME'=>'_date_',			'FUNC'=>'check_date',			'MESS'=>metadata::$lang["lang_date_"]),
			_time_=>		array('NAME'=>'_time_',			'FUNC'=>'check_time',			'MESS'=>metadata::$lang["lang_time_"]),
			_datetime_=>	array('NAME'=>'_datetime_',		'FUNC'=>'check_datetime',		'MESS'=>metadata::$lang["lang_datetime_"]),
			_alphastring_=>	array('NAME'=>'_alphastring_',	'FUNC'=>'check_alphastring',	'MESS'=>metadata::$lang["lang_alphastring_"]),
			_login_=>		array('NAME'=>'_login_',		'FUNC'=>'check_login',			'MESS'=>metadata::$lang["lang_login_"]),
			_dirname_=>		array('NAME'=>'_dirname_',		'FUNC'=>'check_dirname',		'MESS'=>metadata::$lang["lang_login_"]),
			_int_=>			array('NAME'=>'_int_',			'FUNC'=>'check_int',			'MESS'=>metadata::$lang["lang_int_"]),
			_float_=>		array('NAME'=>'_float_',		'FUNC'=>'check_float',			'MESS'=>metadata::$lang["lang_float_"]),
			
			// Поддержка следующих префиксов в административной части не реализована
//			_radio_=>		array('NAME'=>'_radio_',		'FUNC'=>'check_nonempty',		'MESS'=>metadata::$lang["lang_nonempty_"]),
//			_radioalt_=>	array('NAME'=>'_radioalt_',		'FUNC'=>'check_nonempty',		'MESS'=>metadata::$lang["lang_nonempty_"]),
		);
		$this->errors_map=array(
			"text"=>	_no_error_,
			"int"=>		_int_,
			"float"=>	_float_,
			"checkbox"=>_no_error_,
			"textarea"=>_no_error_,
			"order"=>	_int_,
			"date"=>	_date_,
			"datetime"=>_datetime_,
			"select1"=>	_no_error_,
			"select2"=>	_int_,
			"parent"=>	_int_|_nonempty_,
			"img"=>		_no_error_,
			"file"=>	_no_error_,
		);
	}

	/**
	 * Возвращает строку с префиксами для поля по его типу и параметру errors 
	 *
	 * @param array $field_descr	описание поля в формате def-файла
	 * @return int
	 */
	public function get_prefixes($field_descr){
		$errors=$field_descr["errors"] | $this->errors_map[$field_descr["type"]];
		foreach($this->err_info as $err_code=>$err_descr){
			if($errors & $err_code){
				$prefix.=$err_descr["NAME"];
			}
		}
		return $prefix;
	}

	/**
	 * Проверка значения поля по типу. Подготавливает поле к формату хранения в БД
	 *
	 * Диспетчеризует проверку по соответствующим методам
	 * Если проверка прошла успешно, то возвращает значение, подготовленное к укладке в БД
	 * Для полей типа img и file проверка осуществляется после закачки файла
	 *
	 * @param mixed $content		сырое значение поля
	 * @param array $field_descr	описание поля в формате def-файла
	 * @param array $field_name		название поля
	 * @return mixed
	 */
	public function get_prepared($content, $field_descr, $field_name = ''){
		$check_type_method="check_type_".$field_descr["type"];
		$prepare_method="prepare_".$field_descr["type"];
		$this->$check_type_method($content, $field_descr);
		if($field_descr["type"]!="img" && $field_descr["type"]!="file"){
			$this->check_content($content, $field_descr, $field_name);
			$content=$this->$prepare_method($content, $field_descr, $field_name);
		}else{
			$content=$this->$prepare_method($content, $field_descr, $field_name);
			$this->check_content($content, $field_descr, $field_name);
		}
		return $content;
	}

	/**
	 * Проверка значения поля по префиксам. В случае провала проверки будет брошено исключение.
	 *
	 * @param mixed $content		сырое значение поля
	 * @param array $field_descr	описание поля в формате def-файла
	 * @param array $field_name		название поля
	 */
	public function check_content($content, $field_descr, $field_name = ''){
		$errors=$field_descr["errors"] | $this->errors_map[$field_descr["type"]];
		foreach($this->err_info as $err_code=>$err_descr){
			if($errors & $err_code){
				$check_method=$err_descr["FUNC"];
				if(!$this->$check_method($content)){
					throw new Exception($this->te_object_name.": ".metadata::$lang["lang_wrong_format_of_field"]." '{$field_descr["title"]}': {$err_descr["MESS"]}");
				}
			}
		}
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	public function index_			($content){return $content;} // Для сбора произвольной таблички с полями без типов
	public function index__ops		($content){return $content;} // Для спецтипа стандартных операций
	public function index__link		($content){return $content;} // Для спецтипа ссылок
	public function index__group	($content){return $content;} // Для спецтипа чекбоксов групповых операций
	public function index__list		($content){return $content;} // Для спецтипа списка значений

	public function index_text		($content){return $content;}
	public function index_textarea	($content){return $content;}
	public function index_checkbox	($content){return $content;}
	public function index_order		($content){return $content;}
	public function index_select1	($content){return $content;}
	public function index_select2	($content){return $content;}
	public function index_parent	($content){return $content;}
	public function index_int		($content){return $content;}
	public function index_float		($content){return $content;}

	public function index_date( $content )
	{
		return lib::unpack_date( $content, 'short' );
	}
	public function index_datetime( $content, $view_type='long' )
	{
		if (!$view_type) $view_type='long';
		return lib::unpack_date( $content, $view_type );
	}
	
	public function index_ip ( $content) 
	{
		return long2ip($content);
	}

	public function index_img		($content){return $content;}
	public function index_file		($content){return $content;}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	public function change_text		($content){return $content;}
	public function change_textarea	($content){return $content;}
	public function change_checkbox	($content){return $content;}
	public function change_order	($content){return $content;}
	public function change_select1	($content){return $content;}
	public function change_select2	($content){return $content;}
	public function change_parent	($content){return $content;}
	public function change_int		($content){return $content;}
	public function change_float	($content){return $content;}
	
	public function change_date( $content )
	{
		return $this -> index_date( $content );
	}
	
	public function change_datetime( $content )
	{
		return $this -> index_datetime( $content );
	}
	
	public function change_img		($content){return $content;}
	public function change_file		($content){return $content;}
	public function change_ip		($content){return long2ip($content);}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	protected function check_type_text		($content, $field_descr){
		$max_size=4000;
		if(mb_strlen($content, params::$params["encoding"]["value"])>$max_size){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_field_size"]." '{$field_descr["title"]}' ".metadata::$lang["lang_not_greater_than"]." {$max_size} ".metadata::$lang["lang_bytes_rp"]);
		}
	}

	protected function check_type_textarea	($content, $field_descr){}
	protected function check_type_checkbox	($content, $field_descr){}
	protected function check_type_order		($content, $field_descr){}
	protected function check_type_select1	($content, $field_descr){}
	protected function check_type_select2	($content, $field_descr){}
	protected function check_type_parent	($content, $field_descr){}
	
	protected function check_type_int( $content, $field_descr ) {
		if ( $content != '' && !preg_match( '/^\-?\+?\d+$/', $content ) )
			throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_wrong_format_of_field'] . " '" . $field_descr['title'] . "': " . metadata::$lang['lang_int_'] );
	}
	protected function check_type_float( $content, $field_descr ) {
		if ( $content !== '' && !preg_match( '/^\-?\+?\d+[\.,]?\d*$/', $content ) )
			throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_wrong_format_of_field'] . " '" . $field_descr['title'] . "': " . metadata::$lang['lang_float_'] );
	}
	protected function check_type_date		($content, $field_descr){}
	protected function check_type_datetime	($content, $field_descr){}
	protected function check_type_img		($content, $field_descr){}
	protected function check_type_file		($content, $field_descr){}
	protected function check_type_ip		($content, $field_descr)
	{
		if (ip2long($content)===FALSE)
			throw new Exception( metadata::$lang['lang_wrong_format_of_field'].' '.$field_descr['title'].": '$content'" );
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	public function check_nonempty($content){
		return ( trim( $content ) !== '' );
	}

	public function check_login($content){
		return ( $content === '' || is_null( $content ) || preg_match( '/^[a-z0-9_]+$/i', $content ) );
	}

	public function check_dirname($content){
		return ( $content === '' || is_null( $content ) || preg_match( '/^[a-z0-9_\.\[\]-]+$/i', $content ) );
	}
	
	public function check_alphastring($content){
		return ( $content === '' || is_null( $content ) || preg_match( '/^[a-z_]+$/i', $content ) );
	}

	public function check_int($content) {
		return ( $content === '' || is_null( $content ) || preg_match( '/^\-?\+?\d+$/', $content ) );
	}

	public function check_float($content) {
		return ( $content === '' || is_null( $content ) || preg_match( '/^\-?\+?\d+[\.,]?\d*$/', $content ) );
	}

	public function check_email($content) {
		return ( $content === '' || is_null( $content ) || preg_match( '/^[a-z0-9_\.-]+@[a-z0-9_\.-]+\.[a-z]{2,}$/i', $content ) );
	}

	public function check_date($content) {
		return ( $content === '' || is_null( $content ) || ( 
			preg_match( '/^(\d{2})\.(\d{2})\.(\d{4})$/', $content, $match ) && 
			checkdate ( $match[2], $match[1], $match[3] ) ) );
	}

	public function check_time($content) {
		return ( $content === '' || is_null( $content ) || ( 
			preg_match( '/^(\d{2})\:(\d{2})$/', $content, $match ) && 
			( $match[1] >= 0 && $match[1] <= 23 && $match[2] >= 0 && $match[2] <= 59 ) ) );
	}

	public function check_datetime($content){
		return ( $content === '' || is_null( $content ) || ( 
			preg_match( '/^(\d{2})\.(\d{2})\.(\d{4}) (\d{2})\:(\d{2})$/', $content, $match ) && 
			checkdate ( $match[2], $match[1], $match[3] ) &&
			( $match[4] >= 0 && $match[4] <= 23 && $match[5] >= 0 && $match[5] <= 59 ) ) );
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	public function prepare_text		($content, $field_descr, $field_name){return $content;}
	public function prepare_textarea	($content, $field_descr, $field_name){return $content;}

	public function prepare_checkbox( $content, $field_descr, $field_name )
	{
		return ($content ? 1 : 0);
	}

	public function prepare_order	($content, $field_descr, $field_name){return $content;}
	public function prepare_select1	($content, $field_descr, $field_name){return $content;}
	public function prepare_select2	($content, $field_descr, $field_name){return $content;}
	public function prepare_parent	($content, $field_descr, $field_name){return $content;}

	public function prepare_int( $content, $field_descr, $field_name )
	{
		$null = $field_descr['is_null']?null:0;
		return $content !== '' ? $content : $null;
	}

	public function prepare_float( $content, $field_descr, $field_name )
	{
		$content = str_replace( ',', '.', $content );
		$null = $field_descr['is_null']?null:0;
		return $content !== '' ? $content : null;
	}

	public function prepare_date( $content, $field_descr, $field_name )
	{
		return lib::pack_date( $content, 'short' );
	}

	public function prepare_datetime( $content, $field_descr, $field_name )
	{
		return lib::pack_date( $content, 'long' );
	}

	public function prepare_img( $content, $field_descr, $field_name )
	{
		return $this -> prepare_file( $content, $field_descr, $field_name );
	}

	public function prepare_file( $content, $field_descr, $field_name )
	{
		if ( isset( $_FILES[$field_name.'_file'] ) )
		{
			if ( $_FILES[$field_name.'_file']['error'] == UPLOAD_ERR_INI_SIZE )
				throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_wrong_format_of_field'] . " '" . $field_descr['title'] . "': " . metadata::$lang['lang_upload_err_ini_size'] . " ( " . ini_get( 'upload_max_filesize' ) . " )" );
			elseif ( $_FILES[$field_name.'_file']['error'] == UPLOAD_ERR_FORM_SIZE )
				throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_wrong_format_of_field'] . " '" . $field_descr['title'] . "': " . metadata::$lang['lang_upload_err_form_size'] );
			elseif ( $_FILES[$field_name.'_file']['error'] == UPLOAD_ERR_PARTIAL )
				throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_wrong_format_of_field'] . " '" . $field_descr['title'] . "': " . metadata::$lang['lang_upload_err_partial'] );
			elseif ( $_FILES[$field_name.'_file']['error'] == UPLOAD_ERR_OK )
			{
				$upload_dir = isset( $field_descr['upload_dir'] ) ? $field_descr['upload_dir'] : params::$params['upload_dir']['value'];
				$upload_file = upload::upload_file( $_FILES[$field_name.'_file'], params::$params['common_htdocs_server']['value'] . $upload_dir, true, false );
				return str_replace( params::$params['common_htdocs_server']['value'], params::$params['common_htdocs_http']['value'], $upload_file);
			}
		}
		return $content;
	}
	
	public function prepare_ip ($content, $field_descr, $field_name ) 
	{
		if ($content)
			return sprintf("%u", ip2long($content)); // делаем беззнаковым
		return $content;
	}
}
?>