<?php
/**
 * Класс декоратор таблиц "Цепочка публикации (workflow)"
 *
 * @package		RBC_Contents_5_0
 * @subpackage core
 * @copyright	Copyright (c) 2007 RBC SOFT
 * @todo сейчас у нас уведомления рассылаются только на почту, а хорошо бы их и в базе сохранять, чтобы можно было просмотреть прямо из системы, что на тебе висит
 * @todo Есть идея распилить этот файл на общий и расширения по признаку версия, язык, блоки, но стоит ли так делать - отдельный вопрос, надо изучать
 * @todo Копирование записей сейчас некорректно работает на языках, блоках, возможно воркфлоу - нужно все это делать
 */
class table_workflow extends decorator{

	/**
	 * Название таблицы разделения доступа - AUTH_SYSTEM_SECTION или INF_BLOCK, если соответственно нет или есть декоратор block
	 * @var string
	 */
	protected $aot_table;
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Конструктор. В зависимости от декораторов определяемся с таблицей разделения доступа
	 *
	 * @see table::__construct()
	 */
	function __construct(&$full_object, $decorators){
		parent::__construct($full_object, $decorators);
		$this->aot_table=($this->decorators["block"] ? "INF_BLOCK" : "AUTH_SYSTEM_SECTION");
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Выставляем состояние свежедобавленной записи (а можем еще и резолюцию наложить, если надо)
	 *
	 * @see table::exec_add()
	 * @todo Проводится тройная проверка прав с последовательным наращиванием первичного ключа (блок, язык). Надо попробовать устранить такую неоптимальность
	 */
	public function exec_add($raw_fields, $prefix){
		// Проверяем права отдельно, чтобы указать язык
		$lang_id=($this->decorators["lang"] ? $raw_fields["{$prefix}LANG_ID"] : 0);
		$pk=array();
		$pk+=($this->decorators["block"] ? array("INF_BLOCK_ID"=>$raw_fields[$prefix."INF_BLOCK_ID"]) : array());
		$pk+=($this->decorators["lang"] ? array("LANG_ID"=>$lang_id) : array());
		$this->full_object->is_permitted_to("add", $pk, true);
		// Собственно добавление
		$new_state=$this->full_object->get_new_state_id($pk, true);
		// Для таблиц без декоратора "Язык" в качестве начального состояния берем нулевой элемент массива
		if (!$this->decorators["lang"]) $new_state = $new_state[0];
		$last_insert_id=$this->inner_object->exec_add($raw_fields, $prefix);
		$pk=array($this->autoinc_name=>$last_insert_id)+($this->decorators["lang"] ? array("LANG_ID"=>$lang_id) : array());
		db::update_record($this->obj, array("WF_STATE_ID"=>$new_state), "", $pk);
		// Наложить резолюцию
		if($raw_fields["{$prefix}WF_RESOLUTION_ID"]){
			$this->full_object->exec_resolve($pk, $raw_fields["{$prefix}WF_RESOLUTION_ID"], $raw_fields["{$prefix}COMMENTS"], $this->full_object->get_users_for_resolution($raw_fields, $prefix), $this->full_object->get_privs_for_resolution($raw_fields, $prefix));
		}
		return $last_insert_id;
	}


	/**
	 * После изменения можем еще и наложить резолюцию, если надо
	 *
	 * Информация о резолюции должна быть в $raw_fields как после обычного сабмита формы
	 *
	 * @see table::exec_change()
	 * @todo свести код отсюда, из exec_added и из action_resolved в единое место
	 */
	public function exec_change($raw_fields, $prefix, $pk){
		$this->inner_object->exec_change($raw_fields, $prefix, $pk);
		// Наложить резолюцию
		if($raw_fields["{$prefix}WF_RESOLUTION_ID"]){
			$this->full_object->exec_resolve($pk, $raw_fields["{$prefix}WF_RESOLUTION_ID"], $raw_fields["{$prefix}COMMENTS"], $this->full_object->get_users_for_resolution($raw_fields, $prefix), $this->full_object->get_privs_for_resolution($raw_fields, $prefix));
		}
	}

	/**
	 * Удаление заведено на механизм наложения резолюций, которое уже само умеет удалять
	 *
	 * @see table::exec_delete()
	 * @todo Сейчас все связи, которые должны удалять нижележащие декораторы, будут удаляться только при физическом удалении записи, в то время как дополнительные операции после переопределения данного метода будут выполняться всегда, даже в случае "удаления в корзину". Надо обдумать этот скользкий момент.
	 * @todo Нужно будет делать заточку под администраторов сайтов, когда они будут сделаны
	 */
	public function exec_delete( $pk )
	{
		// Получение идентификатора резолюции удаления
		$resolution_id = $this -> get_delete_resolution( $pk );
		
		// Получаем список привилегий, необходимых для наложения данной резолюции
		$resolution_privileges = array_keys(lib::array_reindex(
			$this->get_resolution_privileges($pk, $resolution_id), "AUTH_PRIVILEGE_ID"));
		
		// Вызов стандартной обрезолючивалки. Пытаемся наложить все возможные права
		$this->full_object->exec_resolve($pk, $resolution_id, "", array(), $resolution_privileges);
	}

	/**
	 * Опубликовать запись - заведено на механизм наложения резолюций
	 *
	 * @see table_version::exec_publish()
	 */
	public function exec_publish( $pk )
	{
		// Получение идентификатора резолюции публикации
		$resolution_id = $this -> get_publish_resolution( $pk );
		
		// Получаем список привилегий, необходимых для наложения данной резолюции
		$resolution_privileges = array_keys(lib::array_reindex(
			$this->get_resolution_privileges($pk, $resolution_id), "AUTH_PRIVILEGE_ID"));
		
		// Вызов стандартной обрезолючивалки. Пытаемся наложить все возможные права
		$this->full_object->exec_resolve($pk, $resolution_id, "", array(), $resolution_privileges);
	}
	
	/**
	 * Снять с публикации запись - заведено на механизм наложения резолюций
	 *
	 * @see table_version::exec_unpublish()
	 */
	public function exec_unpublish( $pk )
	{
		// Получение идентификатора резолюции снятия с публикации
		$resolution_id = $this -> get_unpublish_resolution( $pk );
		
		// Получаем список привилегий, необходимых для наложения данной резолюции
		$resolution_privileges = array_keys(lib::array_reindex(
			$this->get_resolution_privileges($pk, $resolution_id), "AUTH_PRIVILEGE_ID"));
		
		// Вызов стандартной обрезолючивалки. Пытаемся наложить все возможные права
		$this->full_object->exec_resolve($pk, $resolution_id, "", array(), $resolution_privileges);
	}
	
	/**
	 * Отменить изменения - заведено на механизм наложения резолюций
	 *
	 * @see table_version::exec_undo()
	 */
	public function exec_undo( $pk )
	{
		// Получение идентификатора резолюции отмены изменений
		$resolution_id = $this -> get_undo_resolution( $pk );
		
		// Получаем список привилегий, необходимых для наложения данной резолюции
		$resolution_privileges = array_keys(lib::array_reindex(
			$this->get_resolution_privileges($pk, $resolution_id), "AUTH_PRIVILEGE_ID"));
		
		// Вызов стандартной обрезолючивалки. Пытаемся наложить все возможные права
		$this->full_object->exec_resolve($pk, $resolution_id, "", array(), $resolution_privileges);
	}

	/**
	 * Перевести - заведено на механизм наложения резолюций
	 *
	 * @see table_lang::exec_translate()
	 */
	public function exec_translate( $pk, $lang_id )
	{
		// Получение идентификатора зезолюции перевода
		$resolution_id = $this -> get_translate_resolution( $pk, $lang_id );
		
		// Получаем список привилегий, необходимых для наложения данной резолюции
		$resolution_privileges = array_keys(lib::array_reindex(
			$this->get_resolution_privileges($pk, $resolution_id), "AUTH_PRIVILEGE_ID"));
		
		// Вызов стандартной обрезолючивалки. Пытаемся наложить все возможные права
		$this->full_object->exec_resolve($pk, $resolution_id, "", array(), $resolution_privileges);
	}
	
	/**
	 * Применить резолюцию к записи со всеми последствиями, вплоть до удаления
	 *
	 * Причем настоящее удаление происходит только в том случае, если у состояния после удаления нет версий
	 *
	 * @param array $pk				первичный ключ записи
	 * @param int $resolution_id	идентификатор резолюции, которая накладывается на запись
	 * @param string $comments		комментарии к резолюции
	 * @param array $users			пользователи административной системы, которым должны быть высланы уведомления
	 * @param array $privileges		права цепочки публикации для наложения резолюции (используется только в параллельном согласовании)
	 */
	public function exec_resolve($pk, $resolution_id, $comments, $users, $privileges=array()){
		// проверяем блокировку
		$this->full_object->is_record_blocked($pk);

		// Проверка прав и выемка необходимых вспомогательных данных
		$record=$this->full_object->get_change_record($pk, true);
		$this->full_object->is_permitted_to("resolve", $pk+array("WF_RESOLUTION_ID"=>$resolution_id, "WF_STATE_ID"=>$record["WF_STATE_ID"]), true);
		
		
		$resolution_obj = object::factory( 'WF_RESOLUTION' );
		list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
			$resolution_obj -> ext_field_selection( 'TITLE', 1 );
		
		$block_id=($this->decorators["block"] ? $this->full_object->get_main_block_id($pk) : "");
		$resolution=db::replace_field(db::sql_select("
			SELECT WF_RESOLUTION.*, " . $dec_field . " AS \"_TITLE\"
				,WF_STATE1.WF_STATE_ID AS WF_STATE1_ID, WF_STATE1.VERSIONS AS WF_STATE1_VERSIONS
				,WF_STATE2.WF_STATE_ID AS WF_STATE2_ID, WF_STATE2.VERSIONS AS WF_STATE2_VERSIONS
			FROM WF_STATE WF_STATE1, WF_STATE WF_STATE2, WF_RESOLUTION
			" . $dec_join[0] . "
			WHERE WF_RESOLUTION_ID=:resolution_id
				AND WF_STATE1.WF_STATE_ID=WF_RESOLUTION.FIRST_STATE_ID
				AND WF_STATE2.WF_STATE_ID=WF_RESOLUTION.LAST_STATE_ID
				AND WF_STATE1.WF_WORKFLOW_ID=:workflow_id
		", array("resolution_id"=>$resolution_id, "workflow_id"=>$this->full_object->get_workflow_id($block_id)) + $dec_binds), 'TITLE', '_TITLE');
		
		// Если такой резолюции нет, то бросаем исключение
		if(!$resolution[0]["WF_RESOLUTION_ID"]){
			$res_message=($resolution_id ? " - {$resolution_id}" : "");
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_resolution_not_found"]."{$res_message}: \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
		}
		// Проверка на то, чтобы у записи было такое же исходное состояние, как у резолюции
		if($record["WF_STATE_ID"]!=$resolution[0]["WF_STATE1_ID"]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_resolution_fisrt_state_problem"]." - \"".$resolution_obj->get_record_title(array("WF_RESOLUTION_ID"=>$resolution_id))."\" ({$resolution_id}): \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
		}
		// Если нет декоратора "версия", а резолюция ведет из рабочей версии в рабочую же, то бросаем исключение
		if(!$this->decorators["version"] && $resolution[0]["WF_STATE1_VERSIONS"]=="two_versions" && $resolution[0]["WF_STATE2_VERSIONS"]=="two_versions"){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_resolution_work_to_work_not_allowed"]." - \"".$resolution_obj->get_record_title(array("WF_RESOLUTION_ID"=>$resolution_id))."\" ({$resolution_id}): \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
		}
		// Если есть декоратор "Версия" и происходит публикация, проверяем существование опубликованных записей в присоединяемых таблицах
		if($this->decorators["version"] && $resolution[0]["WF_STATE1_VERSIONS"]=="test_version" && $resolution[0]["WF_STATE2_VERSIONS"]=="two_versions"){
			$this -> full_object -> publish_link_table_check( $record );
		}
		// Если есть декоратор "Версия" и происходит снятие публикации, проверяем существование опубликованных записей в присоединяемых таблицах
		if($this->decorators["version"] && $resolution[0]["WF_STATE1_VERSIONS"]=="two_versions" && $resolution[0]["WF_STATE2_VERSIONS"]=="test_version"){
			$this -> full_object -> unpublish_link_table_check( $record );
		}
		// Если нет декоратора "язык", а резолюция является переводом, то бросаем исключение
		if(!$this->decorators["lang"] && $resolution[0]["LANG_ID"]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_resolution_translate_not_allowed"]." - \"".$resolution_obj->get_record_title(array("WF_RESOLUTION_ID"=>$resolution_id))."\" ({$resolution_id}): \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
		}
		// Если есть декоратор "язык", и резолюция является переводом, проверяем существование у записи версии на языке перевода
		if ( $this -> decorators['lang'] && $resolution[0]['LANG_ID'] )
			$this -> full_object -> is_language_exists( $record, $resolution[0]['LANG_ID'] );
		// Если есть декоратор "язык", и резолюция является переводом, проверяем существование перевода записи в присоединяемых таблицах
		if ( $this -> decorators['lang'] && $resolution[0]['LANG_ID'] )
			$this -> full_object -> translate_link_table_check( $record, $resolution[0]['LANG_ID'] );
		
		$resolution_obj -> __destruct();
		
		// Переводим названия состояний
		$state_obj = object::factory( 'WF_STATE' );
		
		list( $dec_field1, $dec_where_search1, $dec_join1, $dec_binds1 ) =
			$state_obj -> ext_field_selection( 'TITLE', 1 );
		list( $dec_field2, $dec_where_search2, $dec_join2, $dec_binds2 ) =
			$state_obj -> ext_field_selection( 'TITLE', 1 );
		$state_obj -> __destruct();
		
		$state1=db::sql_select('SELECT ' . $dec_field1 . ' AS WF_STATE1_TITLE FROM WF_STATE ' . $dec_join1[0] . ' WHERE WF_STATE_ID = :state_id', array( 'state_id' => $resolution[0]['WF_STATE1_ID'] ) + $dec_binds1);
		$resolution[0]['WF_STATE1_TITLE'] = $state1[0]['WF_STATE1_TITLE'];
		
		$state2=db::sql_select('SELECT ' . $dec_field2 . ' AS WF_STATE2_TITLE FROM WF_STATE ' . $dec_join2[0] . ' WHERE WF_STATE_ID = :state_id', array( 'state_id' => $resolution[0]['WF_STATE2_ID'] ) + $dec_binds2);
		$resolution[0]['WF_STATE2_TITLE'] = $state2[0]['WF_STATE2_TITLE'];
		
		// выбираем для лога
		$record_title = $this->full_object->get_record_title($pk);
		
		// Собственно движение контента
		list( $verdict, $approved_privileges ) = $this -> full_object -> do_parallel_resolution( $pk, $resolution[0], $comments, $users, $privileges );
		
		if( $verdict )
		{
			$this->full_object->resolve_content($pk, $resolution[0]);
			$this->full_object->send_wf_notify($pk, $resolution[0], $comments, $users);
			$this->full_object->purge_old_resolution($pk);
		}
		
		// Получаем список применных прав для записи его в лог
		$auth_privilege_obj = object::factory( 'AUTH_PRIVILEGE' );
		list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
			$auth_privilege_obj -> ext_field_selection( 'TITLE', 1 );
		$auth_privilege_obj -> __destruct();
		
		$resolution_privileges = db::sql_select( '
			select ' . $dec_field . ' as TITLE
			from AUTH_PRIVILEGE ' . $dec_join[0] . '
				inner join WF_PRIVILEGE_RESOLUTION on
					AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID = WF_PRIVILEGE_RESOLUTION.AUTH_PRIVILEGE_ID and
					WF_PRIVILEGE_RESOLUTION.WF_RESOLUTION_ID = :resolution_id
			' . ( is_array( $approved_privileges ) ? 'where AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID in ( ' .
				( count( $approved_privileges ) ? join( ', ', $approved_privileges ) : '0' ) . ' )' : '' ),
			array( 'resolution_id' => $resolution_id ) + $dec_binds );
		
		$resolution_privileges_list = array();
		foreach ( $resolution_privileges as $resolution_privilege )
			$resolution_privileges_list[] = $resolution_privilege['TITLE'];
		
		$this->full_object->log_register('resolve', array_merge($record, array('object_name'=>$record_title, 'resolutions'=>$resolution[0], "resolution_comments"=>$comments, "resolution_privileges"=>join( ', ', $resolution_privileges_list ))));
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Наложить резолюцию на конкретную запись при групповом обрезолючивании
	 */
	public function make_group_resolve($pk){
		$this -> full_object -> exec_resolve( $pk, $_REQUEST["_form_WF_RESOLUTION_ID"], $_REQUEST["_form_COMMENTS"], array(), $this -> full_object -> get_privs_for_resolution( $_REQUEST, "_form_" ) );
	}	

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Добавляем в списке колонку "Резолюции"
	 *
	 * @see table::ext_index_header()
	 */
	public function ext_index_header($mode){
		return array_merge(
			$this->inner_object->ext_index_header($mode), 
			array("resolution"=>array("title"=>metadata::$lang["lang_resolutions"], "type"=>"_link"))
		);
	}

	/**
	 * Выводим поля с резолюциями на карточках добавления и редактирования
	 *
	 * @see table::ext_html_card()
	 */
	public function ext_html_card($form_name, $record){
		list($wf_fields, $wf_js)=$this->full_object->get_wf_fields_and_script("_form_", $record, false, true);
		return array($wf_fields, $wf_js);
	}


///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Действие - добавление записи
	 * 
	 * @see table::action_add()
	 */
	public function action_add()
	{
		// Ограничиваем список доступных языков при добавлении записи. Для блочных таблиц - только в случае, если указан блок
		if ( $this -> decorators['lang'] && ( !$this -> decorators['block'] || $_REQUEST['_f_INF_BLOCK_ID'] ) )
		{
			$allow_langs = array();
			foreach ( $this -> full_object -> r_all_langs as $lang_info )
				if ( $this -> full_object -> is_permitted_to( 'add', array( 'LANG_ID' => $lang_info['LANG_ID'] ) +
					( $this -> decorators['block'] ? array( 'INF_BLOCK_ID' => $_REQUEST['_f_INF_BLOCK_ID'] ) : array() ) ) )
						$allow_langs[] = $lang_info['LANG_ID'];
			
			if ( !count( $allow_langs ) )
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_operation_not_permitted_add"]);
			
			metadata::$objects[$this -> obj]['fields']['LANG_ID']['list_mode'] = array( 'direct' => $allow_langs );
		}
		
		$this -> inner_object -> action_add();
	}
	
	/**
	 * Действие - список записей текущей таблицы (страница по умолчанию)
	 * 
	 * @see table::action_index()
	 */
	public function action_index()
	{
		// Ограничиваем вывод списка состояний выбранной цепочкой побликаций
		$workflow_id = ( metadata::$objects[$this -> obj]['workflow_scope'] == 'block' && !$_REQUEST['_f_INF_BLOCK_ID'] ) ?
			'' : $this -> full_object -> get_workflow_id( $_REQUEST['_f_INF_BLOCK_ID'] );
		
		metadata::$objects[$this -> obj]['fields']['WF_STATE_ID']['list_mode'] = array( 'WF_WORKFLOW_ID' => $workflow_id );
		
		$this -> inner_object -> action_index();
	}
	
	/**
	 * Действие - Интерфейс наложения резолюций
	 */
	public function action_resolution(){
		// Запись
		$pk=$this->primary_key->get_from_request(true);
		$this->full_object->is_permitted_to("change", $pk, true);
		$record=$this->full_object->get_change_record($pk);
		$record_title=$this->full_object->get_record_title($pk);
		$state_title=object::factory("WF_STATE")->get_record_title(array("WF_STATE_ID"=>$record["WF_STATE_ID"]));
		// Табличка с описанием записи
		$headers=array(
			"TITLE"=>				array("title"=>metadata::$lang["lang_name"]),
			"WF_STATE"=>			array("title"=>metadata::$lang["lang_state"])
		);
		$view_record=array("TITLE"=>$record_title, "WF_STATE"=>$state_title);
		if($this->decorators["lang"]){
			$headers+=array("LANG"=>array("title"=>metadata::$lang["lang_lang"]));
			$view_record["LANG"]=$this->r_all_langs[$record["LANG_ID"]]["TITLE"];
		}
		if($this->decorators["version"]){
			$headers+=array("VERSION"=>array("title"=>metadata::$lang["lang_version"]));
			$view_record["VERSION"]=($record["VERSION"] ? metadata::$lang["lang_test_version"] : metadata::$lang["lang_work_version"]);
		}
		
		// Выводим в шаблон информацию о начатом параллельном согласовании
		$resolution_title=object::factory("WF_RESOLUTION")->get_record_title(array("WF_RESOLUTION_ID"=>$record["WF_RESOLUTION_ID"]));
		if ( $resolution_title )
		{
			$headers += array( 'APPROVED_BEGIN' => array( 'title' => metadata::$lang["lang_wf_approved_begin"], 'type' => 'text' ) );
			$view_record['APPROVED_BEGIN'] = $resolution_title;
		}
		
		$record_table=html_element::html_table( array( "header" => $headers, "list" => array($view_record), "counter" => 1 ), $this->tpl_dir."core/html_element/html_table.tpl", $this->field);
		// Список ранее наложенных резолюций. Для простоты сборки запроса попользуемся первичным ключом с некоторой модификацией
		$where_clause=str_replace(array($this->autoinc_name, $this->obj), array("OBJECT_ID", "LR"), $this->primary_key->where_clause($pk));
		$bind_array=$this->primary_key->bind_array($pk);
		// Если есть язык, то достраиваем ограничения по языку
		if($pk["LANG_ID"]){
			$where_clause.=" AND LANG_ID=:lang_id";
			$bind_array+=array("lang_id"=>$pk["LANG_ID"]);
		}
		// Не используем $user_join и $user_binds, потому что переводимые таблицы не снабжаются workflow
		list($user_field, $user_join, $user_binds)=object::factory("AUTH_USER")->get_short_title_clause();
		$resolutions = db::sql_select("
			SELECT LR.*, {$user_field} AS AUTH_USER_TITLE
			FROM LOG_RECORD LR
			LEFT JOIN AUTH_USER  ON AUTH_USER.AUTH_USER_ID=LR.AUTH_USER_ID
			WHERE LR.TE_OBJECT_ID=:te_object_id AND LR.LOG_OPERATION_ID=:log_operation_id
				AND ".$where_clause."
			ORDER BY OPERATION_DATE DESC
		", $bind_array+array("te_object_id"=>$this->te_object_id, "log_operation_id"=>log::$log_operations[log::$log_types['log_records_change']['LOG_TYPE_ID']]['resolve']['LOG_OPERATION_ID']));
		
		foreach($resolutions as &$resolution) {
			$add_fields = log::get_complex_field($resolution['LOG_INFO']);
			$resolution["DESCR"] = $add_fields['resolutions'];
			$resolution["COMMENTS"] = $add_fields['resolution_comments'];
			$resolution["PRIVILEGES"] = $add_fields['resolution_privileges'];
			$resolution["OPERATION_DATE"]=lib::unpack_date($resolution["OPERATION_DATE"], "full");
		}
		
		// Табличка со списком резолюций
		$headers=array(
			"DESCR"=>			array("title"=>metadata::$lang["lang_resolution"]),
			"AUTH_USER_TITLE"=>	array("title"=>metadata::$lang["lang_administrator"]),
			"OPERATION_DATE"=>	array("title"=>metadata::$lang["lang_time"]),
			"PRIVILEGES"=>		array("title"=>metadata::$lang["lang_wf_approved_privileges"]),
			"COMMENTS"=>		array("title"=>metadata::$lang["lang_comments"], "escape"=>1),
		);
		
		$resolution_table=
			html_element::html_table( array( "header" => $headers, "list" => $resolutions, "counter" => count($resolutions) ), $this->tpl_dir."core/html_element/html_table.tpl", $this->field);
		
		// Сбор формы наложения резолюции
		$form_name = html_element::get_next_form_name();
		list($wf_fields, $wf_js)=$this->full_object->get_wf_fields_and_script("_form_", $record, true, false);

		$html_fields=html_element::html_fields($wf_fields, $this->tpl_dir."core/html_element/html_fields.tpl", $this->field);
		$resolution_form=html_element::html_form($html_fields, $this->url->get_hidden("resolved", array("pk"=>$pk)), $this->tpl_dir."core/html_element/html_form.tpl", true);

		$operations = $this -> get_record_operations( $form_name );
		
		$card_title = $this -> full_object -> get_record_title( $pk );
		$this -> path_id_array[] = array( 'TITLE' => $card_title );
		
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'title', $card_title );
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'form', $record_table.$resolution_table.$resolution_form.$wf_js );
		$tpl -> assign( 'form_name', $form_name );
		$this->full_object->set_blocked_tpl_params ($tpl, $pk);
		
		$this -> title = metadata::$lang['lang_resolutions'];
		$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' );
	}
	
	/**
	 * Действие - Наложение резолюции
	 */
	public function action_resolved() {
		$pk=$this->primary_key->get_from_request(true);

		if($pk["LANG_ID"])
			$lang_prefix=$pk["LANG_ID"]."_";

		$this->full_object->exec_resolve($pk, $_REQUEST["_form_{$lang_prefix}WF_RESOLUTION_ID"], $_REQUEST["_form_{$lang_prefix}COMMENTS"], $this->full_object->get_users_for_resolution($_REQUEST, "_form_{$lang_prefix}"), $this->full_object->get_privs_for_resolution($_REQUEST, "_form_{$lang_prefix}"));
		$this->url->redirect();
	}
	
	/**
	 * Интерфейс массового наложения резолюций
	 */
	public function action_group_resolution()
	{
		list( $form_fields, $js ) = $this -> full_object -> get_group_wf_fields_and_script( '_form_', true );
		
		$this -> full_object -> group_action_form( $form_fields, 'group_resolved', metadata::$lang['lang_resolutions'], html_element::get_form_name(), $js );
	}
	
	/**
	 * Действие - массово накладывает резолюцию
	 */
	public function action_group_resolved()
	{
		if(!metadata::$objects[$this->obj]["no_change"]){
			$this->full_object->group_action("make_group_resolve");
		}
	}
	
	/**
	 * Действие - публикация записи
	 */
	public function action_publish()
	{
		$pk = $this -> primary_key -> get_from_request();
		
		// Получение идентификатора резолюции публикации
		$resolution_id = $this -> get_publish_resolution( $pk );
		
		// Получаем список привилегий, необходимых для наложения данной резолюции
		$resolution_privileges = $this -> get_resolution_privileges( $pk, $resolution_id, true );
		
		// Если у администратора остались неспользованные права, перебрасываем его на карточку наложения резолюций
		if ( count( $resolution_privileges ) > 1 )
			$this -> url -> redirect( 'resolution', array( 'pk' => $pk + array( '_f_WF_RESOLUTION_ID' => $resolution_id ) ) );
		
		$this -> full_object -> exec_publish( $pk );
		$this -> url -> redirect();
	}
	
	/**
	 * Действие - снятие с публикации
	 */
	public function action_unpublish()
	{
		$pk = $this -> primary_key -> get_from_request();
		
		// Получение идентификатора резолюции снятия с публикации
		$resolution_id = $this -> get_unpublish_resolution( $pk );
		
		// Получаем список привилегий, необходимых для наложения данной резолюции
		$resolution_privileges = $this -> get_resolution_privileges( $pk, $resolution_id, true );
		
		// Если у администратора остались неспользованные права, перебрасываем его на карточку наложения резолюций
		if ( count( $resolution_privileges ) > 1 )
			$this -> url -> redirect( 'resolution', array( 'pk' => $pk + array( '_f_WF_RESOLUTION_ID' => $resolution_id ) ) );
		
		$this -> full_object -> exec_unpublish( $pk );
		$this -> url -> redirect();
	}
	
	/**
	 * Действие - отмена изменений
	 */
	public function action_undo()
	{
		$pk = $this -> primary_key -> get_from_request();
		
		// Получение идентификатора резолюции отмены изменений
		$resolution_id = $this -> get_undo_resolution( $pk );
		
		// Получаем список привилегий, необходимых для наложения данной резолюции
		$resolution_privileges = $this -> get_resolution_privileges( $pk, $resolution_id, true );
		
		// Если у администратора остались неспользованные права, перебрасываем его на карточку наложения резолюций
		if ( count( $resolution_privileges ) > 1 )
			$this -> url -> redirect( 'resolution', array( 'pk' => $pk + array( '_f_WF_RESOLUTION_ID' => $resolution_id ) ) );
		
		$this -> full_object -> exec_undo( $pk );
		$this -> url -> redirect();
	}
	
	/**
	 * Действие - удаление
	 */
	public function action_delete()
	{
		$pk = $this -> primary_key -> get_from_request();
		
		// Получение идентификатора резолюции удаления
		$resolution_id = $this -> get_delete_resolution( $pk );
		
		// Получаем список привилегий, необходимых для наложения данной резолюции
		$resolution_privileges = $this -> get_resolution_privileges( $pk, $resolution_id, true );
		
		// Если у администратора остались неспользованные права, перебрасываем его на карточку наложения резолюций
		if ( count( $resolution_privileges ) > 1 )
			$this -> url -> redirect( 'resolution', array( 'pk' => $pk + array( '_f_WF_RESOLUTION_ID' => $resolution_id ) ) );
		
		$this -> full_object -> exec_delete( $pk );
		$this -> url -> redirect();
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Обеспечение параллельного согласования
	 *
	 * Производит учет в БД голоса при параллельном согласовании.<br>
	 * Если процесс согласования дошел до кворума или резолюция последовательная, то возвращает true, в противном случае - false.
	 * true означает, что можно передавать управление resolve_content() для непосредственного осуществления перехода записи в новое состояние.
	 * Для параллельных резолюций возвращется также список наложенных прав.
	 *
	 * @param array $pk				первичный ключ записи
	 * @param array $resolution		описание резолюции, которая определяет клонирование (включая данные о состояниях - WF_STATE1_VERSIONS, WF_STATE2_VERSIONS
	 * @param array $users			пользователи административной системы, которым должны быть высланы уведомления
	 * @param array $privileges		права цепочки публикации для наложения резолюции (используется только в параллельном согласовании)
	 * @return array
	 */
	public function do_parallel_resolution($pk, $resolution, $comments, $users, $privileges=array()){
		$verdict=true; $approved_privileges = false;
		// Получение всех прав, которые должны наложить данную резолюцию с пометкой тех прав, что уже наложили данную резолюцию
		$resolution_privileges=$this->get_resolution_privileges($pk, $resolution["WF_RESOLUTION_ID"]);
		$res_privs_array=array_keys(lib::array_group($resolution_privileges, "SYSTEM_NAME"));
		// Если резолюция действительно параллельная, то процессим ее специальным образом
		if(count($resolution_privileges)>1){
			$approved_privileges = array();
			// Для начала бросим исключение, если пользователь не указал ни одного права (довольно формально, но лучше так, чем никак)
			if(!is_array($privileges) || count($privileges)==0){
				$pk_message=($pk[$this->autoinc_name] ? ": (".$this->primary_key->pk_to_string($pk).")" : "");
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_privileges_not_pointed"].$pk_message);
			}
			// Если запись еще не в процессе наложения данной резолюции, то регистрируем резолюцию в записи и очищаем информацию о предыдущей резолюции
			$record=$this->full_object->get_change_record($pk);
			if($record["WF_RESOLUTION_ID"]!=$resolution["WF_RESOLUTION_ID"]){
				$this->full_object->purge_old_resolution($pk);
				db::update_record($this->obj, array("WF_RESOLUTION_ID"=>$resolution["WF_RESOLUTION_ID"]), "", $pk);
			}
			// Собираем переиндексированные списки тех прав, что пользователь хочет наложить и тех, что пользователь может налолжить
			foreach($privileges as $priv){
				$r_privileges[$priv]=$priv;
			}
			
			// Получение всех прав, которые назначены текущему пользователю
			$r_user_privileges = $this -> get_all_user_privileges( $res_privs_array );
			
			// Проходим по списку требуемых резолюций
			foreach($resolution_privileges as $res_priv){
				// Если не присоединился CONTENT_ID, значит эта резолюция еще не была наложена
				if(!$res_priv["CONTENT_ID"]){
					// Проверяем, чтобы пользователь хотел и мог наложить резолюцию и регистрируем в случае успеха пожелание пользователя
					if($r_privileges[$res_priv["AUTH_PRIVILEGE_ID"]] && ($r_user_privileges[$res_priv["AUTH_PRIVILEGE_ID"]] || $this->auth->is_main_admin)){
						db::insert_record("WF_APPROVED", array("CONTENT_ID"=>$pk[$this->autoinc_name], "LANG_ID"=>(int)$pk["LANG_ID"], "TE_OBJECT_ID"=>$this->te_object_id, "AUTH_PRIVILEGE_ID"=>$res_priv["AUTH_PRIVILEGE_ID"], "AUTH_USER_ID"=>$_SESSION["AUTH_USER_ID"], "COMMENTS"=>$comments, "OPERATION_DATE"=>date("YmdHis", time())));
						
						$approved_privileges[] = $res_priv["AUTH_PRIVILEGE_ID"];
						foreach ( $users as $user )
							if ( !lib::is_record_exists( 'WF_NOTIFY', array( 'AUTH_USER_ID' => $user ) + $extended_pk ) )
								db::insert_record( 'WF_NOTIFY', array( 'AUTH_USER_ID' => $user, 'OPERATION_DATE' => date( 'YmdHis', time() ) ) + $extended_pk );
						$approved++;
						
						// Комментарий запоминаем только один раз
						$comments = '';
					}
				}else{
					$approved++;
				}
			}
			// Если кворум не набрался, то резолюция незавершена
			if((int)$approved<$resolution["QUORUM"]){
				$verdict=false;
			}
		}
		return array($verdict, $approved_privileges);
	}

	/**
	 * Удаление информации о резолюции
	 *
	 * Включает очистку соответствующего поля в записи, а также WF_APPROVED и WF_NOTIFY
	 *
	 * @param array $pk				первичный ключ записи
	 */
	public function purge_old_resolution($pk){
		db::update_record($this->obj, array("WF_RESOLUTION_ID"=>0), "", $pk);
		$del_pk=array("CONTENT_ID"=>$pk[$this->autoinc_name], "LANG_ID"=>(int)$pk["LANG_ID"], "TE_OBJECT_ID"=>$this->te_object_id);
		db::delete_record("WF_APPROVED", $del_pk);
		db::delete_record("WF_NOTIFY", $del_pk);
	}
	
	/**
	 * Вычисление потребности в клонировании или удалении строки (или записи целиком) и выполнение этого действия для exec_resolve()
	 *
	 * Метод в целом написан таким образом, что ему не важно - есть декораторы "язык" и "версия" или нет. Но при этом плохого ничего не
	 * произойдет, потому что dup_content() и delete_content(), которых использует этот метод для модификации данных,
	 * обучены осторожному обращению с декораторами и, если что-то нельзя делать при таком наборе декораторов, то не будут это делать.
	 *
	 * @param array $pk				первичный ключ записи
	 * @param array $resolution		описание резолюции, которая определяет клонирование (включая данные о состояниях - WF_STATE1_VERSIONS, WF_STATE2_VERSIONS
	 */
	public function resolve_content($pk, $resolution){
		// Неглавная версия. Если версия неважна, то считаем неглавной рабочую версию
		$not_main_version=($resolution["MAIN_VERSION"]==0 ? 1 : 0);
		// Если конечное состояние без версий - удаляем все
		if($resolution["WF_STATE2_VERSIONS"]=="no_version"){
			$this->inner_object->exec_delete($pk); // На нижележащий объект, потому что у текущего объекта удаление ведет именно в текущий метод
		// Если мы переводим на другой язык
		}elseif($resolution["LANG_ID"]){
			$this->dup_content($pk, $resolution["MAIN_VERSION"], $resolution["LANG_ID"], 1);
			// Если переводить нужно сразу и в рабочую (будет корректно отработано, хотя переводить сразу в рабочую версию неправильно по смыслу)
			if($resolution["WF_STATE2_VERSIONS"]=="two_version"){
				$this->dup_content($pk, $resolution["MAIN_VERSION"], $resolution["LANG_ID"], 0);
			}
		// Если язык остается тем же, то рассматриваем случай изменения количества версий для таблиц с версионностью
		}else{
			// Исходное состояние с одной версией
			if($resolution["WF_STATE1_VERSIONS"]=="test_version"){
				// Если конечное с двумя версиями, то дублируем контент
				if($resolution["WF_STATE2_VERSIONS"]=="two_versions"){
					$this->dup_content($pk, $resolution["MAIN_VERSION"]);
				}
			// Исходное состояние с двумя версиями
			}else{
				// Если конечное тоже с двумя
				if($resolution["WF_STATE2_VERSIONS"]=="two_versions"){
					// Если нет главной версии, то ничего не делаем
					if($resolution["MAIN_VERSION"]==2){
					// Иначе удаляем противоположную версию и дублируем контент
					}else{
						$this->delete_content($pk, $not_main_version);
						$this->dup_content($pk, $resolution["MAIN_VERSION"]);
					}
				// Если конечное с одной версией, то удаляем неглавную версию, а затем уточняем оставшуюся версию (если, например, главная версия - рабочая)
				}else{
					$this->delete_content($pk, $not_main_version); 
					if($this->decorators["version"]){
						db::update_record($this->obj, array("VERSION"=>1), "", $pk);
					}
				}
			}
		}
		// Фиксируем изменение состояния (изменение языка - потому что при переводе состояние меняется у вновь создаваемой записи)
		if($resolution["LANG_ID"] && $this->decorators["lang"]){
			$pk=array("LANG_ID"=>$resolution["LANG_ID"])+$pk;
		}
		db::update_record($this->obj, array("WF_STATE_ID"=>$resolution["LAST_STATE_ID"]), "", $pk);
	}

	/**
	 * Клонирование строки - используется в механизме наложения резолюций
	 *
	 * Нуждается в переопределении, если какие-либо связанные записи тоже должны клонироваться
	 *
	 * Если нет декоратора "версия", то при вызове, который НЕ является переводом на другой язык ничего не делает.
	 * Если нет декоратора "язык", то при вызове, который является переводом на другой язык ничего не делает.
	 *
	 * @param array $pk				первичный ключ записи
	 * @param array $version		версия исходной записи (игнорируется, если декоратора "версия" нет). Может быть равно 2, если исходная версия не важна
	 * @param array $lang_id		новый язык (указывается ТОЛЬКО при переводе)
	 * @param array $new_version	версия новой записи (указывается ТОЛЬКО при переводе)
	 * @todo Надо бы для красоты сделать проверку - нет ли уже такой версии, которую пытаемся вставить
	 */
	public function dup_content($pk, $version, $new_lang_id="", $new_version=""){
		// Ничего не делаем, если запрос не соответствует декораторам
		if((!$this->decorators["version"] && !$new_lang_id) || (!$this->decorators["lang"] && $new_lang_id)){
			return;
		}
		// Если версия имеет значение и ее применение допустимо, то дополняем ею первичный ключ
		if($version!=2 && $this->decorators["version"]){
			$pk+=array("VERSION"=>$version);
		}
		// Вынимаем исходную запись (или записи, если декоратор версия есть, но версия не важна)
		foreach($pk as $key=>$value){
			$where[]="{$key}=:{$key}";
		}
		$record=db::sql_select("SELECT * FROM {$this->obj} WHERE ".join(" AND ", $where), $pk);
		// Уточняем язык версии, если надо
		if($new_lang_id){
			$record[0]["LANG_ID"]=$new_lang_id;
		}
		// Уточняем версию записи, если надо
		if($this->decorators["version"]){
			$record[0]["VERSION"]=($new_lang_id ? $new_version : abs($record[0]["VERSION"]-1));
		}
		lib::inserter($this->obj, array($record[0]));
		
		// Для записей из блочных таблиц очищаем кэш их блоков
		if ( $this -> decorators['block'] && $this -> decorators['version'] && !$record[0]['VERSION'] )
			$this -> full_object -> delete_content_cache( $pk );
	}

	/**
	 * Удаление строки - используется в механизме наложения резолюций
	 *
	 * Нуждается в переопределении, если какие-либо связанные записи тоже должны удаляться
	 *
	 * Если нет декоратора "версия", то ничего не делает.
	 *
	 * @param array $pk				первичный ключ записи
	 * @param array $version		версия исходной записи (игнорируется, если декоратора "версия" нет). НЕ может быть равно 2
	 */
	public function delete_content($pk, $version){
		if($this->decorators["version"]){
			$pk+=array("VERSION"=>$version);
			db::delete_record($this->obj, $pk);
			
			// Для записей из блочных таблиц очищаем кэш их блоков
			if ( $this -> decorators['block'] && !$version )
				$this -> full_object -> delete_content_cache( $pk );
		}
	}

	/**
	 * Рассылка уведомлений указанным администраторам, а также сохраненным в процессе паралелльного согласования
	 */
	public function send_wf_notify( $pk, $resolution, $comments, $users )
	{
		$extended_pk = array( 'CONTENT_ID' => $pk[$this -> autoinc_name], 'LANG_ID' => (int) $pk['LANG_ID'], 'TE_OBJECT_ID' => $this -> te_object_id );
		
		$privilege_obj = object::factory( 'AUTH_PRIVILEGE' );
		list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
			$privilege_obj -> ext_field_selection( 'TITLE', 1 );
		$privilege_obj -> __destruct();
		
		$administrators = db::sql_select( '
			select AUTH_USER.SURNAME, ' . $dec_field . ' as PRIVILEGE_TITLE, WF_APPROVED.COMMENTS
			from WF_APPROVED, AUTH_USER, AUTH_PRIVILEGE '  . $dec_join[0] . '
			where AUTH_USER.AUTH_USER_ID = WF_APPROVED.AUTH_USER_ID and
				WF_APPROVED.AUTH_PRIVILEGE_ID = AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID and
				WF_APPROVED.CONTENT_ID = :CONTENT_ID and
				WF_APPROVED.LANG_ID = :LANG_ID and
				WF_APPROVED.TE_OBJECT_ID = :TE_OBJECT_ID', $extended_pk + $dec_binds );
		
		if ( count( $administrators ) )
		{
			// Если согласование было паралелльным, извлекаем список пользователей из WF_NOTIFY
			$approved_users = db::sql_select( '
				select AUTH_USER_ID from WF_NOTIFY
				where CONTENT_ID = :CONTENT_ID and
					LANG_ID = :LANG_ID and
					TE_OBJECT_ID = :TE_OBJECT_ID', $extended_pk );
			
			$users = array();
			foreach( $approved_users as $user )
				$users[] = $user['AUTH_USER_ID'];
		}
		else
		{
			// В случае последовательного согласования информацию об администраторе берем из параметров метода
			$administrators = array( array( 'SURNAME' => $this -> auth -> user_info['SURNAME'], 'PRIVILEGE_TITLE' => '', 'COMMENTS' => $comments ) );
		}
		
		// Рассылка уведомлений выбранным администраторам
		list( $users_by_resolution, $all_users ) = $this -> full_object -> get_user_by_resolution(
			$this -> decorators['block'] ? $this -> full_object -> get_main_block_id( $pk ) : '' );
		
		if ( is_array( $users_by_resolution[$resolution['WF_RESOLUTION_ID']] ) && is_array( $users ) )
		{
			$users_in = join( ', ', array_intersect( $users, $users_by_resolution[$resolution['WF_RESOLUTION_ID']] ) );
			$emails = db::sql_select( 'SELECT SURNAME, EMAIL FROM AUTH_USER
				WHERE AUTH_USER_ID IN (' . ( $users_in ? $users_in : 0 ) . ')' );
			
			if ( count( $emails ) )
			{
				foreach( $emails as $email )
					if ( $email['EMAIL'] && $this -> field -> check_email( $email['EMAIL'] ) ) {
						$email_tpl = new smarty_ee( metadata::$lang );
								
						$email_tpl -> assign( 'document_title', $this -> full_object -> get_record_title( $pk ) );
						$email_tpl -> assign( 'table_url', params::$params['adm_htdocs_http']['value'] . 'index.php?obj=' . $this -> obj );
						$email_tpl -> assign( 'administrators', $administrators );
						$email_tpl -> assign( 'resolution', $resolution );
					
						$this->send_notify_email($email, $email_tpl);
					}
			}
		}
	}
	
	/**
	* Посылка уведомительного письма
	* @param array $email_data Данные письма
	* @param array $email_tpl Шаблон письма
	* @return boolean
	*/

	private function send_notify_email($email_data, $email_tpl) {
		$email = new vlibMimeMail();
		
		$to_encoding = strtolower( params::$params['encoding']['value'] );
		
		$email -> to( $email_data['EMAIL'], '=?'.$to_encoding.'?B?'.base64_encode( $email_data['SURNAME'] ).'?=' );
		$email -> from( 'admin@' . $_SERVER['SERVER_NAME'], '=?'.$to_encoding.'?B?'.base64_encode( params::$params['project_name']['value'] ).'?=' );
		$email -> subject( '=?'.$to_encoding.'?B?'.base64_encode( metadata::$lang['lang_resolution_reminder'] ).'?=' );
		
		// аттач к email-у 
		$bottom_background_src = 'cid:'.$email -> attach (params::$params["common_htdocs_server"]["value"].'/adm/img/tabs/top/b-bg.gif', 'inline', 'IMAGE/GIF');
		$top_background_src = 'cid:'.$email -> attach (params::$params["common_htdocs_server"]["value"].'/adm/img/tabs/top/t-bg.gif', 'inline', 'IMAGE/GIF');
		$logo_src = 'cid:'.$email -> attach (params::$params["common_htdocs_server"]["value"].'/adm/img/tabs/top/logo.gif', 'inline', 'IMAGE/GIF');
		
		$email_tpl -> assign ('bottom_background_src', $bottom_background_src);
		$email_tpl -> assign ('top_background_src', $top_background_src);
		$email_tpl -> assign ('logo_src', $logo_src);
		
		$message = $email_tpl -> fetch( $this -> tpl_dir . 'te/wf_workflow/wf_notify.tpl' );
		
		$email -> body( strip_tags( $message ), $to_encoding );
		$email -> htmlBody( $message, $to_encoding );
		
		return $email -> send();
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Добавляем ячейку со ссылкой на резолюции
	 *
	 * @see table::get_index_ops()
	 */
	public function get_index_ops($record){
		$ops=$this->inner_object->get_index_ops($record);
		$ops["resolution"]=array("url"=>$this->url->get_url("resolution" ,array("pk"=>$this->primary_key->get_from_record($record))));
		return $ops;
	}

	/**
	 * Если есть декоратор "язык" и собирается карточка добавления, то цепляем js для замены списка резолюций на относящиеся к этому языку
	 *
	 * @see table::html_card()
	 */
	public function html_card($mode, &$request){
		list($title, $body)=$this->inner_object->html_card($mode, $request);
		if($mode=="add"){
			// Выбираем вообще все резолюции из стартовых состояний, а уже js будет подставлять те, что относятся стартовому состоянию нужного языка
			$resolution_obj = object::factory( 'WF_RESOLUTION' );
			list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
				$resolution_obj -> ext_field_selection( 'TITLE', 1 );
			$resolution_obj -> __destruct();
			
			$resolutions=db::replace_field(db::sql_select("
				SELECT WF_RESOLUTION.*, " . $dec_field . " as \"_TITLE\", WF_EDGE_STATE.LANG_ID AS \"_LANG_ID\"
				FROM WF_RESOLUTION " . $dec_join[0] . ", WF_STATE, WF_EDGE_STATE
				WHERE WF_RESOLUTION.FIRST_STATE_ID=WF_STATE.WF_STATE_ID
					AND WF_EDGE_STATE.WF_STATE_ID=WF_STATE.WF_STATE_ID
					AND WF_EDGE_STATE.EDGE_TYPE='new'", $dec_binds), array('TITLE', 'LANG_ID'), array('_TITLE', '_LANG_ID'));
			
			foreach(lib::array_reindex($resolutions, "WF_RESOLUTION_ID") as $resolution){
				$js_res_title.="res_title[{$resolution["WF_RESOLUTION_ID"]}]='".htmlspecialchars($resolution["TITLE"], ENT_QUOTES)."';\n";
			}
			foreach(lib::array_group($resolutions, "LANG_ID") as $lang_id=>$res_by_lang_id){
				$js_res_by_lang_id.="res_by_lang_id[{$lang_id}]=new Array('".join("', '", array_keys(lib::array_group($res_by_lang_id, "WF_RESOLUTION_ID")))."');\n";
			}
			$form_name=html_element::get_form_name();
			$body.=<<<HTM
	<script type="text/javascript">
		var allowed_res=new Array();
		var res_by_lang_id=new Array();
		{$js_res_by_lang_id}
		var res_title=new Array();
		{$js_res_title}
		if(oSelect){
			for(i=0;i<oSelect.options.length;i++){
				if(oSelect.options[i].value){
					allowed_res[oSelect.options[i].value]=oSelect.options[i].value;
				}
			}
		}
		var oLang_select = document.{$form_name}['_form_LANG_ID'];
		if ( oLang_select )
		{
			addListener( oLang_select, 'change', modify_resolution_by_lang );
			modify_resolution_by_lang();
		}
		
		function modify_resolution_by_lang(){
			for(var i=oSelect.options.length-1; i>=0; i--)oSelect.options[i]=null;
			optionName=new Option();
			oSelect.options[0]=optionName;
			
			var oBlockSelect = document.{$form_name}['_form_INF_BLOCK_ID'];
			if ( oBlockSelect && oBlockSelect.selectedIndex == 0 )
				return false;

			var lang_res=res_by_lang_id[oLang_select ? oLang_select.options[oLang_select.selectedIndex].value : 0];
			for(res_id in lang_res){
				if(allowed_res[lang_res[res_id]]){
					optionName=new Option(res_title[lang_res[res_id]], lang_res[res_id]);
					oSelect.options[oSelect.options.length]=optionName;
				}
			}
			modify_users_by_resolution();
			modify_privs_by_resolution();
		}
	</script>	
HTM;
			
			// Если при добавлении записи не указан блок, то список резолюций при закрузке формы
			// оказывается пустым и каждый раз при выборе блока запрашивается с сервера аяксом
			if( $this->decorators['block'] && !$_REQUEST['_f_INF_BLOCK_ID'] )
			{
				$resolutions_service_url = 'index.php?obj=' . $this -> obj . '&action=service&command=get_resolutions_by_block';
				$user_and_privs_service_url = 'index.php?obj=' . $this -> obj . '&action=service&command=get_user_and_privs_by_block';
				
				$body .= <<<HTM
		<script type="text/javascript">
			var oBlockSelect = document.{$form_name}['_form_INF_BLOCK_ID'];
			if ( oBlockSelect )
				addListener( oBlockSelect, 'change', modify_resolutions_by_block );
			
			function modify_resolutions_by_block()
			{
				var iBlockId = oBlockSelect.options[oBlockSelect.selectedIndex].value;
				
				Manager.sendCommand( '{$resolutions_service_url}', { 'block_id': iBlockId },
					window, 'modify_resolutions_by_block_callback', null, true );
				Manager.sendCommand( '{$user_and_privs_service_url}', { 'block_id': iBlockId },
					window, 'modify_resolutions_by_block_callback', null, true );
				
				modify_resolution_by_lang();
			}
			
			function modify_resolutions_by_block_callback( xmlResponse, oParam )
			{
				var xmlText = xmlResponse.documentElement.getElementsByTagName( 'items' );
				if ( xmlText.length )
				{
					try {
						eval( xmlText[0].firstChild.nodeValue );
					} catch ( e ) {
						alert( Dictionary.translate( 'lang_error_from_server' ) ); return false;
					}
				}
			}
		</script>
HTM;
			}
		}
		
		return array($title, $body);
	}
	
	/**
	 * Дополняем список групповых операций, операциями, специфичными для данного декоратора
	 * 
	 * @see table::get_group_operations
	 */
	public function get_group_operations()
	{
		$operations = array();
	
		$operations['group_resolution'] = array( 'title' => metadata::$lang['lang_mass_resolution'], 'no_action' => 'no_change', 'confirm_message' => metadata::$lang['lang_confirm_mass_resolution'] );
		
		return $operations + $this -> inner_object -> get_group_operations();
	}
	
	/**
	 * Выдает список пользователей по резолюциям, а также полный список пользователей, которые задействованы в частных списках
	 *
	 * Принцип отнесения пользователей к резолюциям: собираются те пользователи, которые могут передвинуть запись из 
	 * того состояния, в которое запись перейдет после той резолюции, к котой эти пользователи и будут назначены.
	 * Проще говоря, это те пользователи, которых можно уведомить о том, что запись была передвинута
	 *
	 * @param int $inf_block_id - идентификатор блока, для которого собираются пользователи. Должен быть указан, если таблица оснащена декоратором "блоки"
	 * @return array
	 */
	protected function get_user_by_resolution($inf_block_id=""){
		// Для начала вычисляем все роли, которые могут выполнять любой переход ПОСЛЕ любого перехода в данной таблице (с уточнением для блока, если надо) (детей этих ролей вычислим дальше)
		list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause("", $this->full_object->get_privs("view", ""), "workflow", $this->aot_table);
		$roles_info=db::sql_select("
			SELECT DISTINCT AUTH_ACL.AUTH_SYSTEM_ROLE_ID, WF_RESOLUTION_1.WF_RESOLUTION_ID, AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID, WF_RESOLUTION_1.TITLE AS S_TITLE
			FROM {$auth_tables}, WF_RESOLUTION WF_RESOLUTION_1, WF_RESOLUTION WF_RESOLUTION_2, WF_PRIVILEGE_RESOLUTION, WF_STATE
			WHERE {$auth_clause}
				AND WF_RESOLUTION_2.FIRST_STATE_ID=WF_RESOLUTION_1.LAST_STATE_ID
				AND WF_RESOLUTION_2.WF_RESOLUTION_ID=WF_PRIVILEGE_RESOLUTION.WF_RESOLUTION_ID
				AND WF_PRIVILEGE_RESOLUTION.AUTH_PRIVILEGE_ID=AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID
				AND WF_RESOLUTION_1.FIRST_STATE_ID=WF_STATE.WF_STATE_ID
				AND WF_STATE.WF_WORKFLOW_ID=:workflow_id
				".( ( $this->decorators["block"] && $inf_block_id ) ? " AND INF_BLOCK_ID=:inf_block_id " : "")."
		", $auth_binds+( ( $this->decorators["block"] && $inf_block_id ) ? array("inf_block_id"=>$inf_block_id) : array())+array("workflow_id"=>$this->full_object->get_workflow_id($inf_block_id)));
		$roles_by_resolution=lib::array_group($roles_info, "WF_RESOLUTION_ID");
		// Выбираем все резолюции
		$resolutions=db::sql_select("SELECT * FROM WF_RESOLUTION");
		// Вынимаем пользователей, которые относятся к вышевынутым ролям (здесь уже и к детям этих ролей)
		$roles=array_keys(lib::array_group($roles_info, "AUTH_SYSTEM_ROLE_ID"));
		$users_by_roles=auth::get_users_by_roles($roles);
		// Сливаем всех пользователей, которые относятся к каждой из резолюций, а также собираем полный список пользователей, могущих двигать контент в данной таблице
		$users_by_resolution=array();
		$all_users=array();
		foreach($resolutions as $resolution){
			$users_by_resolution[$resolution["WF_RESOLUTION_ID"]]=array();
			if(is_array($roles_by_resolution[$resolution["WF_RESOLUTION_ID"]])){
				foreach($roles_by_resolution[$resolution["WF_RESOLUTION_ID"]] as $res_info){
					$users_by_resolution[$res_info["WF_RESOLUTION_ID"]]+=$users_by_roles[$res_info["AUTH_SYSTEM_ROLE_ID"]];
				}
			}
			$all_users+=$users_by_resolution[$resolution["WF_RESOLUTION_ID"]];
		}
		return array($users_by_resolution, $all_users);
	}

	/**
	 * Выдает список прав по резолюциям, а также полный список прав, которые задействованы в частных списках
	 *
	 * @param mixed $pk - первичный ключ записи, или пустая строка, если неприменимо (добавление)
	 * @return array
	 * @todo здесь и в do_parallel_resolution() используется одинаковый код в секции "// Получение всех прав, которые назначены текущему пользователю"
	 */
	protected function get_priv_by_resolution($pk="", $inf_block_id=""){
		// Все привилегии, которые могут быть использованы
		$privileges=db::sql_select("
			SELECT AUTH_PRIVILEGE.*, WF_RESOLUTION.WF_RESOLUTION_ID
			FROM AUTH_PRIVILEGE, WF_PRIVILEGE_RESOLUTION, WF_RESOLUTION, WF_STATE
			WHERE AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID=WF_PRIVILEGE_RESOLUTION.AUTH_PRIVILEGE_ID
				AND WF_PRIVILEGE_RESOLUTION.WF_RESOLUTION_ID=WF_RESOLUTION.WF_RESOLUTION_ID
				AND WF_RESOLUTION.FIRST_STATE_ID=WF_STATE.WF_STATE_ID
				AND WF_STATE.WF_WORKFLOW_ID=:workflow_id
		", array("workflow_id"=>$this->full_object->get_workflow_id($inf_block_id)));
		$res_privs_array=array_keys(lib::array_group($privileges, "SYSTEM_NAME"));
		$r_privileges=lib::array_group($privileges, "WF_RESOLUTION_ID");
		// Выбираем все резолюции
		$resolutions=db::sql_select("SELECT * FROM WF_RESOLUTION");
		
		// Получение всех прав, которые назначены текущему пользователю
		$r_user_privileges = $this -> get_all_user_privileges( $res_privs_array );
		
		// Получаем уже назначенные права
		if(is_array($pk)){
			$content=$this->full_object->get_change_record($pk);
			$approved_privs=db::sql_select("SELECT AUTH_PRIVILEGE_ID FROM WF_APPROVED WHERE WF_APPROVED.CONTENT_ID=:content_id AND WF_APPROVED.LANG_ID=:lang_id AND WF_APPROVED.TE_OBJECT_ID=:te_object_id",
				array("content_id"=>(int)$pk[$this->autoinc_name], "lang_id"=>(int)$pk["LANG_ID"], "te_object_id"=>$this->te_object_id));
			$r_approved_privs=lib::array_reindex($approved_privs, "AUTH_PRIVILEGE_ID");
		}
		// Собираем готовые массивы прав
		$privs_by_resolution=array();
		$all_privs=array();
		foreach($resolutions as $resolution){
			$privs_by_resolution[$resolution["WF_RESOLUTION_ID"]]=array();
			if(is_array($r_privileges[$resolution["WF_RESOLUTION_ID"]])){
				foreach($r_privileges[$resolution["WF_RESOLUTION_ID"]] as $res_info){
					// Если некое право еще не назначено и пользователь имеет это право или он главный администратор, то добавляем
					if(
						($resolution["WF_RESOLUTION_ID"]!=$content["WF_RESOLUTION_ID"] || !$r_approved_privs[$res_info["AUTH_PRIVILEGE_ID"]]) &&
						($r_user_privileges[$res_info["AUTH_PRIVILEGE_ID"]] || $this->auth->is_main_admin)
					){
						$privs_by_resolution[$res_info["WF_RESOLUTION_ID"]]+=array($res_info["AUTH_PRIVILEGE_ID"]=>$res_info["AUTH_PRIVILEGE_ID"]);
					}
				}
			}
			$all_privs+=$privs_by_resolution[$resolution["WF_RESOLUTION_ID"]];
		}
		return array($privs_by_resolution, $all_privs);
	}
	
	/**
	 * Возвращает список всех прав, которые назначены текущему пользователю
	 *
	 * @param array $res_privs_array	список допустимых системных прав
	 * @return array
	 */
	protected function get_all_user_privileges( $res_privs_array )
	{
		list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in,$res_privs_array, "workflow", $this->aot_table);
		$user_privileges=db::sql_select("
			SELECT AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID
			FROM {$auth_tables}, TE_OBJECT
			WHERE {$auth_clause}
				AND {$this->aot_table}.TE_OBJECT_ID=TE_OBJECT.TE_OBJECT_ID
				AND TE_OBJECT.SYSTEM_NAME=:te_object_name
		", $auth_binds+array("te_object_name"=>$this->obj));
		return lib::array_reindex($user_privileges, "AUTH_PRIVILEGE_ID");
	}

	/**
	 * Возвращает поля и скрипт для работы механизма наложения резолюций
	 *
	 * @param string $prefix		префикс полей формы
	 * @param array $record			запись, на которую предполагается наложить резолюцию. Если в записи не указано состояние, то состояние считается новым. Если в записи есть язык, то поля и скрипт будут дополенны языком - чтобы отличались в случае нескольких языковых карточек на одной странице
	 * @param boolean $_nonempty_	если истинно, то поле Резолюция обязательно
	 * @param boolean $no_deleted	если истинно, то не выводятся резолюции удаления
	 * @return string
	 */
	protected function get_wf_fields_and_script($prefix, $record, $_nonempty_=false, $no_deleted=false){
		if($record["LANG_ID"]){
			$lang_prefix=$record["LANG_ID"]."_";
		}
		// Вычисление всех администраторов, которые могут передвинуть запись из состояний, в которые запись может быть передвинута, а еще заточку под жава скрипт
		$block_id=($this->decorators["block"] ? ( $_REQUEST['_f_INF_BLOCK_ID'] ? $_REQUEST['_f_INF_BLOCK_ID'] : $this->full_object->get_main_block_id($this->primary_key->get_from_record($record)) ) : "");
		list($users_by_resolution, $all_users)=$this->full_object->get_user_by_resolution($block_id);
		foreach($users_by_resolution as $resolution_id=>$users){
			$js_users="new Array(".($users ? "'".join("', '", $users)."'" : "").")";
			$js_us_by_res_items.="resolution_map{$lang_prefix}[{$resolution_id}]={$js_users};\n";
		}
		$auth_user_obj = object::factory("AUTH_USER");
		$users_info=$auth_user_obj->get_index_records($none, "select2", array());
		$auth_user_obj -> __destruct();
		// Сбор прав, которые могут быть применены к резолюциям (только для параллельного согласования)
		list($privs_by_resolution, $all_privs)=$this->full_object->get_priv_by_resolution($this->primary_key->get_from_record($record), $block_id);
		foreach($privs_by_resolution as $resolution_id=>$privs){
			$js_privs="new Array(".($privs ? "'".join("', '", $privs)."'" : "").")";
			$js_priv_by_res_items.="priv_resolution_map{$lang_prefix}[{$resolution_id}]={$js_privs};\n";
		}
		$auth_priv_obj = object::factory("AUTH_PRIVILEGE");
		$privs_info=$auth_priv_obj->get_index_records($none, "select2", array("by_auth_object"=>"workflow"));
		$auth_priv_obj -> __destruct();
		$state_id=($record["WF_STATE_ID"] ? $record["WF_STATE_ID"] : $this->full_object->get_new_state_id(array('INF_BLOCK_ID'=>$block_id)+(!$this->decorators['lang']?array('LANG_ID'=>0):array())));
		// Селект с резолюциями для формы наложения резолюции
		$fields=array(
			"{$lang_prefix}WF_RESOLUTION_ID"=>array("title"=>metadata::$lang["lang_resolution"], "type"=>"select2", "fk_table"=>"WF_RESOLUTION", "errors"=>(int)$_nonempty_, "list_mode"=>array(
				"AUTH_USER_ID"=>$this->auth->user_info["AUTH_USER_ID"],
				"obj"=>$this->obj,
				"FIRST_STATE_ID"=>$state_id,
				"WF_WORKFLOW_ID"=>$this->full_object->get_workflow_id($block_id),
				"parallel_restriction"=>($record["WF_STATE_ID"] ? 1 : 0),
				"no_version"=>(int)!$this->decorators["version"],
				"no_lang"=>(int)!$this->decorators["lang"],
				"check_langs"=>(int)(boolean)$this->decorators["lang"],
				"autoinc_name"=>$this->autoinc_name,
				"autoinc_value"=>$record[$this->autoinc_name],
				"lang_id"=>(int)$record["LANG_ID"],
				"no_deleted"=>$no_deleted,
			)),
			"{$lang_prefix}COMMENTS"=>array("title"=>metadata::$lang["lang_comments"], "type"=>"textarea"),
		);
		if($this->decorators["block"]){
			$fields["{$lang_prefix}WF_RESOLUTION_ID"]["list_mode"]["inf_block_id"]=$block_id;
		}
		// Чекбоксы с пользователями
		foreach($users_info as $user_info){
			$fields["{$lang_prefix}WF_AUTH_USER_{$user_info["_VALUE"]}"]=array("title"=>$user_info["_TITLE"], "type"=>"checkbox", 'vars' => array( 'display' => 'none' ) );
			$admin_arr[]="{$user_info["_VALUE"]}";
		}
		// Чекбоксы с правами
		foreach($privs_info as $priv_index => $priv_info){
			$fields["{$lang_prefix}WF_PRIVILEGE_{$priv_info["_VALUE"]}"]=array("title"=>$priv_info["_TITLE"], "type"=>"checkbox", 'vars' => array( 'display' => 'none' ) + ( $priv_index == 0 ? array( 'separator' => 1 ) : array() ) );
			$priv_arr[]="{$priv_info["_VALUE"]}";
		}
		
		// Поля формы
		$form_fields=$this->full_object->get_form_fields("add", $prefix, array("{$lang_prefix}WF_RESOLUTION_ID"=>$_REQUEST["_f_WF_RESOLUTION_ID"]), "", $fields);
		
		$pk=$this->primary_key->get_from_record($record);
		
		// Сбор js
		$js_admin_arr=(is_array($admin_arr) ? "'".join("', '", $admin_arr)."'" : "");
		$js_priv_arr=(is_array($priv_arr) ? "'".join("', '", $priv_arr)."'" : "");
		$js=$this->full_object->get_users_by_resolution_js(html_element::get_form_name(), $js_admin_arr, $js_us_by_res_items, $lang_prefix);
		$js.=$this->full_object->get_privs_by_resolution_js(html_element::get_form_name(), $js_priv_arr, $js_priv_by_res_items, $lang_prefix);
		$js.=$this->full_object->get_disable_comment_js(html_element::get_form_name(), $lang_prefix);
		return array($form_fields, $js);
	}

	/**
	 * Возвращает поля для работы механизма группового наложения резолюций
	 *
	 * @param string $prefix		префикс полей формы
	 * @param boolean $_nonempty_	если истинно, то поле Резолюция обязательно
	 * @return array
	 */
	protected function get_group_wf_fields_and_script($prefix, $_nonempty_=false){
		// Ограничиваем вывод списка резолюций выбранной цепочкой побликаций
		$workflow_id = ( metadata::$objects[$this -> obj]['workflow_scope'] == 'block' && !$_REQUEST['_f_INF_BLOCK_ID'] ) ?
			'' : $this -> full_object -> get_workflow_id( $_REQUEST['_f_INF_BLOCK_ID'] );
	
		// Селект с резолюциями для формы наложения резолюции
		$fields=array(
			"WF_RESOLUTION_ID"=>array("title"=>metadata::$lang["lang_resolution"], "type"=>"select2", "fk_table"=>"WF_RESOLUTION", "errors"=>(int)$_nonempty_,
				"list_mode"=>array(	"obj"=>$this->obj, "no_version"=>(int)!$this->decorators["version"], "no_lang"=>1, "WF_WORKFLOW_ID" => $workflow_id, "with_state"=>1 ) ),
			"COMMENTS"=>array("title"=>metadata::$lang["lang_comments"], "type"=>"textarea")
		);
		
		// Сбор прав, которые могут быть применены к резолюциям (только для параллельного согласования)
		list( $privs_by_resolution, $all_privs ) = $this -> full_object -> get_priv_by_resolution();
		foreach( $privs_by_resolution as $resolution_id => $privs )
		{
			$js_privs = "new Array(" . ( $privs ? "'" . join( "', '", $privs ) . "'" : "") . ")";
			$js_priv_by_res_items .= "priv_resolution_map{$lang_prefix}[{$resolution_id}]={$js_privs};\n";
		}
		
		$auth_priv_obj = object::factory("AUTH_PRIVILEGE");
		$privs_info = $auth_priv_obj -> get_index_records( $none, "select2", array( "by_auth_object" => "workflow" ) );
		$auth_priv_obj -> __destruct();
		
		// Чекбоксы с правами
		foreach($privs_info as $priv_index => $priv_info){
			$fields["WF_PRIVILEGE_{$priv_info["_VALUE"]}"]=array("title"=>$priv_info["_TITLE"], "type"=>"checkbox", 'vars' => array( 'display' => 'none' ) + ( $priv_index == 0 ? array( 'separator' => 1 ) : array() ) );
			$priv_arr[]="{$priv_info["_VALUE"]}";
		}
		
		// Поля формы
		$form_fields = $this -> full_object -> get_form_fields( "add", $prefix, "", "", $fields );
		
		// Сбор js
		$js_priv_arr = ( is_array( $priv_arr ) ? "'" . join( "', '", $priv_arr ) . "'" : "" );
		$js = $this -> full_object -> get_privs_by_resolution_js( html_element::get_next_form_name(), $js_priv_arr, $js_priv_by_res_items, "" );
		
		return array( $form_fields, $js );
	}
	
	/**
	 * Сбор js для показа/непоказа администраторов при наложении резолюции. Вспомогателен для get_wf_fields_and_script()
	 *
	 * @param string $form_name				название формы
	 * @param string $js_admin_arr			строка с перечнем идентификаторов администраторов для формирования массива в js
	 * @param string $js_us_by_res_items	строка с js-кодом маппящим администраторов на резолюции
	 * @param string $lang_prefix			префикс к названию функции и полей, если запись содержит языки
	 * @return string
	 */
	protected function get_users_by_resolution_js($form_name, $js_admin_arr, $js_us_by_res_items, $lang_prefix){
		return <<<HTM
	<script type="text/javascript">
		var admin_arr{$lang_prefix}=new Array({$js_admin_arr});
		var resolution_map{$lang_prefix}=new Array();
		{$js_us_by_res_items}
		var oSelect{$lang_prefix} = document.{$form_name}['_form_{$lang_prefix}WF_RESOLUTION_ID'];
		if(oSelect{$lang_prefix}){
			addListener( oSelect{$lang_prefix}, 'change', modify_users_by_resolution{$lang_prefix} )
			modify_users_by_resolution{$lang_prefix}();
		}
		function modify_users_by_resolution{$lang_prefix}(){
			for(admin_id in admin_arr{$lang_prefix}){
				document.getElementById("_form_{$lang_prefix}WF_AUTH_USER_"+admin_arr{$lang_prefix}[admin_id]).style.display = 'none';
			}
			if( res_users = resolution_map{$lang_prefix}[oSelect{$lang_prefix}.options[oSelect{$lang_prefix}.selectedIndex].value] ){
				for(admin_id in res_users){
					document.getElementById("_form_{$lang_prefix}WF_AUTH_USER_"+res_users[admin_id]).style.display = '';
				}
			}
		}
	</script>
HTM;
	}

	/**
	 * Сбор js для показа/непоказа прав при наложении резолюции. Вспомогателен для get_wf_fields_and_script()
	 *
	 * @param string $form_name				название формы
	 * @param string $js_priv_arr			строка с перечнем идентификаторов прав для формирования массива в js
	 * @param string $js_priv_by_res_items	строка с js-кодом маппящим права на резолюции
	 * @param string $lang_prefix			префикс к названию функции и полей, если запись содержит языки
	 * @return string
	 */
	protected function get_privs_by_resolution_js($form_name, $js_priv_arr, $js_priv_by_res_items, $lang_prefix){
		return <<<HTM
	<script type="text/javascript">
		var priv_arr{$lang_prefix}=new Array({$js_priv_arr});
		var priv_resolution_map{$lang_prefix}=new Array();
		{$js_priv_by_res_items}
		var oSelect{$lang_prefix} = document.{$form_name}['_form_{$lang_prefix}WF_RESOLUTION_ID'];
		if(oSelect{$lang_prefix}){
			addListener( oSelect{$lang_prefix}, 'change', modify_privs_by_resolution{$lang_prefix} )
			modify_privs_by_resolution{$lang_prefix}();
		}
		function modify_privs_by_resolution{$lang_prefix}(){
			var separator_show{$lang_prefix} = false;
			for(priv_id in priv_arr{$lang_prefix}){
				// Снимаем чек со всех существующих прав          
				var checkboxes = document.forms['{$form_name}']['_form_{$lang_prefix}WF_PRIVILEGE_'+priv_arr{$lang_prefix}[priv_id]];
				for ( var i = 0; i < checkboxes.length; i++ )
					checkboxes[i].checked = false;
				// Скрываем все строки с правами          
				document.getElementById('_form_{$lang_prefix}WF_PRIVILEGE_'+priv_arr{$lang_prefix}[priv_id]).style.display = 'none';
				// Запоминаем имя строки с разделителем          
				if ( priv_id == 0 )
					var separator_name{$lang_prefix} = "_form_{$lang_prefix}WF_PRIVILEGE_"+priv_arr{$lang_prefix}[priv_id]+"_SEPARATOR";
			}
			if( res_privs = priv_resolution_map{$lang_prefix}[oSelect{$lang_prefix}.options[oSelect{$lang_prefix}.selectedIndex].value] ){
				for(priv_id in res_privs){
					// Ставим чек на нужные права          
					var checkboxes = document.forms['{$form_name}']['_form_{$lang_prefix}WF_PRIVILEGE_'+res_privs[priv_id]];
					for ( var i = 0; i < checkboxes.length; i++ )
						checkboxes[i].checked = true; 
					if ( res_privs.length > 1 ) {
						// Скрываем все строки с нужными правами          
						document.getElementById('_form_{$lang_prefix}WF_PRIVILEGE_'+res_privs[priv_id]).style.display = '';
						separator_show{$lang_prefix} |= true;
					}
				}
			}
			document.getElementById(separator_name{$lang_prefix}).style.display = separator_show{$lang_prefix} ? '' : 'none';
		}
	</script>
HTM;
	}
	
	/**
	 * Сбор js для блокировки поля для ввода комментария при невыбранной резолюции. Вспомогателен для get_wf_fields_and_script()
	 *
	 * @param string $form_name				название формы
	 * @param string $lang_prefix			префикс к названию функции и полей, если запись содержит языки
	 * @return string
	 */
	protected function get_disable_comment_js($form_name, $lang_prefix){
		return <<<HTM
	<script type="text/javascript">
		var oSelect{$lang_prefix} = document.{$form_name}['_form_{$lang_prefix}WF_RESOLUTION_ID'];
		if(oSelect{$lang_prefix}){
			addListener( oSelect{$lang_prefix}, 'change', disable_comment_resolution{$lang_prefix} )
			disable_comment_resolution{$lang_prefix}();
		}
		
		function disable_comment_resolution{$lang_prefix}(){
			document.forms['{$form_name}']['_form_{$lang_prefix}COMMENTS'].disabled = oSelect{$lang_prefix}.selectedIndex == 0;
		}
	</script>
HTM;
	}

	/**
	 * Выбирает пользователей для уведомления о резолюции из сырого массива полей (как правило из $_REQUEST)
	 *
	 * @param array $raw_fields			сырые поля
	 * @param string $prefix		префикс полей формы
	 * @return array
	 */
	protected function get_users_for_resolution($raw_fields, $prefix){
		$users=array();
		foreach($raw_fields as $param=>$value){
			if(preg_match("/^{$prefix}WF_AUTH_USER_(\d+)$/", $param, $matches) && $value==1){
				$users[]=$matches[1];
			}
		}
		return $users;
	}

	/**
	 * Выбирает примененные в параллельном согласовании права из сырого массива полей (как правило из $_REQUEST)
	 *
	 * @param array $raw_fields			сырые поля
	 * @param string $prefix		префикс полей формы
	 * @return array
	 */
	protected function get_privs_for_resolution($raw_fields, $prefix){
		$privs=array();
		foreach($raw_fields as $param=>$value){
			if(preg_match("/^{$prefix}WF_PRIVILEGE_(\d+)$/", $param, $matches) && $value==1){
				$privs[]=$matches[1];
			}
		}
		return $privs;
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Проверка прав при разделении доступа по воркфлоу
	 *
	 * В случае проверки на "resolve" в $pk должен быть подмешан WF_RESOLUTION_ID (на какую резолюцию проверка).
	 * В случае проверки на добавление в конкретный язык в $pk должен быть подмешан LANG_ID.
	 *
	 * @see object::is_permitted_to()
	 * @todo возможно стоит проверять существование резолюции даже для главного администратора, а то типа права есть, а наложить резолюцию невозможно, то же самое касается администраторов сайтов
	 * @todo Не сделаны элементарные права "publish", "unpublish", "undo". Хотя можно и обойтись, так как соответствующие методы группы exec_ работают не напрямую, а через exec_resolve, а ей самой такие элементарные права не нужны. То же самое касается is_permitted_to_mass
	 */
	public function is_permitted_to($ep_type, $pk="", $throw_exception=false){
		if($this->auth->is_main_admin){
			$is_permitted=true;
		}else{
			// Если есть блоки, то отправляем запрос вниз - декоратору "блоки", а он знает, что делать в случае Воркфлоу
			if($this->aot_table=="INF_BLOCK"){
				$is_permitted=$this->inner_object->is_permitted_to($ep_type, $pk, $throw_exception);
			}else{
				list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, $this->full_object->get_privs($ep_type, $pk), "workflow", $this->aot_table);
				$rights=db::sql_select("
					SELECT COUNT(*) AS COUNTER
					FROM {$auth_tables}, TE_OBJECT
					WHERE {$auth_clause}
						AND {$this->aot_table}.TE_OBJECT_ID=TE_OBJECT.TE_OBJECT_ID
						AND TE_OBJECT.SYSTEM_NAME=:te_object_name
					", $auth_binds+array("te_object_name"=>$this->obj));
				$is_permitted=(bool)$rights[0]["COUNTER"];
			}
		}
		if(!$is_permitted && $throw_exception){
			// Название записи в сообщении не выводится по соображениям защиты данных
			$pk_message=($pk[$this->autoinc_name] ? ": (".$this->primary_key->pk_to_string($pk).")" : "");
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_operation_not_permitted_".$ep_type].$pk_message);
		}
		return $is_permitted;
	}

	/**
	 * Массовые права для Воркфлоу
	 *
	 * В отличие от is_permitted_to() здесь при наличии блоков не происходит переадресации в класс table_block - все делается самостоятельно
	 *
	 * @param array $additional_info		отступление от стандартного метода is_permitted_to_mass() - потребовался для того, чтобы передавать WF_RESOLUTION_ID
	 * @see object::is_permitted_to_mass()
	 * @todo Надо бы более осмысленно писать - на какую именно запись нет прав. Нужно будет вывести идентификаторы, а то и главные поля записей, по разнице между массивами $ids и $rights
	 * @todo Может быть надо разнести $additional_info по всем методам is_permitted_to_mass() - для красоты
	 * @todo Возможно стоит проверять существование резолюции даже для главного администратора, а то типа права есть, а наложить резолюцию невозможно
	 * @todo Убедиться, что автовыборка языка и версии правильно работает, если в фильтре что-то выбрано
	 */
	public function is_permitted_to_mass($ep_type, $ids=array(), $throw_exception=false, $additional_info=array()){
		$not_allowed=array(); $allowed=array();
		if($this->auth->is_main_admin){
			// Можно все
		}else{
			// Готовим обычную проверку
			list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, $this->full_object->get_privs("view", ""), "workflow", $this->aot_table);
			$in=(is_array($ids) && count($ids)>0 ? join(", ", $ids) : 0);
			// Для воркфлоу с блоками готовим спецкляузу
			$block_binds=array();
			$site_binds=array();
			if($this->decorators["block"]){
				$block_clause="
					AND INF_BLOCK.TE_OBJECT_ID=:te_object_id
					AND CONTENT_MAP.INF_BLOCK_ID=INF_BLOCK.INF_BLOCK_ID
					AND CONTENT_MAP.IS_MAIN=1
					AND CONTENT_MAP.CONTENT_ID={$this->obj}.{$this->autoinc_name}
				";
				$block_binds=array("te_object_id"=>$this->te_object_id);
				// Кляуза для администраторов сайтов
				if($this->auth->sites_in){
					$site_clause="
						UNION
						SELECT DISTINCT CONTENT_MAP.CONTENT_ID AS {$this->autoinc_name} FROM INF_BLOCK, CONTENT_MAP
						WHERE INF_BLOCK.SITE_ID IN ({$this->auth->sites_in})
							AND INF_BLOCK.TE_OBJECT_ID=:te_object_id2
							AND CONTENT_MAP.INF_BLOCK_ID=INF_BLOCK.INF_BLOCK_ID
							AND CONTENT_MAP.IS_MAIN=1
							AND CONTENT_MAP.CONTENT_ID IN ({$in})
					";
					$site_binds=array("te_object_id2"=>$this->te_object_id);
				}
			}
			// По умолчанию никаких прав не будет
			$clause=" AND 0=1"; $binds=array();
			// Изменять можно, если любая резолюция возможна
			if($ep_type=="change"){
				$clause=""; 
			// Удаление, если есть резолюции переводящие в состояние после удаления
			}elseif($ep_type=="delete"){
				if( metadata::$objects[$this -> obj]['workflow_scope'] == 'block' )
				{
					$clause = " AND WF_RESOLUTION.LAST_STATE_ID IN (
						SELECT WF_STATE.WF_STATE_ID
						FROM WF_STATE, WF_EDGE_STATE
						WHERE WF_STATE.WF_STATE_ID = WF_EDGE_STATE.WF_STATE_ID
							AND WF_STATE.WF_WORKFLOW_ID = INF_BLOCK.WF_WORKFLOW_ID
							AND WF_EDGE_STATE.EDGE_TYPE = 'deleted'
					)";
				}
				else
					$clause = " AND WF_RESOLUTION.LAST_STATE_ID IN ( " . join( ", ", $this -> full_object -> get_deleted_state_id() ) . " )";
			// Наложение резолюции, если есть права на эту резолюцию
			}elseif($ep_type=="resolve"){
				$clause=" AND WF_RESOLUTION.WF_RESOLUTION_ID=:resolution_id";
				$binds=array("resolution_id"=>$additional_info["WF_RESOLUTION_ID"]);
			}
			elseif ($ep_type=="translate") {
				$clause = " AND WF_RESOLUTION.LANG_ID <> 0";
			}
			elseif ($ep_type=="publish") {
				$clause = " AND WF_RESOLUTION.LAST_STATE_ID IN (
					SELECT WF_STATE2.WF_STATE_ID 
					FROM WF_STATE WF_STATE1, WF_STATE WF_STATE2
					WHERE 
						WF_STATE1.WF_STATE_ID=WF_RESOLUTION.FIRST_STATE_ID
							AND
								WF_STATE2.WF_STATE_ID=WF_RESOLUTION.LAST_STATE_ID
							AND (
								(WF_STATE1.VERSIONS='test_version' AND WF_STATE2.VERSIONS='two_versions')
								OR (WF_STATE1.VERSIONS='two_versions' AND WF_STATE1.WF_STATE_ID=WF_STATE2.WF_STATE_ID AND WF_RESOLUTION.MAIN_VERSION=1)
							)
					)
					AND WF_RESOLUTION.LANG_ID=0"; 

			}
			elseif ($ep_type=="unpublish") {
				$clause = " AND WF_RESOLUTION.LAST_STATE_ID IN (
					SELECT WF_STATE2.WF_STATE_ID
					FROM WF_STATE WF_STATE1, WF_STATE WF_STATE2
					WHERE 
						WF_STATE1.WF_STATE_ID=WF_RESOLUTION.FIRST_STATE_ID
							AND 
								WF_STATE2.WF_STATE_ID=WF_RESOLUTION.LAST_STATE_ID
							AND 
								WF_STATE1.VERSIONS='two_versions'
							AND 
								WF_STATE2.VERSIONS='test_version'
					)
					AND WF_RESOLUTION.LANG_ID=0
				";
				
			}
			elseif ($ep_type=="undo") {
				$clause = " AND WF_RESOLUTION.LAST_STATE_ID IN (
					SELECT WF_STATE2.WF_STATE_ID
					FROM WF_STATE WF_STATE1, WF_STATE WF_STATE2
					WHERE
						WF_STATE1.WF_STATE_ID=WF_RESOLUTION.FIRST_STATE_ID
							AND 
								WF_STATE2.WF_STATE_ID=WF_RESOLUTION.LAST_STATE_ID
							AND
								WF_STATE1.WF_STATE_ID=WF_STATE2.WF_STATE_ID
							AND
								WF_STATE1.VERSIONS='two_versions'
				)
				AND	
					WF_RESOLUTION.MAIN_VERSION=0
				";
			}
			
			// Запрос проверки прав на все записи сразу c обычным списковым автовыбором (язык, версия) и сайтами, если применимо
			$rights=db::sql_select("
				SELECT DISTINCT {$this->obj}.{$this->autoinc_name}
				FROM {$auth_tables}, WF_RESOLUTION, WF_PRIVILEGE_RESOLUTION, {$this->obj}".($this->decorators["block"] ? ", CONTENT_MAP" : "")."
				WHERE 
					{$this->obj}.{$this->autoinc_name} IN ({$in})	
					{$block_clause}
					AND {$this->obj}.WF_STATE_ID=WF_RESOLUTION.FIRST_STATE_ID
					AND WF_RESOLUTION.WF_RESOLUTION_ID=WF_PRIVILEGE_RESOLUTION.WF_RESOLUTION_ID
					AND WF_PRIVILEGE_RESOLUTION.AUTH_PRIVILEGE_ID=AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID
					AND {$auth_clause}
					{$clause}
					".$this->full_object->ext_index_query()."
					{$site_clause}
				", $auth_binds+$binds+$block_binds+$site_binds+$this->full_object->ext_index_query_binds());

			$not_allowed=$this->full_object->is_permitted_to_mass_report($ids, $rights, $this->autoinc_name);
			
			// для случая с $throw_exception
			$is_permitted=!(bool)count($not_allowed);
			if(!$is_permitted && $throw_exception){
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_mass_operation_not_permitted_".$ep_type].": ".join(", ", $not_allowed));
			}
		}
		return $not_allowed;
	}

	/**
	 * Возвращает список системных названий ролей (прав) Воркфлоу, которые имеют права осуществлять элементарную операцию $mode
	 *
	 * Список собирается не для текущего пользователя, а вообще, потому что его задача не точное вычисление прав,
	 * а всего лишь собрать такой набор прав, которые позволяют выполнить операцию, если любые из этих прав назначены пользователю.
	 * Метод нужен для того, чтобы было удобно пользоваться auth::get_auth_clause() (который уже делает привязку к пользователю) в случае Воркфлоу
	 *
	 * @param string $mode	режим работы метода - режимы в точности такие как и элементарные права системы
	 * @param array $pk		первичный ключ записи (если это имеет смысл для данного действия). В некоторых случаях (добавление) здесь может передаваться не собственно первичный ключ, а информация о месте, куда добавляется запись - идентификатор родителя, идентификатор блока и другие данные, помогающие определить - есть права или нет. А еще может передаваться идентификатор резолюции для resolve
	 * @return mixed
	 */
	public function get_privs($mode, $pk, $throw_exception=false){
		$binds=array();
		$block_binds = $this->decorators["block"] ? array( "INF_BLOCK_ID" => $pk["INF_BLOCK_ID"] ? $pk["INF_BLOCK_ID"] : $this->full_object->get_main_block_id($pk) ) : array();
		// Просмотр (если есть хотя бы одна роль Воркфлоу)
		if($mode=="view"){
			// Ничего специального не требуется
		// Роли добавления (если есть роль Воркфлоу, которая может выводить из исходного состояния (может быть массивом))
		}elseif($mode=="add"){
			$lang_binds = isset( $pk["LANG_ID"] ) ? array("LANG_ID"=>(int)$pk["LANG_ID"]) : array();
			$new_state=$this->full_object->get_new_state_id($lang_binds + $block_binds, $throw_exception);
			if(is_array($new_state)){
				$query=" AND WF_RESOLUTION.FIRST_STATE_ID IN (".join(", ", $new_state).")";
			}else{
				$query=" AND WF_RESOLUTION.FIRST_STATE_ID=:first_state_id";
				$binds=array("first_state_id"=>$new_state);
			}
		// Роли изменения (если есть роль Воркфлоу, которая может выводить из текущего состояния записи)
		}elseif($mode=="change"){
			$is_obj=true;
		// Роли удаления (если есть роль Воркфлоу, которая может приводить в состояние после удаления)
		}elseif($mode=="delete"){
			$is_obj=true;
			$query=" AND WF_RESOLUTION.LAST_STATE_ID=:last_state_id";
			$binds=array("last_state_id"=>$this->full_object->get_deleted_state_id(array("LANG_ID"=>(int)$pk["LANG_ID"]) + $block_binds, $throw_exception));
		// Роли наложения резолюции (если есть роль Воркфлоу, которая может применять указанную в $pk резолюцию к текущему состоянию записи)
		}elseif($mode=="resolve"){
			//$record=$this->full_object->get_change_record($pk);
			$is_obj=true;
			$query=" AND WF_RESOLUTION.WF_RESOLUTION_ID=:resolution_id";// AND WF_RESOLUTION.FIRST_STATE_ID=:first_state_id";
			$binds=array("resolution_id"=>$pk["WF_RESOLUTION_ID"]/*, "first_state_id"=>$record["WF_STATE_ID"]*/);
		
		}
		// перевод
		elseif ($mode=="translate") {
			$is_obj=true;
			$query = " AND WF_RESOLUTION.LANG_ID <> 0";
		}
		// публикация
		elseif ($mode=="publish") {
			$is_obj = true;
			$query = " AND WF_RESOLUTION.LAST_STATE_ID IN (
					SELECT WF_STATE2.WF_STATE_ID 
					FROM WF_STATE WF_STATE1, WF_STATE WF_STATE2
					WHERE 
						WF_STATE1.WF_STATE_ID=WF_RESOLUTION.FIRST_STATE_ID
							AND
								WF_STATE2.WF_STATE_ID=WF_RESOLUTION.LAST_STATE_ID
							AND (
								(WF_STATE1.VERSIONS='test_version' AND WF_STATE2.VERSIONS='two_versions')
								OR (WF_STATE1.VERSIONS='two_versions' AND WF_STATE1.WF_STATE_ID=WF_STATE2.WF_STATE_ID AND WF_RESOLUTION.MAIN_VERSION=1)
							)
					)
					AND WF_RESOLUTION.LANG_ID=0"; 
			
		}
		// отмена публикации
		elseif ($mode=="unpublish") {
			$is_obj = true;
			$query = " AND WF_RESOLUTION.LAST_STATE_ID IN (
					SELECT WF_STATE2.WF_STATE_ID
					FROM WF_STATE WF_STATE1, WF_STATE WF_STATE2
					WHERE 
						WF_STATE1.WF_STATE_ID=WF_RESOLUTION.FIRST_STATE_ID
							AND 
								WF_STATE2.WF_STATE_ID=WF_RESOLUTION.LAST_STATE_ID
							AND 
								WF_STATE1.VERSIONS='two_versions'
							AND 
								WF_STATE2.VERSIONS='test_version'
					)
					AND WF_RESOLUTION.LANG_ID=0
				";
			
		}
		// undo
		elseif ($mode=="undo") {
			 $is_obj = true;
			 $query = " AND WF_RESOLUTION.LAST_STATE_ID IN (
					SELECT WF_STATE2.WF_STATE_ID
					FROM WF_STATE WF_STATE1, WF_STATE WF_STATE2
					WHERE
						WF_STATE1.WF_STATE_ID=WF_RESOLUTION.FIRST_STATE_ID
							AND 
								WF_STATE2.WF_STATE_ID=WF_RESOLUTION.LAST_STATE_ID
							AND
								WF_STATE1.WF_STATE_ID=WF_STATE2.WF_STATE_ID
							AND
								WF_STATE1.VERSIONS='two_versions'
				)
				AND	
					WF_RESOLUTION.MAIN_VERSION=0
				";
		}
		
		// Для непредусмотренных операций возвращаем пустую строку - то есть ролей подходящих нет		
		
		else{
			return "";
		}
		// Добавляем компоненты для присоединения таблицы объекта
		if($is_obj){
			$from=", {$this->obj}";
			$query.=" AND WF_RESOLUTION.FIRST_STATE_ID={$this->obj}.WF_STATE_ID AND ".$this->primary_key->where_clause();
			$binds+=$this->primary_key->bind_array($pk);
			
			if ( $this -> decorators['block'] )
			{
				$from.=", CONTENT_MAP";
				$query.=" AND CONTENT_MAP.CONTENT_ID = {$this->obj}.{$this->autoinc_name}
					AND CONTENT_MAP.INF_BLOCK_ID = :INF_BLOCK_ID
					AND CONTENT_MAP.IS_MAIN = 1";
				$binds+=$block_binds;
			}
		}
		// Формируем сам запрос
		$wf_roles=db::sql_select("
			SELECT DISTINCT AUTH_PRIVILEGE.SYSTEM_NAME
			FROM AUTH_PRIVILEGE, AUTH_OBJECT_TYPE, WF_RESOLUTION, WF_PRIVILEGE_RESOLUTION {$from}
			WHERE AUTH_OBJECT_TYPE.SYSTEM_NAME=:system_name
				AND AUTH_OBJECT_TYPE.AUTH_OBJECT_TYPE_ID=AUTH_PRIVILEGE.AUTH_OBJECT_TYPE_ID
				AND AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID=WF_PRIVILEGE_RESOLUTION.AUTH_PRIVILEGE_ID
				AND WF_RESOLUTION.WF_RESOLUTION_ID=WF_PRIVILEGE_RESOLUTION.WF_RESOLUTION_ID
				{$query}
		", array("system_name"=>"workflow")+$binds);
		// Делаем из резалт сета простой список значений или пустую строку, если значений нет (пустая строка является валидным параметром для auth::get_auth_clause())
		return (count($wf_roles)>0 ? array_keys(lib::array_group($wf_roles, "SYSTEM_NAME")) : "");
	}

	/**
	 * Возвращает компоненты авторизационного запроса для автоматической выборки подстановочных резолюций в exec_delete, exec_publish, exec_unpublish, exec_undo
	 *
	 * Возвращаемые значения подобны результату работы auth::get_auth_clause(), но имеют немного другой формат для удобства использования
	 *
	 * @param array $pk				первичный ключ записи
	 * @param array $ep_type		системное название элементарной операции
	 * @return array
	 * @see table::exec_publish()
	 */
	public function get_auth_clause_for_resolution($ep_type, $pk){
		// Главный администратор или администратор сайта будет выбирать любую подходящую резолюцию, прочие администраторы - в соответствии со своими праваи
		if($this->auth->is_main_admin/* || ($this->decorators["block"] && auth::is_site_admin_for($this->obj, $pk, true))*/){
			$auth_tables="";
			$auth_clause="1=1";
			$auth_binds=array();
		// Выбор для обычного пользователя
		}else{
			list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, $this->full_object->get_privs($ep_type, $pk), "workflow", $this->aot_table);
			$auth_tables.=", WF_PRIVILEGE_RESOLUTION, ";
			$auth_clause.="	AND WF_RESOLUTION.WF_RESOLUTION_ID=WF_PRIVILEGE_RESOLUTION.WF_RESOLUTION_ID AND WF_PRIVILEGE_RESOLUTION.AUTH_PRIVILEGE_ID=AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID";
		}
		return array($auth_tables, $auth_clause, $auth_binds);
	}

	/**
	 * Возвращает идентификатор цепочки публикации для данного объекта
	 *
	 * @param array $inf_block_id	идентификатор блока
	 * @return mixed
	 */
	public function get_workflow_id($inf_block_id=""){
		if ( metadata::$objects[$this -> obj]['workflow_scope'] == 'block' && $inf_block_id )
			$workflow_by_object=db::sql_select("SELECT WF_WORKFLOW_ID FROM INF_BLOCK WHERE INF_BLOCK_ID=:inf_block_id", array("inf_block_id"=>$inf_block_id));
		else
			$workflow_by_object=db::sql_select("SELECT WF_WORKFLOW_ID FROM TE_OBJECT WHERE TE_OBJECT_ID=:te_object_id", array("te_object_id"=>$this->te_object_id));
		return $workflow_by_object[0]["WF_WORKFLOW_ID"];
	}

	/**
	 * Возвращает идентификатор нового состояния для записи
	 *
	 * Логика работы следующая: Если $params["LANG_ID"] указан, то возвращается идентификатор начального состояния для этого языка (включая LANG_ID=0), или будет брошено исключение, если такое состояние не будет обнаружено
	 * Если $params["LANG_ID"] не указан, то возвращается список всех начальных состояний, индексированный по языкам
	 *
	 * @param array $params		параметры поиска состояния. Могут включать LANG_ID
	 * @return mixed
	 */
	public function get_new_state_id($params=array(), $throw_exception=false){
		return $this->full_object->get_edge_state_id($params, "new", $throw_exception);
	}

	/**
	 * Возвращает идентификатор состояния после удаления
	 *
	 * Логика работы такая же как у get_new_state_id()
	 *
	 * @param array $params		параметры поиска состояния. Могут включать LANG_ID
	 * @return mixed
	 */
	 
	public function get_deleted_state_id($params=array(), $throw_exception=false){
		return $this->full_object->get_edge_state_id($params, "deleted", $throw_exception);
	}

	/**
	 * Возвращает идентификатор граничного состояния
	 *
	 * Логика работы такая же как у get_new_state_id() (инкапсулирует общий код для get_new_state_id() и get_deleted_state_id())
	 *
	 * @param array $params		параметры поиска состояния. Могут включать LANG_ID
	 * @return mixed
	 */
	public function get_edge_state_id($params, $edge_type, $throw_exception=false){
		// Определяемся с необходимостью ограничения по языкам
		if(isset($params["LANG_ID"])){
			$lang_binds=array("lang_id"=>$params["LANG_ID"]);
			$lang_clause="AND WF_EDGE_STATE.LANG_ID=:lang_id";
		}else{
			$lang_binds=array();
			$lang_clause="";
		}
		
		// Определяемся с необходимостью ограничения по цепочке публикаций
		// Ограничения не ставятся, если ЦП определяется блоком, но блок в параметрах не передан
		if ( !metadata::$objects[$this -> obj]['workflow_scope'] == 'block' || $params['INF_BLOCK_ID'] )
		{
			$workflow_binds = array( 'workflow_id' => $this -> full_object -> get_workflow_id( $params['INF_BLOCK_ID'] ) );
			$workflow_clause = 'AND WF_STATE.WF_WORKFLOW_ID=:workflow_id';
		}
		else
		{
			$workflow_binds=array();
			$workflow_clause="";
		}
		
		// Выбираем начальное состояние или состояния
		$state=db::sql_select("
			SELECT WF_STATE.WF_STATE_ID, WF_EDGE_STATE.LANG_ID
			FROM WF_STATE, WF_EDGE_STATE
			WHERE WF_STATE.WF_STATE_ID=WF_EDGE_STATE.WF_STATE_ID
				{$workflow_clause}
				{$lang_clause}
				AND WF_EDGE_STATE.EDGE_TYPE=:edge_type
		", array("edge_type"=>$edge_type)+$workflow_binds+$lang_binds);
		// Бросаем исключение или не бросаем и возвращаем результат
		if(!$state[0]["WF_STATE_ID"] && isset($params["LANG_ID"]) && $throw_exception){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_cannot_find_{$edge_type}_state"]);
		}
		if(isset($params["LANG_ID"])){
			return $state[0]["WF_STATE_ID"];
		}else{
			foreach($state as $s){
				$return_array[$s["LANG_ID"]]=$s["WF_STATE_ID"];
			}
			return $return_array;
		}
	}
	
	/**
	* Собирает информацию для журнала
	* @param array $fields	Параметры
	* @return array
	*/
	
	public function get_additional_info_for_log($fields) {
		if ($fields['resolutions']) {
			$fields['log_params']['log_info']['resolutions']="{$fields['resolutions']['TITLE']} ('{$fields['resolutions']['WF_STATE1_TITLE']}' -> '{$fields['resolutions']['WF_STATE2_TITLE']}')";
			
			$fields['log_params']['version'] = $this->get_version_for_log($fields['resolutions']);
			
			if ($fields['resolutions']['LANG_ID'])
				$fields['log_params']['lang_id'] = $fields['resolutions']['LANG_ID'];
		}
		
		$fields['log_params']['log_info']['resolution_comments']=$fields['resolution_comments'];
		$fields['log_params']['log_info']['resolution_privileges']=$fields['resolution_privileges'];
		
		$fields['log_params']['log_info']['object_name']=$fields['object_name'];
		return $this->inner_object->get_additional_info_for_log($fields);
	}
	
	/**
	* Выдает номер версии, который необходимо записать в лог при переводе записи из одного состояния в другое
	* @param array $r Данные о резолюции, полученные из запроса в exec_resolve
	* @return int Версия
	*/
	private function get_version_for_log($r) {
		if(
			($r['WF_STATE1_VERSIONS']=="test_version" && $r['WF_STATE2_VERSIONS']=="two_versions") || // Публикация
			($r['WF_STATE1_VERSIONS']=="two_versions" && $r['WF_STATE2_VERSIONS']=="test_version" && !$r["LANG_ID"]) || // Снятие с публикации
			($r['WF_STATE1_VERSIONS']=="two_versions" && $r['WF_STATE2_VERSIONS']=="two_versions" && $r["MAIN_VERSION"]==1) || // Публикация изменений
			($r['WF_STATE1_VERSIONS']=="two_versions" && $r['WF_STATE2_VERSIONS']=="no_version") // Удаление из опубликованного состояния
		){
			return 0;
		}
		return 1;
	}	
	
	/**
	 * Обработчик команды 'get_resolutionss_by_block'. Возвращает список резолюций по блоку
	 * 
	 * @param string $mark - уникальный идентификатор команды
	 */
	public function command_get_resolutions_by_block( $mark = '' )
	{
		$list_mode = array(
			"obj" => $this -> obj,
			"AUTH_USER_ID" => $this -> auth -> user_info["AUTH_USER_ID"],
			"FIRST_STATE_ID" => $this -> full_object -> get_new_state_id( array( 'INF_BLOCK_ID' => $_REQUEST['block_id'] ) + ( !$this -> decorators['lang'] ? array( 'LANG_ID' => 0 ) : array() ) ),
			"no_version" => (int)!$this->decorators["version"],
			"no_lang" => (int)!$this->decorators["lang"],
			"inf_block_id" => $_REQUEST['block_id'],
			"no_deleted" => true );
		
		$resolution_obj = object::factory("WF_RESOLUTION");
		$resolutions = $resolution_obj -> get_index_records($none, "select2", $list_mode );
		$resolution_obj -> __destruct();
		
		$js_allowed_res = array( 'allowed_res = new Array()' );
		foreach( $resolutions as $resolution )
			$js_allowed_res[] = "allowed_res[{$resolution['WF_RESOLUTION_ID']}] = {$resolution['WF_RESOLUTION_ID']}";
		
		// Формируем ответ сервера
		return html_element::xml_response( '<items><![CDATA[' . join( '; ', $js_allowed_res ) . ']]></items>', $mark );
	}
	
	/**
	 * Обработчик команды 'get_user_and_privs_by_block'. Возвращает список пользователей по блоку
	 * 
	 * @param string $mark - уникальный идентификатор команды
	 */
	public function command_get_user_and_privs_by_block( $mark = '' )
	{
		list( $users_by_resolution, $all_users ) =
			$this -> full_object -> get_user_by_resolution( $_REQUEST['block_id'] );
		
		list( $privs_by_resolution, $all_privs ) =
			$this -> full_object -> get_priv_by_resolution( '', $_REQUEST['block_id'] );
		
		$js_us_by_res_items = array( 'resolution_map = new Array()' );
		foreach( $users_by_resolution as $resolution_id => $users )
			$js_us_by_res_items[] = "resolution_map[{$resolution_id}] = [ " . ( $users ? "'" . join( "', '", $users ) . "'" : "" ) . " ]";
		
		$js_priv_by_res_items = array( 'priv_resolution_map = new Array()' );
		foreach( $privs_by_resolution as $resolution_id => $privs )
			$js_priv_by_res_items[] = "priv_resolution_map[{$resolution_id}] = [ " . ( $privs ? "'" . join( "', '", $privs ) . "'" : "" ) . " ]";
		
		$response = join( '; ', $js_us_by_res_items ) . '; ' . join( '; ', $js_priv_by_res_items );
		
		// Формируем ответ сервера
		return html_element::xml_response( '<items><![CDATA[' . $response . ']]></items>', $mark );
	}
	
	/**
	* Удаляем из информации для экспорта поля WF_STATE_ID и TIMESTAMP
	*/
	public function get_fields_for_export() {
		$fields = $this->inner_object->get_fields_for_export();
		unset($fields['WF_RESOLUTION_ID']);
		unset($fields['WF_STATE_ID']);
		unset($fields['TIMESTAMP']);
		return $fields;
	}		
	
	/**
	* Добавляем к блокирующим акциям resolution
	* @see table::get_lock_actions
	* @return array
	*/
	
	public function get_lock_actions () {
		$actions = $this->inner_object->get_lock_actions();
		$actions[] = 'resolution';
		return $actions;
	}
	
	/**
	* Добавляем к изменяющим акциям resolved
	* @see table::get_commit_lock_actions
	* @return array
	*/
	
	public function get_commit_lock_actions() {
		$actions = $this->inner_object->get_commit_lock_actions();
		$actions[] = 'resolved';
		return $actions;
	}
	
	/**
	 * Метод возвращает идентификатор резолюции публикации
	 *
	 * Срабатывает в 2 случаях:<br>
	 * 1. Запись находится в состоянии без рабочей версии и есть резолюция перевести ее в состояние с тестовой и рабочей версией одновременно<br>
	 * 2. Запись находится в состоянии с рабочей и тестовой версией и есть резолюция перевести ее в то же самое состояние с главной версией ТЕСТОВОЙ
	 *
	 * В этом методе и в exec_unpublish, exec_undo используется право "change" для сбора кляузы выборки резолюции, что оправдано, так как
	 * это позволит выбрать любые роли любых пользователей, которые могут подвинуть данную запись, а уже в самом запросе они будут уточнены до нужной резолюции нужного пользователя.
	 */
	public function get_publish_resolution( $pk )
	{
		$record=$this->full_object->get_change_record($pk, true);
		list($auth_tables, $auth_clause, $auth_binds)=$this->full_object->get_auth_clause_for_resolution("change", $pk);
		
		$resolution=db::sql_select("
			SELECT WF_RESOLUTION.*
			FROM {$auth_tables} WF_RESOLUTION, WF_STATE WF_STATE1, WF_STATE WF_STATE2
			WHERE {$auth_clause}
				AND WF_STATE1.WF_STATE_ID=WF_RESOLUTION.FIRST_STATE_ID
				AND WF_STATE2.WF_STATE_ID=WF_RESOLUTION.LAST_STATE_ID
				AND (
						(WF_STATE1.VERSIONS='test_version' AND WF_STATE2.VERSIONS='two_versions')
					OR	(WF_STATE1.VERSIONS='two_versions' AND WF_STATE1.WF_STATE_ID=WF_STATE2.WF_STATE_ID AND WF_RESOLUTION.MAIN_VERSION=1)
				)
				AND WF_RESOLUTION.FIRST_STATE_ID=:first_state_id
				AND WF_RESOLUTION.LANG_ID=0
		", array("first_state_id"=>$record["WF_STATE_ID"])+$auth_binds);
		
		if(!$resolution[0]["WF_RESOLUTION_ID"]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_resolution_not_found"].": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
		}
		
		return $resolution[0]["WF_RESOLUTION_ID"];
	}
	
	/**
	 * Метод возвращает идентификатор резолюции снятия с публикации
	 *
	 * Срабатывает если:<br>
	 * Запись находится в состоянии с рабочей и тестовой версией и есть резолюция перевести ее в состояние с одной тестовой версией
	 */
	public function get_unpublish_resolution( $pk )
	{
		$record=$this->full_object->get_change_record($pk, true);
		list($auth_tables, $auth_clause, $auth_binds)=$this->full_object->get_auth_clause_for_resolution("change", $pk);
		
		$resolution=db::sql_select("
			SELECT WF_RESOLUTION.*
			FROM {$auth_tables} WF_RESOLUTION, WF_STATE WF_STATE1, WF_STATE WF_STATE2
			WHERE {$auth_clause}
				AND WF_STATE1.WF_STATE_ID=WF_RESOLUTION.FIRST_STATE_ID
				AND WF_STATE2.WF_STATE_ID=WF_RESOLUTION.LAST_STATE_ID
				AND WF_STATE1.VERSIONS='two_versions' AND WF_STATE2.VERSIONS='test_version'
				AND WF_RESOLUTION.FIRST_STATE_ID=:first_state_id
				AND WF_RESOLUTION.LANG_ID=0
		", array("first_state_id"=>$record["WF_STATE_ID"])+$auth_binds);
		
		if(!$resolution[0]["WF_RESOLUTION_ID"]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_resolution_not_found"].": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
		}
		
		return $resolution[0]["WF_RESOLUTION_ID"];
	}
	
	/**
	 * Метод возвращает идентификатор резолюции отмены изменений
	 *
	 * Срабатывает если:<br>
	 * Запись находится в состоянии с рабочей и тестовой версией и есть резолюция перевести ее в то же самое состояние с главной версией РАБОЧЕЙ
	 */
	public function get_undo_resolution( $pk )
	{
		$record=$this->full_object->get_change_record($pk, true);
		list($auth_tables, $auth_clause, $auth_binds)=$this->full_object->get_auth_clause_for_resolution("change", $pk);
		
		$resolution=db::sql_select("
			SELECT WF_RESOLUTION.*
			FROM {$auth_tables} WF_RESOLUTION, WF_STATE WF_STATE1, WF_STATE WF_STATE2
			WHERE {$auth_clause}
				AND WF_STATE1.WF_STATE_ID=WF_RESOLUTION.FIRST_STATE_ID
				AND WF_STATE2.WF_STATE_ID=WF_RESOLUTION.LAST_STATE_ID
				AND WF_STATE1.VERSIONS='two_versions' AND WF_STATE1.WF_STATE_ID=WF_STATE2.WF_STATE_ID AND WF_RESOLUTION.MAIN_VERSION=0
				AND WF_RESOLUTION.FIRST_STATE_ID=:first_state_id
				AND WF_RESOLUTION.LANG_ID=0
		", array("first_state_id"=>$record["WF_STATE_ID"])+$auth_binds);
		
		if(!$resolution[0]["WF_RESOLUTION_ID"]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_resolution_not_found"].": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
		}
		
		return $resolution[0]["WF_RESOLUTION_ID"];
	}
	
	/**
	 * Метод возвращает идентификатор резолюции удаления
	 *
	 * @param array $pk					первичный ключ записи
	 */
	public function get_delete_resolution( $pk )
	{
		$record=$this->full_object->get_change_record($pk, true);
		$block_id=($this->decorators["block"] ? $this->full_object->get_main_block_id($pk) : "");
		list($auth_tables, $auth_clause, $auth_binds)=$this->full_object->get_auth_clause_for_resolution("delete", $pk);
		
		// В этом запросе вынимаем первую попавшуюся резолюцию удаления, на которую у пользователя есть права. По воркфлоу ограничения не делается, потому что это выполняется в get_deleted_state_id()
		$resolution=db::sql_select("
			SELECT WF_RESOLUTION.*
			FROM {$auth_tables} WF_RESOLUTION, WF_STATE WF_STATE2
			WHERE {$auth_clause}
				AND WF_STATE2.WF_STATE_ID=:deleted_state_id
				AND WF_STATE2.WF_STATE_ID=WF_RESOLUTION.LAST_STATE_ID
				AND WF_RESOLUTION.FIRST_STATE_ID=:first_state_id
		", array("deleted_state_id"=>$this->full_object->get_deleted_state_id(array("LANG_ID"=>(int)$pk["LANG_ID"], "INF_BLOCK_ID"=>$block_id)), "first_state_id"=>$record["WF_STATE_ID"])+$auth_binds);
		
		if(!$resolution[0]["WF_RESOLUTION_ID"]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_resolution_not_found"].": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
		}
		
		return $resolution[0]["WF_RESOLUTION_ID"];
	}
	
	/**
	 * Метод возвращает идентификатор резолюции перевода
	 *
	 * @param array $pk					первичный ключ записи
	 * @param integer $lang_id			идентификатор языка, на который необходимо перевести запись
	 */

	public function get_translate_resolution ($pk, $lang_id) {

		$lang_id=intval($lang_id);
		$record=$this->full_object->get_change_record($pk, true);
		
		// Проверка на существование языка в системе
		if(!$this->r_all_langs[$lang_id]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_such_language"].": ({$lang_id})");
		}
		
		// ищем резолюцию, которой  можно перевести
		$resolution=db::sql_select('
			SELECT 
				WR.WF_RESOLUTION_ID 
			FROM 
				WF_RESOLUTION WR 
					INNER JOIN
						WF_STATE WS 
					ON (WR.LAST_STATE_ID=WS.WF_STATE_ID) 
			WHERE 
				WR.FIRST_STATE_ID=:state_id AND WR.LANG_ID=:lang_id AND WS.VERSIONS IN ("one_version", "test_version")', 
			array('state_id'=>$record['WF_STATE_ID'], 'lang_id'=>$lang_id)
		);
		
		if(!$resolution[0]["WF_RESOLUTION_ID"]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_resolution_not_found"].": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
		}
		
		return $resolution[0]["WF_RESOLUTION_ID"];
	}
	
	/**
	 * Метод возвращает список привилегий, необходимых для наложения данной резолюции
	 * 
	 * @param array $pk					первичный ключ записи
	 * @param integer $resolution_id	идентификатор резолюции
	 * @param boolean $only_remained	возвращать только неналоженные привилегии
	 * @return array
	 */
	public function get_resolution_privileges( $pk, $resolution_id, $only_remained = false )
	{
		$binds = array("CONTENT_ID"=>$pk[$this->autoinc_name], "LANG_ID"=>(int)$pk["LANG_ID"],
			"TE_OBJECT_ID"=>$this->te_object_id, "resolution_id"=>$resolution_id);
		
		return db::sql_select("
			SELECT AUTH_PRIVILEGE.*, WF_APPROVED.CONTENT_ID
			FROM WF_PRIVILEGE_RESOLUTION, AUTH_PRIVILEGE
			LEFT JOIN WF_APPROVED ON
					WF_APPROVED.TE_OBJECT_ID=:TE_OBJECT_ID
				AND WF_APPROVED.CONTENT_ID=:CONTENT_ID
				AND WF_APPROVED.LANG_ID=:LANG_ID
				AND WF_APPROVED.AUTH_PRIVILEGE_ID=AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID
			WHERE AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID=WF_PRIVILEGE_RESOLUTION.AUTH_PRIVILEGE_ID
				AND WF_PRIVILEGE_RESOLUTION.WF_RESOLUTION_ID=:resolution_id
				" . ( $only_remained ? "AND WF_APPROVED.CONTENT_ID IS NULL" : "" ), $binds);
	}	
}
?>