<?PHP
/**
 * Класс для редактирования глобальных параметров системы из админки
 * Переписывает значениями из БД дефолтные значения параметров типа G (Global)
 *
 * @package		RBC_Contents_5_0
 * @subpackage te
 * @author Alexandr Vladykin <avladykin@rbc.ru>
 * @copyright	Copyright (c) 2007 RBC SOFT
 */
 
 class system_global_params extends tool {
 	protected $parameter_type = 'GLOBAL';
 	
 	private $params = array();
 	private $form_prefix = '_f_';
 	private $form_element_prefix = '_form_';
 	
	
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
	 * Страница по умолчанию
	*/
	protected function action_index(){
		list($this->title, $this->body)=$this->full_object->html_card($_REQUEST);		
	}
		
	/**
	 * Вызывается при изменении параметров пользователем
	*/		

	protected function action_changed() {
		foreach ($this->params as $system_name=>$param) {
			$new_value=$_REQUEST[$this->form_element_prefix.$system_name];
			if (/*$new_value &&*/ ($new_value!=$param['value'])) {
				$this->set_parameter_new_value($system_name, $new_value);
			}
		}
		
		$add_params=array();
		if ($this->params['title_separator']['value']!=$_REQUEST[$this->form_element_prefix.'title_separator']) 
			$add_params['splitter_changed']=1;
		
		// Сохраняем в куках выбранный размер шрифта интерфейса
		setcookie('font_size', $_REQUEST[$this->form_element_prefix.'default_font_size']);
		
		$this->url->redirect("", array('add_params'=>$add_params));
	}
		
	/**
	 * Изменяет значение параметра
	 * @param string $system_name - название параметра
	 * @param string $new_value - новое значение параметра
	*/

	public function set_parameter_new_value ($system_name, $new_value) {
		$this->preprocess_set_parameter_new_value($system_name, $new_value);

		if ($this->is_present_parameter_in_db($system_name)) {
			$this->update_db_parameter($system_name, $new_value);
		}
		else {
			$this->insert_db_parameter($system_name, $new_value);
		}
		$old_value = params::$params[$system_name]['value'];
		system_params::set_parameter_value($system_name, $new_value);
		
		if ($old_value!=$new_value)
			$this->log_register($system_name, $old_value, $new_value);
	}
		
		
	/**
	 * Проверяет, существует ли данный параметр в БД
	 * @param string $system_name - название параметра
	*/
	public function is_present_parameter_in_db ($system_name) {
		$res=db::sql_select('SELECT COUNT(*) AS CNT FROM SYSTEM_GLOBAL_PARAMS WHERE SYSTEM_NAME=:system_name', array('system_name'=>$system_name));
		return $res[0]['CNT'];
	}

	/**
	 * Вносит значение параметра в БД
	 * @param string $system_name - название параметра
	 * @param string $new_value - новое значение параметра
	*/
	protected function insert_db_parameter($system_name, $new_value) {
		db::insert_record('SYSTEM_GLOBAL_PARAMS', array('SYSTEM_NAME'=>$system_name, 'VALUE'=>$new_value));
	}

	/**
	 * Обновляет значение параметра в БД
	 * @param string $system_name - название параметра
	 * @param string $new_value - новое значение параметра
	*/

	protected function update_db_parameter($system_name, $new_value) {
		db::update_record('SYSTEM_GLOBAL_PARAMS', array('SYSTEM_NAME'=>$system_name, 'VALUE'=>$new_value), '', array('SYSTEM_NAME'=>$system_name));
	}

	/**
	 * Обновляет значение параметра в БД
	 * @param string $parameter_type - тип параметра
	*/
	protected function check_parameter_type($parameter_type) {
		return in_array($this->parameter_type, explode('+', $parameter_type));
	}

	/**
	 * Загружает необходимые значения параметров во внутреннюю переменную объекта $params
	*/
	protected function load_params() {
		foreach (params::$params as $system_name=>$param) {
			if ($this->check_parameter_type($param['param_type'])) {
				$this->params[$system_name]=$param;
				// если локальные переписывают глобальные, нужно грузить глобальные, костыль...
				if ($param['param_type']=='GLOBAL+LOCAL' && $this->parameter_type=='GLOBAL') {
					if (sizeof(db::sql_select('SELECT * FROM SYSTEM_AUTH_USER_PARAMS WHERE SYSTEM_NAME=:system_name', array('system_name'=>$system_name)))) {
						$this->params[$system_name]['value']=$param['default_value'];
					}
				}
				if (!$param['title']) $this->params[$system_name]['title']=metadata::$lang['lang_uncheckable_'.$system_name];
				if (!$param['type']) $this->params[$system_name]['type']='text';
				
				// Переводим названия вариантов в списке типа select1
				if ( $this->params[$system_name]['type'] == 'select1' )
					foreach ( $this->params[$system_name]['value_list'] as $value_index => $value_array )
						$this->params[$system_name]['value_list'][$value_index]['title'] =
							metadata::$lang[$this->params[$system_name]['value_list'][$value_index]['title']];
			}
		}
	}

	/**
	 * Возвращает параметры в виде, пригодном для передачи в форму
	*/
	private function get_params_for_form () {
		$res=array();
		foreach ($this->params as $system_name=>$param) {
			$this->preprocess_param_for_form($system_name, $param['value']);
			$res[$this->form_prefix.$system_name]=$param['value'];
		}
		return $res;
	}
		

	/**
	 * Какие-то действия с system_name и new_value перед сохранением параметра
	 * @param string $system_name - название параметра
	 * @param string $new_value - новое значение параметра
	*/
	protected function preprocess_set_parameter_new_value(&$system_name, &$new_value) {
		// проверяем ограничения
		if ($this->params[$system_name]['restrictions']) {
			list($first, $last)=explode('..', $this->params[$system_name]['restrictions']);
			if (is_numeric($first) && is_numeric($last)) {
				$first=$this->params[$system_name]['type'] == 'float' ? (float)$first : (int)$first;
				$last=$this->params[$system_name]['type'] == 'float' ? (float)$last : (int)$last;
			}
			
			if ($new_value<$first) 
				$new_value=$first;
			if ($new_value>$last) 
				$new_value=$last;
		}
		
		if ($system_name=='default_interface_lang') {
			$code=$this->get_lang_code_from_id($new_value);
			if ($code) {
				$new_value=$code;
			}
			else {
				throw new Exception(metadata::$lang['lang_bad_language']);
			}
		}
		
	}
	
	/**
	* Карточка редактирования
	* Возвращает массив с названием и хтмл-текстом
	* @param array $req REQUEST
	* @return array
	* @see table::html_card - сделано по аналогии
	*/
	
	private function html_card ($req) {
		$inf_block = '';
		
		if ($req['splitter_changed']) {
			$msg = metadata::$lang['lang_system_global_parameters_after_change_splitter_msg'].' <a href="'.lib::make_request_uri(array('obj'=>'TEMPLATE', 'action'=>'distributed', 'do_op'=>'mass_generate')).'">'.metadata::$lang['lang_system_global_parameters_refresh_all_pages'].'</a>.';
			$tpl = new smarty_ee( metadata::$lang );
			$tpl->assign('msg', $msg);
			$info_block = $tpl->fetch($this->tpl_dir."core/object/html_warning.tpl");
		}
		
		$title = metadata::$objects[$this -> obj]['title'];
		$form_name = html_element::get_next_form_name();
		$form_fields=$this->full_object->get_form_fields('change', $this->form_element_prefix, $this->get_params_for_form(), $this->form_prefix, $this->params);
		$html_fields=html_element::html_fields($form_fields, $this->tpl_dir."core/html_element/html_fields.tpl", $this->field);
		$form=html_element::html_form($html_fields, $this->url->get_hidden('changed'), $this->tpl_dir."core/html_element/html_form.tpl", true, $info_block);
		
		$operations = $this -> get_record_operations($form_name);
		
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'title', metadata::$objects[$this -> obj]['title']);
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'form', $form );
		
		return array( $title, $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' ) );		
	}
	
	
	/**
	 * Cписок операций
	 * @param string $form_name Название формы
	 * @return array
	 */
	 
	public function get_record_operations($form_name)
	{
		$operations = array();
		$operations[] = array("name"=>"apply", "alt"=>metadata::$lang["lang_action_apply"], "url"=>"javascript:if ( CheckForm.validate( document.forms['{$form_name}'] ) ) { document.forms['{$form_name}'].submit() }");
		return $operations;
	}
	
	
	/**
	 * Какие-то действия с system_name и param_value перед показом параметра в форме
	 * @param string $system_name - название параметра
	 * @param string $param_value - значение параметра
	*/
	
		/**
	 * Какие-то действия с system_name и param_value перед показом параметра в форме
	 * @param string $system_name - название параметра
	 * @param string $param_value - значение параметра
	*/
	
	protected function preprocess_param_for_form(&$system_name, &$param_value) {
		//parent::preprocess_param_for_form(&$system_name, &$param_value);
		if ($system_name=='default_interface_lang') {
			$lang_id=$this->get_lang_id_from_code($param_value);
			if ($lang_id) $param_value=$lang_id;
		}
	}	
	
	/**
	 * получение кода языка по id
	 * @param int $lang_id - id языка
	 * @returns string - код языка
	*/
	
	protected function get_lang_code_from_id($lang_id) {
		$res=db::sql_select('SELECT ROOT_DIR FROM LANG WHERE LANG_ID=:LANG_ID', array('LANG_ID'=>$lang_id));
		if ($res[0]['ROOT_DIR']) 
			return $res[0]['ROOT_DIR'];
		return false;
	}

	/**
	 * получение id языка по коду
	 * @param string $lang_code - код языка
	 * @returns int - id языка
	*/
	
	protected function get_lang_id_from_code($lang_code) {
		$res=db::sql_select('SELECT LANG_ID FROM LANG WHERE ROOT_DIR=:LANG_CODE', array('LANG_CODE'=>$lang_code));
		if ($res[0]['LANG_ID']) 
			return $res[0]['LANG_ID'];
		return false;
	}
	
	protected function log_register($system_name, $old_value, $new_value) {
		if (!log::is_enabled('log_records_change')) return;
		
		$log_info = array (
			'object_name' => metadata::$lang['lang_uncheckable_'.$system_name].' ('.$system_name.')',
			'old_value' => $old_value,
			'new_value' => $new_value
		);
		log::register('log_records_change', 'change', $log_info, $this->te_object_id, 0, 0, "", "");

	}
 }
?>
