<?php
/**
 * Класс декоратор таблиц "Язык в первичном ключе"
 *
 * @package		RBC_Contents_5_0
 * @subpackage core
 * @copyright	Copyright (c) 2006 RBC SOFT
 * @todo решить как лучше сделать поиск по фильтру в том случае, если язык не выбран. Тоже самое продумать в декораторе "Версия"
 */
class table_lang extends decorator{

	/**
	 * Все языки системы в списке с ключом-идентификатором языка
	 * @var array
	 */
	protected $r_all_langs=array();

	/**
	 * Главный язык - выбранный в фильтре или язык по умолчанию, если в фильтре ничего не выбрано
	 * @var int
	 */
	protected $main_lang_id;

	/**
	 * Язык выбранный в фильтре или null, если ничего не выбрано
	 * @var int
	 */
	protected $lang_filter;

	/**
	 * Названия языков, на которых есть варианты записей, выбранных в списке
	 * @var array
	 */
	protected $r_lang_info=array();

	/**
	 * Конструктор. Заранее выбираем список языков и определяемся с языком который выбран главным в данный момент
	 *
	 * @see table::__construct()	 
	 */
	function __construct(&$full_object, $decorators){
		parent::__construct($full_object, $decorators);
		// Все языки
		$lang_obj = object::factory( 'LANG' );
		list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
			$lang_obj -> ext_field_selection( 'TITLE', 1 );
		$lang_obj -> __destruct();
		
		$all_langs=db::replace_field(db::sql_select("SELECT LANG.*, " . $dec_field . " AS \"_TITLE\" FROM LANG " . $dec_join[0] . " WHERE IN_CONTENT = 1 ORDER BY " . $dec_field, $dec_binds), 'TITLE', '_TITLE');
		
		
		
		foreach($all_langs as $lang){
			$this->r_all_langs[$lang["LANG_ID"]]=$lang;
			if($lang["PRIORITY"]){
				$default_lang_id=$lang["LANG_ID"];
			}
		}
		// Главный язык
		if(isset($this->r_all_langs[$_REQUEST["_f_LANG_ID"]])){
			$this->main_lang_id=$_REQUEST["_f_LANG_ID"];
			$this->lang_filter=$_REQUEST["_f_LANG_ID"];
		}else{
			$this->main_lang_id=$default_lang_id;
		}


	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Перевести запись на другой язык, в случае неуспеха вызвает исключение
	 *
	 * @param array $pk			первичный ключ, который определяет изменяемую запись
	 * @param int $lang_id		язык, на который осуществляется перевод
	 */
	public function exec_translate($pk, $lang_id){
		$lang_id=intval($lang_id);
		$record=$this->full_object->get_change_record($pk, true);
		
		// При переводе информация о тегах не нужна
		if ( $this -> is_taxonomy ) unset( $record['TAG'] );
		
		$this->full_object->is_permitted_to("add", "", true);
		// Проверка на существование языка в системе
		if(!$this->r_all_langs[$lang_id]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_such_language"].": ({$lang_id})");
		}
		
		// Проверяем существование у записи версии на языке перевода
		$this -> full_object -> is_language_exists( $record, $lang_id );
		// Проверяем существование перевода записи в присоединяемых таблицах
		$this -> full_object -> translate_link_table_check( $record, $lang_id );
		
		// Собственно перевод
		lib::inserter($this->obj, array($record), array("LANG_ID"=>$lang_id));
		
		// Заносим в журнал
		$record["LANG_ID"]=$lang_id;
		$this->full_object->log_register("translate", $record);
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Действие - карточка перевода записи на другой язык
	 */
	public function action_translate(){
		$pk=$this->primary_key->get_from_request(true);
		$record=$this->full_object->get_change_record($pk);
		$this->full_object->is_permitted_to("add", "", true);
		
		// Переводимая версия не должна быть помечена, как удаленная
		if ( $this -> decorators['external'] && $record['EXTERNAL_IS_DELETED'] )
			throw new Exception( $this -> te_object_name . ' (translate): ' . metadata::$lang['lang_external_record_is_deleted'] . ': (' . $this -> primary_key -> pk_to_string( $pk ) . ')' );
		
		// Собираем информацию о том, какие языки в записи уже задействованы
		$r_used_langs=lib::array_reindex(db::sql_select("SELECT LANG_ID FROM {$this->obj} WHERE {$this->autoinc_name}=:id_value" . ( $this -> decorators['external'] ? ' and EXTERNAL_IS_DELETED <> 1' : '' ), array("id_value"=>$pk[$this->autoinc_name])), "LANG_ID");
		// Получаем поле языка в формате для шаблона формы
		$field_done=$this->full_object->get_form_fields("add", "_form_", "", "", array("LANG_ID"=>metadata::$objects[$this->obj]["fields"]["LANG_ID"]));
		// Удаляем из него задействованные в данной записи языки
		foreach($field_done["LANG_ID"]["value_list"] as $k=>$lang){
			if($r_used_langs[$lang["LANG_ID"]]){
				unset($field_done["LANG_ID"]["value_list"][$k]);
			}
		}
		
		// Удаляем также языки, для которых не существует перевода записи в связанной переводимой таблице
		foreach ( metadata::$objects[$this -> obj]['fields'] as $field_name => $field_item )
		{
			// Условие срабатывает только для заполненых или обязательных полей
			if ( ( $field_item['type'] == 'select2' ) && ( $record[$field_name] || ( $field_item['errors'] & _nonempty_ )  )&&
					isset( metadata::$objects[$field_item['fk_table']]['decorators']['lang'] ) )
			{
				// Получаем объект присоединяемой таблицы
				$fk_table = object::factory( $field_item['fk_table'] );
				
				// Для каждого из оставшихся языков перевода проверяем наличие соответствующей записи в таблице
				foreach( $field_done['LANG_ID']['value_list'] as $k => $lang )
					if ( !lib::is_record_exists( $fk_table -> obj, array( $fk_table -> autoinc_name => $record[$field_name], 'LANG_ID' => $lang['LANG_ID'] ) ) )
						unset( $field_done['LANG_ID']['value_list'][$k] );
				$fk_table -> __destruct();
			}
		}
		
		// Если языков для перевода не осталось, выводим в шаблон соответствующее сообщение
		// @todo Сообщение можно расшифровать, добавив в него возможные причины отсутствия доступных языков
		if ( !count( $field_done['LANG_ID']['value_list'] ) )
			$field_done['LANG_ID']['vars']['message'] =
				array( 'title' => metadata::$lang['lang_no_available_language'] );
		
		// Собираем форму редактирования
		$form_name = html_element::get_next_form_name();
		$html_fields = html_element::html_fields( $field_done, $this -> tpl_dir . 'core/html_element/html_fields.tpl', $this -> field );
		$form = html_element::html_form( $html_fields, $this -> url -> get_hidden( 'translated', array( 'pk' => $pk ) ), $this -> tpl_dir . 'core/html_element/html_form.tpl', true );

		$operations = $this -> get_record_operations( $form_name );
		
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'title', metadata::$lang['lang_translate'] );
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'form', $form );
		
		$this -> title = metadata::$lang['lang_translate'];
		$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' );
	}

	/**
	 * Действие - перевод записи на другой язык
	 */
	public function action_translated(){
		$pk=$this->primary_key->get_from_request();
		$this->full_object->exec_translate($pk, $_REQUEST["_form_LANG_ID"]);
		$this->url->redirect();
	}

	/**
	 * Действие - изменяет существующую запись по всем языковым версиям
	 *
	 * Полностью пеопределена, чтобы можно было редактировать все языковые версии одновременно. 
	 * При этом сама exec_change() не изменена, она по-прежнему изменяет только по одной языковой версии
	 *
	 * @see table::action_changed()
	 */
	 
	public function action_changed(){
		if($this->full_object->is_applied_to("change", false)){
			try{
				$metadata=metadata::$objects[$this->obj];
				$pk=$pk_lang=$this->primary_key->get_from_request();
				$pk_lang["LANG_ID"]=0; // Чтобы получить сразу все версии записи
				$records=$this->full_object->get_other_langs($pk_lang);
				foreach($records as $record){
					if($_REQUEST["_check_lang_{$record["LANG_ID"]}"]){
						$pk_lang["LANG_ID"]=$record["LANG_ID"];
						$this->full_object->unblock_user_record($pk_lang);
						$this->full_object->exec_change($_REQUEST, "_form_{$record["LANG_ID"]}_", $pk_lang);
					}
				}
				
				// Присоединяем теги к обновленной записи
				if ( $this -> is_taxonomy && isset( $_REQUEST['_form_TAG'] ) )
					$this -> full_object -> set_tags( $pk, $_REQUEST['_form_TAG'] );
				
				$this->url->redirect( "", array( "restore_params" => 1 ) );
			}catch(Exception $e){
				$_REQUEST["action"]="change";
				metadata::$objects[$this->obj]=$metadata;
				$this->from_request = true; $this->full_object->action_change();
				$this->body = object::html_error($e->getMessage(), $e->getFile(), $e->getLine(), '', $e->getTraceAsString(), false).$this->body;
			}
		}
	}

	/**
	 * Действие - изменяет существующую запись и снова возвращается на страницу редактрования
	 */
	public function action_changed_apply() {
		if($this->full_object->is_applied_to("change", false)){
			try{
				$metadata=metadata::$objects[$this->obj];
				$pk=$pk_lang=$this->primary_key->get_from_request();
				$pk_lang["LANG_ID"]=0; // Чтобы получить сразу все версии записи
				$records=$this->full_object->get_other_langs($pk_lang);
				foreach($records as $record){
					if($_REQUEST["_check_lang_{$record["LANG_ID"]}"]){
						$pk_lang["LANG_ID"]=$record["LANG_ID"];
						$this->full_object->unblock_user_record($pk_lang);
						$this->full_object->exec_change($_REQUEST, "_form_{$record["LANG_ID"]}_", $pk_lang);
					}
				}
				
				// Присоединяем теги к обновленной записи
				if ( $this -> is_taxonomy && isset( $_REQUEST['_form_TAG'] ) )
					$this -> full_object -> set_tags( $pk, $_REQUEST['_form_TAG'] );
				
				$this->url->redirect("change", array("pk"=>$pk));
			}catch(Exception $e){
				$_REQUEST["action"]="change";
				metadata::$objects[$this->obj]=$metadata;
				$this->from_request = true; $this->full_object->action_change();
				$this->body = object::html_error($e->getMessage(), $e->getFile(), $e->getLine(), '', $e->getTraceAsString(), false).$this->body;
			}
		}
	}
	
	

	/**
	 * Действие - копирует запись со всеми ее языковыми версиями.
	 *
	 * @todo Не очень понятно, как правильно копировать мультиязычные записи. Как решится этот момент, тогда же надо будет и уточнить корректность работы проверки прав на операцию
	 * @todo В этом декораторе не определен метод exec_copy() - копирование идет по-прежнему с помощью exec_add(). Разобраться, что тут нужно поменять, чтобы все стало более логичным
	 */
	public function action_copied(){
		if(metadata::$objects[$this->obj]["copy"]){
			try{
				$metadata=metadata::$objects[$this->obj];
				foreach($this->r_all_langs as $lang){
					// в случае инф. блока, добавляем данные в каждый язык
					$_REQUEST["_form_{$lang["LANG_ID"]}_INF_BLOCK_ID"] = $_REQUEST["_form_INF_BLOCK_ID"];
					
					if($_REQUEST["_check_lang_{$lang["LANG_ID"]}"]){// Проверка наличия такого языка в форме
						// Вначале добавляем первую языковую версию, подмешивая ее язык в $_REQUEST
						if(!$inserted_id){
							$_REQUEST["_form_{$lang["LANG_ID"]}_LANG_ID"]=$lang["LANG_ID"];
							$inserted_id=$this->full_object->exec_add($_REQUEST, "_form_{$lang["LANG_ID"]}_");
							$base_lang=$lang["LANG_ID"];
						// Для последующих версий вначале делаем перевод, а потом изменяем его до нужного состояния
						}else{
							// Здесь первичные ключи вынужденно формируются руками, то есть в обход объекта $this->primary_key
							$this->full_object->exec_translate(array($this->autoinc_name=>$inserted_id, "LANG_ID"=>$base_lang), $lang["LANG_ID"]);
							$this->full_object->exec_change($_REQUEST, "_form_{$lang["LANG_ID"]}_", array($this->autoinc_name=>$inserted_id, "LANG_ID"=>$lang["LANG_ID"]));
						}
					}
				}
				
				// Присоединяем теги к скопированной записи
				if ( $this -> is_taxonomy && isset( $_REQUEST['_form_TAG'] ) )
					$this -> full_object -> set_tags( array( $this -> autoinc_name => $inserted_id ), $_REQUEST['_form_TAG'] );
				
				$this->url->redirect();
			}catch(Exception $e){
				metadata::$objects[$this->obj]=$metadata;
				list($this->title, $this->body)=$this->full_object->html_card("copy", $_REQUEST, true);
				$this->body = object::html_error($e->getMessage(), $e->getFile(), $e->getLine(), '', $e->getTraceAsString(), false).$this->body;
			}
		}
	}
	
	/**
	* Операция разблокирования записи
	*/
	
	public function action_unblock_record () {
		if ($this->full_object->is_checkinout_table() && $this->full_object->is_checkinout_admin()) {
			$pk=$this->primary_key->get_from_request();
			unset($pk['LANG_ID']);
			$this->full_object->exec_unblock_record($pk);
		}
		
		// для того чтобы не вернулись на список
		unset($_REQUEST['prev_params']);
		
		$this->call_parent('action_unblock_record');
	}
	
	/**
	* Разблокируем запись, вызывается при уходе со страницы
	*/
	
	public function command_unblock_record ( $mark = '') {
		if (!$this->full_object->is_checkinout_table()) return;
		parse_str($_REQUEST['params'], $params);
		if (sizeof($params) && in_array($params['action'], array('change', 'm2m', 'resolution'))) {
			$pk=$pk_lang=$this->primary_key->get_from_record($params);
			$pk_lang["LANG_ID"]=0; // Чтобы получить сразу все версии записи
			$records=$this->full_object->get_other_langs($pk_lang);
			foreach($records as $record){
				$pk_lang["LANG_ID"]=$record["LANG_ID"];
				$this->full_object->unblock_user_record($pk_lang, false);
			}
		}
		return html_element::xml_response( '', $mark );
	}
	

	/**
	* При пинге продолжаем блокировку всех языковых версий записи
	*/
	
	public function command_ping ( $mark = '') {
		if (!$this->full_object->is_checkinout_table()) return;
		parse_str($_REQUEST['params'], $params);
		if (sizeof($params) && in_array($params['action'], $this->full_object->get_lock_actions())) {
			$pk=$pk_lang=$this->primary_key->get_from_record($params);
			$pk_lang["LANG_ID"]=0; // Чтобы получить сразу все версии записи
			$records=$this->full_object->get_other_langs($pk_lang);
			foreach($records as $record){
				$pk_lang["LANG_ID"]=$record["LANG_ID"];
				$this->full_object->exec_prolong_block($pk_lang);
			}
		}
		return html_element::xml_response( '', $mark );
	}
	
	/**
	* Проводится дополнительная проверка, если пользователь нажал кнопку Back, и форма редактирования подгрузилась из кэша,
	* вызывается Ajax с попыткой заблокировать записи. В случае если кто-то успел заблокировать данную запись, выведется клиенту
	* сообщение об этом, и он должен будет перегрузить страницу, для нормального вида заблокированной записи
	*/
	
	public function command_check_block_record ( $mark = '' ) {
		if ($this->full_object->is_checkinout_table()) {
			parse_str($_REQUEST['params'], $params);
			if (sizeof($params) && in_array($params['action'], $this->full_object->get_lock_actions()) && $params['CUR_LANG']) {
				$pk=$pk_lang=$this->primary_key->get_from_record($params);
				$pk_lang['LANG_ID']=$params['CUR_LANG'];
				if ($this->full_object->is_record_blocked($pk_lang, false))
					return html_element::xml_response( '<![CDATA[blocked]]>', $mark );
				$this->full_object->exec_block_record($pk_lang);					
			}
		}
		
		return html_element::xml_response( '<![CDATA[ok]]>', $mark );
	}


///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Формирует карточку записи для добавления/изменения/копирования записи
	 *
	 * Здесь мы полностью переопределяем форму, потому что она работает совсем по другому -
	 * параллельное редактирование всех языковых версий, хотя стоит подумать о том, как сократить
	 * дублирование кода в этих методах в данном классе и в классе table
	 *
	 * @see table::html_card()
	 * @todo метод раздулся и стал неоптимален. Например, почему собирается один набор полей, а сразу после этого еще один для "view"
	 */
	public function html_card($mode, &$request){
		$form_name=html_element::get_next_form_name();
		// Готовим наборы полей в HTML-виде
		if($mode=="copy" || $mode=="change" || $mode=="view"){ // Копирование и изменение
			$pk=$this->primary_key->get_from_record($request, true);
			$this->primary_key->is_record_exists($pk, true);
			// Вынимаем записи
			$entire_record=array_merge(array($this->full_object->get_change_record($pk)), $this->full_object->get_other_langs($pk));
			
			// Удаляем записи, "почеченные как удаленные"
			if ( $this -> decorators['external'] )
				foreach ( $entire_record as $record_index => $record_item )
					if ( $record_item['EXTERNAL_IS_DELETED'] )
						unset( $entire_record[$record_index] );
			
			// в случае копирования или просмотра никакого блокирования не может быть в принцпе, иначе будем проверять...
			$is_blocked_all= ($mode=="copy" || $mode=="view") ? false : true;
			
			// если режим копирования, не надо выводить языки, как в варианте change
			
			foreach($entire_record as $er)
			{
				// Делаем списки полей записей
				foreach ( metadata::$objects[$this -> obj]['fields'] as $field_name => $field_item )
				{
					// Для полей типа select2 из таблиц с декоратором 'lang' проводим некоторые махинации с метаданными
					if ( $field_item['type'] == 'select2' && isset( metadata::$objects[$field_item['fk_table']]['decorators']['lang'] ) )
					{
						// Получаем объект присоединяемой таблицы
						$fk_table = object::factory( $field_item['fk_table'] );
						// Получаем список языков, на которых переведена запись в данном поле
						$fk_table_records = $fk_table -> get_other_langs(
							array( $fk_table -> autoinc_name => $er[$field_name], 'LANG_ID' => 0 ) );
						
						// Из этого списка выбираем один язык: в первую очередь главный (если он есть), иначе первый попавшийся
						$record_lang = '';
						foreach( $fk_table_records as $fk_table_record )
							if ( $fk_table_record['LANG_ID'] == $this -> main_lang_id )
								$record_lang = $this -> main_lang_id;
						if ( !$record_lang )
							$record_lang = $fk_table_records[0]['LANG_ID'];
						
						// Устанавливаем для данного поля list_mode "LANG_ID"
						metadata::$objects[$this -> obj]['fields'][$field_name]['list_mode']['LANG_ID'] = $er['LANG_ID'];
						
						// Если перевод данной записи для текущего языка отсутствует ...
						if ( !lib::is_record_exists( $fk_table -> obj, array( $fk_table -> autoinc_name => $er[$field_name], 'LANG_ID' => $er['LANG_ID'] ) ) && $record_lang )
						{
							// ... дополняем list_mode идентификатором этой несчастной записи
							metadata::$objects[$this -> obj]['fields'][$field_name]['list_mode']['FK_FIELD_ID'] = $er[$field_name];
							// ... и выводим в шаблон предупреждающее сообщение
							metadata::$objects[$this -> obj]['fields'][$field_name]['vars']['message'] =
								array( 'title' => metadata::$lang['lang_record_link_record'] . ' "' . $fk_table -> get_record_title(
									array( $fk_table -> autoinc_name => $er[$field_name], 'LANG_ID' => $record_lang ) ) . '"' );
						}
						else
							metadata::$objects[$this -> obj]['fields'][$field_name]['vars']['message'] = '';
							
						$fk_table -> __destruct();
					}
				}
				
				if ( $this->from_request )
					foreach(metadata::$objects[$this->obj]["fields"] as $field_name=>$field_descr)
						if(isset($request["_form_{$er["LANG_ID"]}_".$field_name]))
							$er[$field_name]=$request["_form_{$er["LANG_ID"]}_".$field_name];
				
				// если происходит копирование, то вследствие интерфейса не надо отображать языки и инф. блоки
				if ($mode=='copy') {
					metadata::$objects[$this->obj]['fields']['LANG_ID']['no_add']=1;
					if (metadata::$objects[$this->obj]['fields']['INF_BLOCK_ID'])
						metadata::$objects[$this->obj]['fields']['INF_BLOCK_ID']['no_add']=1;
				}

				list($additional_fields, $js)=$this->full_object->ext_html_card($form_name, $er);
				$full_js.=$js;
				

				// Для случая редактирования для каждой языковой версии проверяем права, чтобы подизаблить те поля, на которые нет прав
				$gathered_fields=$this->full_object->get_form_fields($mode, "_form_{$er["LANG_ID"]}_", $er)+$additional_fields;
				
				if($mode=="change"){
					$pk_lang = $this->primary_key->get_from_record($er);
					$lang_rights=$this->full_object->is_permitted_to("change", $pk_lang);
					
					if($lang_rights) 
						$is_permitted_to_change=true;
						
					$blocked=false;
					
					if ($lang_rights) {
						if (!$this->exec_block_record($pk_lang)) {
							$blocked=true;
							$is_blocked_any=true;
						}
					}
					
					
					if (!$blocked) 
						$is_blocked_all=false;
					
					if(!$lang_rights || $blocked)
						foreach($gathered_fields as $field_name=>$field_descr) 
							$gathered_fields[$field_name]["disabled"]=1;
				}
				
				$html_fields = html_element::html_fields($gathered_fields, $this->tpl_dir."core/html_element/html_fields.tpl", $this->field, !$this->from_request);
				if ($mode=='view') 
					$html_fields = html_element::html_value_list($this->full_object->get_form_fields($mode, "", $er, "", "", false)+$additional_fields, $this -> tpl_dir.'core/html_element/html_table.tpl',$this->field);
				
				$cur_fields = array(
					"lang_name"=>$this->r_all_langs[$er["LANG_ID"]]["TITLE"],
					"lang_id"=>$er["LANG_ID"],
					"html_fields"=>$html_fields,
					"lang_enabled"=>($mode!='change')?'1':(int)($lang_rights && !$blocked),
				);
				
				if ($this->full_object->is_checkinout_table()) {
					if ($this->full_object->is_record_blocked($pk_lang, false)) {
						$cur_fields['blocked']=true;
						$cur_fields['blocked_info']=$this->full_object->get_blocked_info($this, $pk_lang);
					}
					else {
						$cur_fields['blocking']=true;
					}
				}
				
				$lang_fields[]=$cur_fields;
			}

			// Помещаем это дело в шаблон
			$tpl=new smarty_ee(metadata::$lang);
			$tpl->assign("lang_fields", $lang_fields);
			
			
			// дополнительные поля
			$addit_fields = $top_addit_fields = array();
			
			// Выводим в карточку записи дополнительное поле "Теги", одно для всех языков
			if ( $this -> is_taxonomy ) {
				$tag_field = array( 
								'title' => metadata::$lang['lang_uncheckable_tag_through_a_comma'], 
								'type' => 'text', 
								'vars' => array( 
									'maxlength' => params::$params["taxonomy_length"]["value"] 
							 ) 
				);
				
				if ($is_blocked_any) {
					$tag_field['no_change']=1;
					$tag_field['disabled']=1;
				}
				
				$addit_fields['TAG'] = $tag_field;
			}
			
			// при копировании нужно указать информационный блок куда копируем
			if ($mode=='copy') 
				if (metadata::$objects[$this->obj]['fields']['INF_BLOCK_ID']) {
					metadata::$objects[$this->obj]['fields']['INF_BLOCK_ID']['no_add']=0;
					$top_addit_fields['INF_BLOCK_ID'] = metadata::$objects[$this->obj]['fields']['INF_BLOCK_ID'];
				}


			if (sizeof($addit_fields)) {
				$addit_fields = html_element::html_fields( 
					$this -> full_object -> get_form_fields( $mode, '_form_', $this -> full_object -> get_change_record( $pk ),
						'', 
						$addit_fields
					), 
					$this -> tpl_dir . 'core/html_element/html_fields.tpl', 
					$this -> field 
				) ;
				
				$tpl -> assign( 'additional_fields', $addit_fields);
			}
			
			if (sizeof($top_addit_fields)) {
				$top_addit_fields = html_element::html_fields( 
					$this -> full_object -> get_form_fields( $mode, '_form_', $this -> full_object -> get_change_record( $pk ),
						'', 
						$top_addit_fields
					), 
					$this -> tpl_dir . 'core/html_element/html_fields.tpl', 
					$this -> field 
				) ;
				
				$tpl -> assign( 'top_additional_fields', $top_addit_fields);
			}
			
	
			$tpl->assign("form_name", html_element::get_form_name());
			$html_fields=$tpl->fetch($this->tpl_dir."core/object/html_card_multilang_form.tpl");
		}else{ // Добавление
			$record=$request;
			$record_prefix="_f_";
			// Поля, которые не выводятся в фильтрах, но имеют значение по умолчанию, получают эти значения
			foreach(metadata::$objects[$this->obj]["fields"] as $field_name=>$field_descr){
				if($record[$record_prefix.$field_name]=="" && $field_descr["value"]!=""){
					$record[$record_prefix.$field_name]=$field_descr["value"];
				}elseif($record[$record_prefix.$field_name]=="" && $field_descr["type"]=="select1"){ // Для select1 можно воспользоваться механизмом выборки дефолтного значения прямо из списка
					foreach($field_descr["value_list"] as $key=>$item){
						if($item["selected"]){
							$record[$record_prefix.$field_name]=$item["value"];
						}
					}
				}
			}
			
			if ( $this->from_request )
				foreach(metadata::$objects[$this->obj]["fields"] as $field_name=>$field_descr)
					$record[$record_prefix.$field_name]=$request["_form_".$field_name];
			
			list($additional_fields, $full_js)=$this->full_object->ext_html_card($form_name, $record);
			
			// Выводим в карточку записи дополнительное поле "Теги"
			if ( $this -> is_taxonomy )
				$additional_fields += $this -> full_object -> get_form_fields( $mode, '_form_', $record, $record_prefix, array( 'TAG' => array( 'title' => metadata::$lang['lang_uncheckable_tag_through_a_comma'], 'type' => 'text', 'vars' => array( 'maxlength' => params::$params["taxonomy_length"]["value"] ) ) ) );
			
			$full_js .= $this -> get_change_language_js($record);
			$html_fields=html_element::html_fields($this->full_object->get_form_fields($mode, "_form_", $record, $record_prefix)+$additional_fields, $this->tpl_dir."core/html_element/html_fields.tpl", $this->field, !$this->from_request);
		}
		// Собираем заголовок и действие, обрабатывающее форму
		if($mode=="copy"){
			$this->full_object->is_permitted_to("add", "", true);
			$title=metadata::$lang["lang_copy_record"];
			$card_title=$this->full_object->get_record_title($pk);
			$pk_for_hidden=array("pk"=>$pk);
			$action="copied";
		}
		elseif ($mode=="view") {
			$this->full_object->is_permitted_to("view", $pk, true);
			$title=$card_title=metadata::$lang["lang_view_record"];
		}elseif($mode=="change"){
			// Если ни на одну языковую версию записи прав нет (проверено выше), то бросаем исключение
			// Стандартная проверка не используется, потому что 1) так быстрее, 2) стандартная проверка умеет проверять конкретную языковую версию записи, но не любую
			if(!$is_permitted_to_change){
				$pk_message=($pk[$this->autoinc_name] ? ": (".$this->primary_key->pk_to_string($pk).")" : "");
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_operation_not_permitted_change"].$pk_message);
			}
			$pk_for_hidden=array("pk"=>$pk);
			$title=metadata::$lang["lang_change_record"];
			$card_title=$this->full_object->get_record_title($pk);
			$action="changed";
			$apply_action="changed_apply";
		}else{
			$this->full_object->is_permitted_to("add", "", true);
			$title=$card_title=metadata::$lang["lang_add_record"];
			$action="added";
		}
		
		$operations=array();
		if ($is_blocked_any && $this->full_object->is_checkinout_admin())  
			$operations[] = array("name"=>"unblock", "alt"=>metadata::$lang["lang_unblock"], "url"=>"javascript: { document.forms['{$form_name}'].action.value='unblock_record'; document.forms['{$form_name}'].submit() }" );
		
		if ($is_blocked_all) 
			$operations[] = array("name"=>"cancel", "alt"=>metadata::$lang["lang_cancel"], "url"=>$this->url->get_url( "", array( "restore_params" => 1 ) ));
		elseif ($mode=='view')
			$operations = $this->full_object->get_operations(array('back'), $form_name);
		else 
			$operations = array_merge($operations, $this -> full_object -> get_record_operations( $form_name, $apply_action ));
		
		$form = html_element::html_form($html_fields, $this->url->get_hidden($action, $pk_for_hidden), $this->tpl_dir."core/html_element/html_form.tpl", true);
		
		$this -> path_id_array[] = array( 'TITLE' => $card_title );
		
		// Дополняем статусную строку путем от текущего уровня до корня
		if ( metadata::$objects[$this->obj]['parent_field'] && $this -> parent_id )
			$this -> path_id_array = array_merge( $this -> path_id_array,
				$this -> full_object -> get_parents_list( $this -> full_object -> get_parent_info( $this -> parent_id ) ) );
		
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'title', $card_title );
		$tpl -> assign( 'form_name', $form_name );
		if ( $action == 'changed' )
			$tpl -> assign( 'tabs', $this -> full_object -> get_header_tabs( $pk ) );
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl') );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'form', $form . $full_js );
		
		
		if (!$is_blocked_all) {
			$tpl -> assign ('ping_time', round(params::$params['lock_timeout']['value']*1000/4));
			$tpl -> assign ('blocking', true);
		}
		
		return array( $title, $tpl -> fetch( $this -> tpl_dir.'core/html_element/html_card.tpl' ) );
	}
	
	/**
	* @see table::get_operations
	*/
	
	public function get_operations ($oper_array, $form_name='')  {
		$opers=$this->inner_object->get_operations($oper_array, $form_name);
		unset($opers['unblock']);
		return $opers;
	}
	
	/**
	* @see table::is_current_record_blocked
	*/
	
	public function is_current_record_blocked () {
		if (!in_array($_REQUEST['action'], $this->full_object->get_lock_actions()))
			return false;
		
		
		$pk_lang=$this->primary_key->get_from_request();
		$pk_lang["LANG_ID"]=0; // Чтобы получить сразу все версии записи
		$records=$this->full_object->get_other_langs($pk_lang);
		
		foreach($records as $record){
			$pk_lang["LANG_ID"]=$record["LANG_ID"];
			if ($this->full_object->is_permitted_to("change", $pk_lang) && !$this->full_object->is_record_blocked($pk_lang, false))
				return false;
		}

		
		return true;
	}	
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Добавляем в списке колонку "Язык"
	 *
	 * @see table::ext_index_header()
	 */
	public function ext_index_header($mode){
		return array_merge(
			$this->inner_object->ext_index_header($mode), 
			array("lang_names"=>array("title"=>metadata::$lang["lang_lang"], "type"=>"_list", "escape"=>0))
		);
	}

	/**
	 * Добавляем в операции кнопку перевода, где это требуется (в случае воркфлоу НЕ требуется)
	 *
	 * @see table::get_index_ops()
	 * @todo нужно бы еще подумать - стоит скрывать стандартный механизм перевода в случае воркфлоу или нет, потому что публикация и удаление разрешены, а перевод нет. Правда у перевода есть дополнительный шаг - выбор языка, что усложняет его реализацию
	 */
	public function get_index_ops($record){
		$ops=$this->inner_object->get_index_ops($record);
		$lang_info=$this->r_lang_info[$this->primary_key->get_id_from_record($record)];
		
		// Выясняем число удаленных версий записи
		$records_deleted = 0;
		if ( $this -> decorators['external'] )
			foreach ( $lang_info as $li_index => $li_item )
				if ( $li_item['deleted'] ) $records_deleted++;
		
		if($this->full_object->is_applied_to("translate", false) && (count($this->r_all_langs)>(count($lang_info)-$records_deleted)) && (count($lang_info)>$records_deleted) && !$this->decorators["workflow"] && $this -> is_ops_permited( 'translate', $pk[$this->autoinc_name] ) ) {
			$translate_url=$this->url->get_url("translate", array("pk"=>$this->primary_key->get_from_record($record)));
			$ops["_ops"][]=array("name"=>"translate", "alt"=>metadata::$lang["lang_translate"], "url"=>$translate_url);
		}
		
		return $ops;
	}

	/**
	 * Добавляем в запрос списка ограничение по языку.
	 *
	 * Выбирается версия записи с главным языком или (если его нет у данной записи) с любым другим.
	 * Причем, сколько бы ни было языковых версий, всегда выбирается только одна.
	 * Очередность, в котором запрос пытается вынуть ту или иную версию записи, такова: вначале главный язык,
	 * потом все остальные в алфавитном порядке названий языков
	 *
	 * @see table::ext_index_query()
	 */
	public function ext_index_query(){
		// Достраиваем запрос альтернативного выбора записи (только если в фильтре не выбран язык)
		if(!$this->lang_filter){
			$clause=$this->full_object->get_autoselect_lang_clause();
		}
		return $this->inner_object->ext_index_query().$clause;
	}

	/**
	 * Сбор кляузы автовыбора языка
	 *
	 * Выбирается версия записи с главным языком или (если его нет у данной записи) с любым другим.
	 * Причем, сколько бы ни было языковых версий, всегда выбирается только одна.
	 * Очередность, в которой запрос пытается вынуть ту или иную версию записи, такова: вначале главный язык,
	 * потом все остальные в алфавитном порядке названий языков
	 *
	 * @param string $table_name			Название таблицы в запросе. Если не указано, то используется $this->obj
	 * @see table::ext_index_query()
	 */
	public function get_autoselect_lang_clause($table_name=""){
		$table_name=($table_name ? $table_name : $this->obj);
		$shown_langs[]=$this->main_lang_id;
		// Делаем список языков без главного
		$r_langs_without_main=$this->r_all_langs;
		unset($r_langs_without_main[$this->main_lang_id]);
		// Бежим по языкам без главного, собирая кусок запроса по этим языкам
		$bind_counter=0;
		$other_counter=0;
		foreach($r_langs_without_main as $id=>$lang){
			$shown_clause="";
			// Собираем перечисление уже пройденных языков
			foreach($shown_langs as $sl){
				$shown_clause.=" L.LANG_ID=:shown_lang_id_{$bind_counter} OR ";
				$bind_counter++;
			}
			$shown_clause=substr($shown_clause, 0, strlen($shown_clause)-3);
			$alt_clause.=" OR (
				{$table_name}.LANG_ID=:other_lang_id_{$other_counter} AND NOT EXISTS (
					SELECT 1 FROM {$this->obj} L WHERE ({$shown_clause}) AND L.{$this->autoinc_name}={$table_name}.{$this->autoinc_name}
				)
			)";
			$shown_langs[]=$id;
			$other_counter++;
		}
		return " AND ({$table_name}.LANG_ID=:main_lang_id {$alt_clause})";
	}

	/**
	 * Добавляем бинды для языкового ограничения запроса списка. Зачем и как, см. {@link table_lang::ext_index_query()}
	 *
	 * @see table::ext_index_query_binds()
	 */
	public function ext_index_query_binds(){
		$binds=$this->inner_object->ext_index_query_binds();
		// Достраиваем бинды запросы альтернативного выбора записи (только если в фильтре не выбран язык)
		if(!$this->lang_filter){
			$binds+=$this->full_object->get_autoselect_lang_binds();
		}
		return $binds;
	}

	/**
	 * Добавляем бинды для языкового ограничения запроса списка. Зачем и как, см. {@link table_lang::ext_index_query()}
	 *
	 * @see table::ext_index_query_binds()
	 */
	public function get_autoselect_lang_binds(){
		$binds["main_lang_id"]=$this->main_lang_id;
		$shown_langs[]=$this->main_lang_id;
		// Делаем список языков без главного
		$r_langs_without_main=$this->r_all_langs;
		unset($r_langs_without_main[$this->main_lang_id]);
		// Бежим по языкам без главного, собирая бинды по этим языкам
		$bind_counter=0;
		$other_counter=0;
		foreach($r_langs_without_main as $id=>$lang){
			// Делаем бинды по уже пройденным языкам
			foreach($shown_langs as $sl){
				$binds["shown_lang_id_{$bind_counter}"]=$sl;
				$bind_counter++;
			}
			// Делаем бинды по текущему языку
			$binds["other_lang_id_{$other_counter}"]=$id;
			$shown_langs[]=$id;
			$other_counter++;
		}
		return $binds;
	}

	/**
	 * Дополнение присоединения таблицы специальными условиями
	 *
	 * Если в зависимой таблице нет языка нет, то выбирается язык по общему правилу, а если есть, то выбирается такой же, как у зависимой
	 */
	public function ext_join($head_table, $fk_table_name){
		list($clause, $binds)=$this->inner_object->ext_join($head_table, $fk_table_name);
		if(metadata::$objects[$head_table]["decorators"]["lang"]){
			$clause.=" AND {$fk_table_name}.LANG_ID={$head_table}.LANG_ID";
		}else{
			$clause.=$this->full_object->get_autoselect_lang_clause($fk_table_name);
			$binds+=$this->full_object->get_autoselect_lang_binds();
		}
		return array($clause, $binds);
	}

	/**
	 * Выбираем из базы набор языков для всех выбранных записей, а также сразу помещаем эти языки в записи
	 *
	 * @see table::get_index_records()
	 * @todo Подумать, как в IN можно аккуратно поместить переменные привязки
	 */
	public function get_index_records(&$request, $mode, $list_mode, $include=0, $exclude=array()){
		// Делаем очень плохую вещь: устанавливаем в ненулевое значение свойство lang_filter,
		// чтобы в select2 при установленном декораторе "Язык" попадали все записи на данном языке
		if ( $mode == 'select2' &&  $list_mode['LANG_ID'] )
			$this -> lang_filter = 'filter';
		
		$records=$this->inner_object->get_index_records($request, $mode, $list_mode, $include, $exclude);
		
		if ( $mode == 'index' )
			$this -> not_permited_ids['translate'] = $this -> full_object -> is_permitted_to_mass( 'translate', $this -> index_records_ids );
		
		// Если перевод данной записи для текущего языка отсутствует, вставляем в список фиктивную строку "Не переведено"
		if ( $mode == 'select2' &&  $list_mode['LANG_ID'] && $list_mode['FK_FIELD_ID'] )
			array_unshift( $records, array( '_TITLE' => '(' . metadata::$lang['lang_record_non_translated'] . ')', '_VALUE' => $list_mode['FK_FIELD_ID'] ) );
		
		if(count($records)>0 && $mode!="select2"){// В селекте дополнительная информация не нужна
			foreach($records as $record){
				$ids[]=$record[$this->autoinc_name];
				$selected_lang[$record[$this->autoinc_name]]=$record["LANG_ID"];
			}
			// Собираем информацию о всех языковых версиях полученных записей.
			// При необходимости делаем сортировку по версии, чтобы в дальнейшем
			// 		при формировании массива r_lang_info тестовая версия имела приоритет.
			$in=join(", ", $ids);
			$lang_info=db::replace_field(db::sql_select("
				SELECT {$this->primary_key->select_clause()}, LANG.LANG_ID AS \"_LANG_ID\"
				FROM {$this->obj}, LANG
				WHERE {$this->obj}.{$this->autoinc_name} IN ({$in})
					AND {$this->obj}.LANG_ID=LANG.LANG_ID AND LANG.IN_CONTENT = 1
				ORDER BY LANG.PRIORITY DESC, LANG.LANG_ID" . ( $this->decorators["version"] ? ", VERSION" : "" )), 'LANG_ID', '_LANG_ID');
			// Сохраняем информацию о языках записей, чтобы потом использовать ее при формировании кнопки перевода
			foreach($lang_info as $li){
				$selected=($selected_lang[$li[$this->autoinc_name]]==$li["LANG_ID"] ? 1 : 0);
				$this->r_lang_info[$li[$this->autoinc_name]][$li["LANG_ID"]]=array("title"=>$this->r_all_langs[$li["LANG_ID"]]["TITLE"], "root_dir"=>$this->r_all_langs[$li["LANG_ID"]]["ROOT_DIR"], "selected"=>$selected) + ($this->decorators["external"] ? array("deleted"=>$li["EXTERNAL_IS_DELETED"]) : array());
			}
			
			// На основании информации о языках формируем строку соответствующих изображений
			$lang_list = array();
			foreach($this->r_lang_info as $id=>$rli)
				foreach ( $rli as $lang_id => $litem )
				{
					$lang_flag_name = ( file_exists( params::$params["common_htdocs_server"]["value"] . "adm/img/lang/" . $litem["root_dir"] . ".gif" ) ? $litem["root_dir"] : "default" ) . ( $litem["selected"] ? "_selected" : "" );
					$old_lang = $_REQUEST['_f_LANG_ID'];
					$_REQUEST['_f_LANG_ID'] = $lang_id;
					$url = $this->url->get_url("", array("no_from"=>1));
					$_REQUEST['_f_LANG_ID'] = $old_lang;
					$lang_list[$id][0]["title"] .= "<a ".(($mode=='index')?"href=\"{$url}\"":'')." title=\"{$litem['title']}\"><img src=\"/common/adm/img/lang/{$lang_flag_name}.gif\" alt=\"{$litem['title']}\"/></a> ";
				}
			
			// Помещаем информацию о языках в записи
			foreach($records as $k=>$record){
				$records[$k]["lang_names"]=$lang_list[$this->primary_key->get_id_from_record($record)];
			}
		}
		return $records;
	}

	/**
	 * Получаем версии записи с языками, которые не указаны в ее первичном ключе
	 *
	 * Можно указать в первичном ключе LANG_ID=0 и тогда будут получены все версии записи
	 *
	 * @param array $pk		первичный ключ записи
	 * @return array
	 */
	public function get_other_langs($pk){
		$records=array();
		$langs=db::sql_select("
			SELECT DISTINCT LANG.LANG_ID, LANG.PRIORITY
			FROM {$this->obj}, LANG 
			WHERE {$this->obj}.{$this->autoinc_name}=:pk_id AND {$this->obj}.LANG_ID<>:shown_lang AND LANG.LANG_ID={$this->obj}.LANG_ID AND LANG.IN_CONTENT = 1
			ORDER BY LANG.PRIORITY DESC, LANG.LANG_ID",
		array("pk_id"=>$pk[$this->autoinc_name], "shown_lang"=>$pk["LANG_ID"]));
		foreach($langs as $lang){
			$records[]=$this->full_object->get_change_record(array_merge($pk, array("LANG_ID"=>$lang["LANG_ID"])));
		}
		return $records;
	}
	
	/**
	* Получает дополнительные данные для лога
	*/
	public function get_additional_info_for_log($fields) {
		if (!isset($fields['log_params']['lang_id']))
			$fields['log_params']['lang_id']=$fields['LANG_ID'];
		return $this->inner_object->get_additional_info_for_log($fields);
	}
	
	/**
	* Дополнительный JavaSript в карточке добавления записи
	* 
	* Оно конечно нужно было бы использовать специально для это предназначенный метод ext_html_card(),
	* но в него не передается $mode, а данный код нужно вставлять на страницу только в карточке добавления
	*/
	public function get_change_language_js( &$request )
	{
		$field_lang_records_array = array();
		foreach ( metadata::$objects[$this -> obj]['fields'] as $field_name => $field_item )
		{
			// Нас интересуют только поля типа select2 из таблиц с декоратором 'lang'
			if ( $field_item['type'] == 'select2' && isset( metadata::$objects[$field_item['fk_table']]['decorators']['lang'] ) )
			{
				// Получаем объект присоединяемой таблицы
				$fk_table = object::factory( $field_item['fk_table'] );
				
				// Заполняем массив списками записей на всех возможных языках
				$lang_records = array();
				foreach ( $this -> r_all_langs as $lang )
					$lang_records[$lang['LANG_ID']] =
						$fk_table -> get_index_records( $request, 'select2', array( 'LANG_ID' => $lang['LANG_ID'], 'use_rights' => metadata::$objects[$this -> obj]['fields'][$field_name]['list_mode']['use_rights'] ) );
				
				// Преобразовываем массив в строку-объект JavaScript
				$lang_records_array = array();
				foreach( $lang_records as $lang_id => $records )
				{
					$records_array = array();
					foreach( $records as $record_id => $record )
						$records_array[] = "'" . $record['_VALUE'] . "': '" . addslashes( $record['_TITLE'] ) . "'";
					$lang_records_array[] = "'" . $lang_id . "': { " . join( ', ', $records_array ) . " }";
				}
				$field_lang_records_array[] = "'_form_" . $field_name . "': { " . join( ', ', $lang_records_array ) . " }";
				
				$fk_table -> __destruct ();
			}
		}
		
		// Выходим, если полей, удовлетворяющих нашему условию, у объекта не найдено
		if ( !count( $field_lang_records_array ) ) return '';
		
		$form_name = html_element::get_form_name();
		$field_lang_records_array_list = join( ', ', $field_lang_records_array );
		
		return <<<HTM

<script type="text/javascript">
	var sFormName = '{$form_name}';
	var aFieldLangRecords = { {$field_lang_records_array_list} };
	
	var oLangSelect = document.forms[sFormName]['_form_LANG_ID'];
	if ( oLangSelect )
	{
		addListener( oLangSelect, 'change', changeLangSelect ); changeLangSelect();
	}
	
	function changeLangSelect()
	{
		for ( var sField in aFieldLangRecords )
			setFieldSelect( document.forms[sFormName][sField],
				aFieldLangRecords[sField][oLangSelect.options[oLangSelect.selectedIndex].value] );
	}
	
	function setFieldSelect( oSelect, aOptions )
	{
		if ( !oSelect ) return;
		
		while ( oSelect.firstChild )
			 oSelect.removeChild( oSelect.firstChild );
		
		oSelect.options.add( new Option() );
		for ( var iOption in aOptions )
		{
			var oOption = new Option( aOptions[iOption], iOption );
			oSelect.options.add( oOption );
		}
	}
</script>
HTM;
	}
	
	/**
	 * Метод проверяет существование у записи версии на языке перевода
	 *
	 * @param array $record		текущая запись
	 * @param array $lang_id	язык перевода
	 */
	public function is_language_exists( $record, $lang_id )
	{
		$pk = $this -> primary_key -> get_from_record( $record );
		
		$where_clause = $this -> primary_key -> where_clause();
		$bind_array = $this -> primary_key -> bind_array( $pk );
		
		// Заменяем в параметрах текущий язык записи на язык перевода
		$bind_array['pk_lang_id'] = $lang_id;
		
		$check_lang = db::sql_select( 'select count(*) as COUNTER from ' . $this->obj . ' where ' . $where_clause, $bind_array );
		
		if ( $check_lang[0]['COUNTER'] > 0 )
			throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_language_exists'] . ' - ' . $lang_id . ': "' . $this -> full_object -> get_record_title( $pk ) . '" (' . $this -> primary_key -> pk_to_string( $pk ) . ')' );
	}
	
	/**
	 * Метод проверяет существование в присоединяемых таблицах с декоратором "Язык"
	 * записей на языке перевода, на который ссылается текущая запись из подчиненной таблицы
	 *
	 * @param array $record		текущая запись
	 * @param array $lang_id	язык перевода
	 */
	public function translate_link_table_check( $record, $lang_id )
	{
		$pk = $this -> primary_key -> get_from_record( $record );
		
		// Проходимся по всем полям подчиненной таблицы
		foreach ( metadata::$objects[$this -> obj]['fields'] as $field_name => $field_item )
		{
			// Условие срабатывает только для заполненых или обязательных полей,
			// которые указывают на присоединяемую таблицу с декоратором "Язык"
			if ( ( $field_item['type'] == 'select2' ) && ( $record[$field_name] || ( $field_item['errors'] & _nonempty_ ) ) &&
				isset( metadata::$objects[$field_item['fk_table']]['decorators']['lang'] ) )
			{
				// Получаем объект присоединяемой таблицы
				$fk_table = object::factory( $field_item['fk_table'] );
				
				// Проверяем, переведена ли соответствующая запись в присоединяемой таблице
				if ( !lib::is_record_exists( $fk_table -> obj, array( $fk_table -> autoinc_name => $record[$field_name], 'LANG_ID' => $lang_id ) ) )
					throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_no_translate_link_table'] . ': "' . $this -> full_object -> get_record_title( $pk ) . '" (' . $this -> primary_key -> pk_to_string( $pk ) . ') ' );
					
				$fk_table -> __destruct ();
			}
		}
	}
	
	/**
	* Удаляем из информации для экспорта поле LANG_ID
	*/
	
	public function get_fields_for_export() {
		$fields = $this->inner_object->get_fields_for_export();
		unset($fields['LANG_ID']);
		return $fields;
	}
	
	/**
	* Возвращает поля для вставки в таблицу в процессе импорта - унаследованный метод от table
	* Дополняет функционал добавлением поля LANG_ID в которое записываем язык корневого раздела, под который проводится импорт
	* @param array $main_children данные обо всех потомках массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return array Подготовленные данные для вставки в таблицу
	*/

	public function get_import_field_values($main_children, &$import_data) {
		$field_values = $this->inner_object->get_import_field_values($main_children, $import_data);
		$field_values['LANG_ID']=$import_data['root_page_info']['LANG_ID'];
		return $field_values;
	}
	
	/**
	* Удаляем стандартную обработку changed и changed_apply
	* @see table::get_commit_lock_actions
	* @return array
	*/
	
	public function get_commit_lock_actions() {
		return array_diff($this->inner_object->get_commit_lock_actions(), array('changed', 'changed_apply'));
	}
	
	/**
	 * Учет языка при автозаполнении поля порядок
	 *
	 * @see table::get_group_where()
	 */
	public function get_group_where( $group_by, &$group_values, $prefix )
	{
		list( $where, $joins, $binds ) = $this -> inner_object -> get_group_where( $group_by, $group_values, $prefix );
		
		if ( $group_values[$prefix . 'LANG_ID'] )
		{
			$binds['g_join_lang_id'] = $group_values[$prefix . 'LANG_ID'];
			$where .= ' AND LANG_ID = :g_join_lang_id ';
		}
		
		return array( $where, $joins, $binds );
	}
}
?>
