<?PHP
/**
 * Класс для нестандартной работы таблицы состояний
 *
 * @package		RBC_Contents_5_0
 * @subpackage te
 * @copyright	Copyright (c) 2007 RBC SOFT
 * @author Alexandr Vladykin
 */
class wf_state extends table_translate {

	/**
	 * Добавление дополнено выставлением граничности
	 */
	public function exec_add($raw_fields, $prefix){
		$this->full_object->check_edges($raw_fields, $prefix);
		$id=parent::exec_add($raw_fields, $prefix);
		$this->full_object->set_edges($raw_fields, $prefix, array("WF_STATE_ID"=>$id));
		return $id;
	}

	/**
	 * Изменение дополнено выставлением граничности
	 */
	public function exec_change($raw_fields, $prefix, $pk){
		$this->full_object->check_edges($raw_fields, $prefix);
		parent::exec_change($raw_fields, $prefix, $pk);
		$this->full_object->set_edges($raw_fields, $prefix, $pk);
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Добавляем поля для определения граничного состояния
	 */
	public function action_add(){
		$this->full_object->add_edge_fields();
		parent::action_add();
	}

	/**
	 * Добавляем поля для определения граничного состояния
	 */
	public function action_change(){
		$this->full_object->add_edge_fields();
		parent::action_change();
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Ограничиваем список возможных состояний различными ограничениями, которые нужны для Воркфлоу
	 */
	public function ext_index_by_list_mode($mode, $list_mode){
		list($where, $binds)=$this -> call_parent( 'ext_index_by_list_mode', array($mode, $list_mode) );
		
		// Ограничение по идентификатору цепочки побликаций
		if($list_mode["WF_WORKFLOW_ID"]){
			$where.=" AND WF_STATE.WF_WORKFLOW_ID = :wf_workflow_id ";
			$binds+=array("wf_workflow_id"=>$list_mode["WF_WORKFLOW_ID"]);
		}
		return array($where, $binds);
	}
	
	/**
	 * Возвращает подготовленные к помещению в БД данные
	 * 
	 * Следит за тем, чтобы значения поля VERSIONS соответствовали выбранной цепочке публикации
	 * 
	 * @param array $raw_fields		Сырые данные, например, $_REQUEST
	 * @param string $prefix			Префикс, которым дополнены сырые данные, например, _form_ для формы
	 * @param string $mode			Режим работы метода - "add" или "change"
	 * @return array
	 */
	public function get_prepared_fields($raw_fields, $prefix, $mode){
		$fields = $this -> call_parent( 'get_prepared_fields', array($raw_fields, $prefix, $mode) );
		// Получаем правильный тип воркфлоу
		if($mode=="add"){
			$workflow=db::sql_select("SELECT * FROM WF_WORKFLOW WHERE WF_WORKFLOW_ID=:workflow_id", array("workflow_id"=>$fields["WF_WORKFLOW_ID"]));
		}else{
			$workflow=db::sql_select("SELECT * FROM WF_WORKFLOW WHERE WF_WORKFLOW_ID=(SELECT WF_WORKFLOW_ID FROM WF_STATE WHERE WF_STATE_ID=:wf_state_id)", array("wf_state_id"=>$raw_fields["WF_STATE_ID"]));
		}
		// Проверяем соответствие типа воркфлоу и версий, а также ругаемся на неизвестный тип воркфлоу
		if(
			($workflow[0]["WORKFLOW_TYPE"]=="dont_use_versions" && $fields["VERSIONS"]!="one_version" && $fields["VERSIONS"]!="no_version") ||
			($workflow[0]["WORKFLOW_TYPE"]=="use_versions" && $fields["VERSIONS"]!="test_version" && $fields["VERSIONS"]!="two_versions" && $fields["VERSIONS"]!="no_version") ||
			($workflow[0]["WORKFLOW_TYPE"]!="dont_use_versions" && $workflow[0]["WORKFLOW_TYPE"]!="use_versions")
		){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_workflow_type_and_versions_problem"].": \"{$fields["VERSIONS"]}\", \"{$workflow[0]["WORKFLOW_TYPE"]}\"");
		}
		return $fields;
	}

	/**
	 * Возвращает запись для формы ее редактирования
	 *
	 * Дополняем запись состояния набором полей с признаком граничности
	 */
	public function get_change_record($pk, $throw_exception=false){
		// Получение данных
		$record=parent::get_change_record($pk, $throw_exception);
		$current_edge=lib::array_reindex(db::sql_select("SELECT * FROM WF_EDGE_STATE WHERE WF_STATE_ID=:state_id", array("state_id"=>$pk["WF_STATE_ID"])), "LANG_ID", "EDGE_TYPE");
		$lang_object=object::factory("LANG");
		$langs=$lang_object->get_index_records($none, "select2", array());
		$lang_object->__destruct();
		array_unshift($langs, array("LANG_ID"=>0));
		// Назначение значений
		foreach($langs as $lang){
			$record["NEW_".$lang["LANG_ID"]]=(int)$current_edge[$lang["LANG_ID"]]["new"];
			$record["DELETED_".$lang["LANG_ID"]]=(int)$current_edge[$lang["LANG_ID"]]["deleted"];
		}
		return $record;
	}

	/**
	 * Проверяет корректность граничности состояния
	 * 
	 * @param array $raw_fields			Сырые данные, например, $_REQUEST
	 * @param string $prefix			Префикс, которым дополнены сырые данные, например, _form_ для формы
	 */
	public function check_edges( $raw_fields, $prefix )
	{
		$edge_exists = array( 'NEW' => array(), 'DELETED' => array() );
		foreach( $raw_fields as $key => $value )
			if ( preg_match( "/^{$prefix}(NEW|DELETED)_(\d+)$/", $key, $matches ) )
				$edge_exists[$matches[1]][$matches[2]] = $value ? 1 : 0;
		
		if ( array_sum( $edge_exists['NEW'] ) && array_sum( $edge_exists['DELETED'] ) )
			throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_wf_state_new_add_deleted'] );
		
		unset( $edge_exists['NEW'][0] );
		if ( array_sum( $edge_exists['NEW'] ) > 1 )
			throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_wf_state_new_for_some_language'] );
		
		unset( $edge_exists['DELETED'][0] );
		if ( array_sum( $edge_exists['DELETED'] ) > 1 && $raw_fields["{$prefix}VERSIONS"] != 'no_version' )
			throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_wf_state_deleted_for_some_language'] );
	}

	/**
	 * Выставляет нужную граничность состоянию (начальное или удаленное)
	 * 
	 * @param array $raw_fields			Сырые данные, например, $_REQUEST
	 * @param string $prefix			Префикс, которым дополнены сырые данные, например, _form_ для формы
	 * @param array $pk					Первичный ключ записи
	 */
	public function set_edges($raw_fields, $prefix, $pk){
		$lang_object=object::factory("LANG");
		$langs=$lang_object->get_index_records($none, "select2", array());
		$lang_object->__destruct();
		array_unshift($langs, array("LANG_ID"=>0));
		
		// Удаляем старые состояния (только если граничность указана явно)
		foreach($langs as $lang){
			for($i=0, $edges=array("new", "deleted"); $i<2; $i++){
				$edge_record=array("WF_STATE_ID"=>$pk["WF_STATE_ID"], "LANG_ID"=>$lang["LANG_ID"], "EDGE_TYPE"=>$edges[$i]);
				$edge_var="{$prefix}".strtoupper($edges[$i])."_{$lang["LANG_ID"]}";
				if(isset($raw_fields[$edge_var])){
					db::delete_record("WF_EDGE_STATE", $edge_record);
				}
			}
		}
		// Вставляем новые
		foreach($langs as $lang){
			for($i=0, $edges=array("new", "deleted"); $i<2; $i++){
				$edge_record=array("WF_STATE_ID"=>$pk["WF_STATE_ID"], "LANG_ID"=>$lang["LANG_ID"], "EDGE_TYPE"=>$edges[$i]);
				$edge_var="{$prefix}".strtoupper($edges[$i])."_{$lang["LANG_ID"]}";
				if($raw_fields[$edge_var]){
					db::insert_record("WF_EDGE_STATE", $edge_record);
				}
			}
		}
	}

	/**
	 * Помещение в метаданные полей для назначения граничности состояния
	 */
	public function add_edge_fields(){
		$new_fields["NEW_0"]=array("title"=>metadata::$lang["lang_new_state_for_no_lang"], "type"=>"checkbox", "virtual"=>1);
		$deleted_fields["DELETED_0"]=array("title"=>metadata::$lang["lang_deleted_state_for_no_lang"], "type"=>"checkbox", "virtual"=>1);
		$lang_object=object::factory("LANG");
		$langs=$lang_object->get_index_records($none, "select2", array());
		$lang_object->__destruct();
		foreach($langs as $lang){
			$new_fields["NEW_{$lang["LANG_ID"]}"]=array("title"=>metadata::$lang["lang_new_state_for_lang"].": ".$lang["TITLE"], "type"=>"checkbox", "virtual"=>1);
			$deleted_fields["DELETED_{$lang["LANG_ID"]}"]=array("title"=>metadata::$lang["lang_deleted_state_for_lang"].": ".$lang["TITLE"], "type"=>"checkbox", "virtual"=>1);
		}
		metadata::$objects["WF_STATE"]["fields"]+=$new_fields+$deleted_fields;
	}
	
	////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Добавляем в конец таблицы скрипт вызова метода для изменения набора полей
	 *
	 * @see table::html_card()
	 */
	public function html_card( $mode, &$request )
	{
		list( $title, $html ) = $this -> call_parent( 'html_card', array( $mode, &$request ) );
		
		$form_name = html_element::get_form_name();
		
		$workflows = db::sql_select( 'select WF_WORKFLOW_ID, WORKFLOW_TYPE from WF_WORKFLOW' ); $workflow_array = array();
		foreach( $workflows as $workflow )
			$workflow_array[] = "'" . $workflow['WF_WORKFLOW_ID'] . "': '" . $workflow['WORKFLOW_TYPE'] . "'";
		$workflow_array_str = join( ', ', $workflow_array );
		
		$html .=  <<<HTML
<script type="text/javascript">
	var oForm = document.forms['{$form_name}'];

	var oWorkflow = { {$workflow_array_str} };
	var oVersions = { 'dont_use_versions': [ '', 'one_version', 'no_version' ], 'use_versions': [ '', 'test_version', 'two_versions', 'no_version' ] };
	
	var oVersionsSelect = oForm['_form_VERSIONS'];
	var oWorkflowSelect = oForm['_form_WF_WORKFLOW_ID'];
	
	var oVersionsOptions = new Array(); var iVersionsSelected = '';
	
	// Запоминаем содержимое селекта "Версии записи"          
	for ( var i = 0; i < oVersionsSelect.options.length; i++ )
	{
		oVersionsOptions[oVersionsSelect.options[i].value] =
			oVersionsSelect.options[i].innerHTML;
		
		if ( oVersionsSelect.options[i].selected )
			iVersionsSelected = oVersionsSelect.options[i].value;
	}
	
	addListener( oWorkflowSelect, 'change', changeVersions );
	
	// Заполняем массив чекбоксов граничных состояний          
	var aEdgeStates = getEdgeStates();
	
	addListener( oVersionsSelect, 'change', changeEdgeStates );
	
	// Вешаем обработчик на чекбоксы граничных состояний          
	for ( var sCheckboxName in aEdgeStates )
		for ( var iLang in aEdgeStates[sCheckboxName] )
			addListener( aEdgeStates[sCheckboxName][iLang], 'click', checkEdgeStates );
	
	changeVersions();
	
	// Обработчик смены состояния селекта "Цепочка публикаций"          
	function changeVersions( oEvent )
	{
		var oAllowVersions = oVersionsOptions;
		var iWorkflowId = oWorkflowSelect.options[oWorkflowSelect.selectedIndex].value;
		
		if ( oWorkflow[iWorkflowId] && oVersions[oWorkflow[iWorkflowId]] )
		{
			oAllowVersions = new Array();
			for ( var iVersionsValue in oVersions[oWorkflow[iWorkflowId]] )
				oAllowVersions[oVersions[oWorkflow[iWorkflowId]][iVersionsValue]] =
					oVersionsOptions[oVersions[oWorkflow[iWorkflowId]][iVersionsValue]];
		}
			
		updateVersions( oAllowVersions, iVersionsSelected );
		
		changeEdgeStates( oEvent );
	}
	
	// Заполнение селекта "Версии записи"          
	function updateVersions( aOptions, iSelect )
	{
		while ( oVersionsSelect.firstChild )
			 oVersionsSelect.removeChild( oVersionsSelect.firstChild );
		
		for ( var iOption in aOptions )
		{
			var oOption = document.createElement( 'option' );
			oOption.setAttribute( 'value', iOption );
			if ( iOption == iSelect )
				oOption.setAttribute( 'selected', 'selected' );
			oOption.innerHTML = aOptions[iOption];
			
			oVersionsSelect.appendChild( oOption );
		}
	}
	
	// Заполнение массива чекбоксов граничных состояний          
	function getEdgeStates()
	{
		var aStates = new Array();
		
		for ( var i = 0; i < oForm.elements.length; i++ )
		{
			if ( oForm.elements[i].type == 'checkbox' )
			{
				if ( oMatch = oForm.elements[i].name.match( /^_form_(NEW|DELETED)_(\d+)$/i ) )
				{
					if ( !aStates[oMatch[1]] )
						aStates[oMatch[1]] = new Array();
					
					aStates[oMatch[1]][oMatch[2]] = oForm.elements[i];
				}
			}
		}
		
		return aStates;
	}
	
	// Обработчик смены состояния селекта "Версии записи"          
	function changeEdgeStates( oEvent )
	{
		for ( var sCheckboxName in aEdgeStates )
		{
			for ( var iLang in aEdgeStates[sCheckboxName] )
			{
				aEdgeStates[sCheckboxName][iLang].disabled =
					oVersionsSelect.options[oVersionsSelect.selectedIndex].value == '';
				if ( oEvent )
					aEdgeStates[sCheckboxName][iLang].checked = false;
			}
		}
	}
	
	// Корректировка состояний чекбоксов граничных состояний          
	function checkEdgeStates( oEvent )
	{
		if ( oEvent.srcElement )
			var oCheckbox = oEvent.srcElement;
		else
			var oCheckbox = oEvent.target;
		
		if ( oMatch = oCheckbox.name.match( /^_form_(NEW|DELETED)_(\d+)$/i ) )
		{
			// Состояние не может быть одновременно и начальным, и удаленным          
			var aOppositeStates = aEdgeStates[ oMatch[1] == 'NEW' ? 'DELETED' : 'NEW' ];
			for ( var iLang in aOppositeStates )
				aOppositeStates[iLang].checked = false;
			
			if ( oMatch[2] != '0' )
			{
				var aFriendlyStates = aEdgeStates[oMatch[1]];
				
				if ( oMatch[1] == 'NEW' )
				{
					// Состояние не может быть начальным одновременно для нескольких языков          
					for ( var iLang in aFriendlyStates )
						if ( iLang != '0' && iLang != oMatch[2] )
							aFriendlyStates[iLang].checked = false;
				}
				else
				{
					// Состояние не может быть удаленным одновременно для нескольких языков          
					for ( var iLang in aFriendlyStates )
						if ( iLang != '0' && iLang != oMatch[2] &&
								oVersionsSelect.options[oVersionsSelect.selectedIndex].value != 'no_version' )
							aFriendlyStates[iLang].checked = false;
				}
			}
		}
	}
</script>
HTML;
		return array( $title, $html );
	}
}
?>
