<?php
include_once(params::$params["adm_data_server"]["value"]."class/te/table/inf_block/inf_block.php");

/**
 * Класс декоратор таблиц "Записи в блоках"
 *
 * Требует наличия в системе класса inf_block, который относится к ядру и должен быть в системе всегда
 *
 * @package		RBC_Contents_5_0
 * @subpackage core
 * @copyright	Copyright (c) 2006 RBC SOFT
 * @todo Продумать, как блоки должны жить отдельно от CMS. В частности, что делать с модулями - без них блоки не живут сейчас
 * @todo Если мы собираемся позволять менять у записей главные блоки, то что будет с иерархической таблицей, если у родительской записи поменять главный блок, а у детей нет?
 */

class table_block extends decorator
{
	/**
	 * Перед добавлением записи проверяем валидность блока, а после добавления записи регистрируем ее в CONTENT_MAP
	 *
	 * Проверяем, есть ли блок и правильный ли у него модуль, и есть ли права на него. Если нет, то бросаем исключение.
	 * Идентификатор блока должен быть передан в $raw_fields как обычное поле.
	 * Для иерархической таблицы проверяется, чтобы родитель записи (если это не корень) имел бы тот же самый главный блок, что и добавляемая запись.
	 *
	 * @see table::exec_add()
	 */
	public function exec_add($raw_fields, $prefix){
		// Проверка на права и существование блока
		$this->full_object->is_permitted_to("add", array("INF_BLOCK_ID"=>$raw_fields[$prefix."INF_BLOCK_ID"]), true);
		inf_block::is_valid_block($raw_fields[$prefix."INF_BLOCK_ID"], $this->obj, true);
		// Проверка на совпадение блока записи и блока родителя
		$parent_id=$raw_fields[$prefix.metadata::$objects[$this->obj]["parent_field"]];
		if($this->parent_id!=="" && $parent_id){
			$parent_main_block_id=$this->full_object->get_main_block_id(array($this->autoinc_name=>$parent_id));
			if($parent_main_block_id!=$raw_fields[$prefix."INF_BLOCK_ID"]){
				throw new Exception($this->te_object_name." (".metadata::$lang["lang_adding"]."): ".metadata::$lang["lang_main_block_is_not_equal"]." - {$raw_fields[$prefix."INF_BLOCK_ID"]}, {$parent_main_block_id}");
			}
		}
		// Собственно добавление и регистрация в CONTENT_MAP
		$inserted_id=$this->inner_object->exec_add($raw_fields, $prefix);
		db::insert_record("CONTENT_MAP", array("INF_BLOCK_ID"=>$raw_fields[$prefix."INF_BLOCK_ID"], "CONTENT_ID"=>$inserted_id, "IS_MAIN"=>1));
		
		$record = db::sql_select("SELECT * FROM {$this->obj} WHERE {$this->autoinc_name}=:{$this->autoinc_name}", array($this->autoinc_name=>$inserted_id));
		$this->full_object->log_register('add_block', $record[0]);
		
		return $inserted_id;
	}
	
	/**
	 * Проверяем, чтобы у родителя был тот же самый главный блок, что и у изменяемой записи
	 *
	 * @see table::exec_change()
	 */
	 
	public function exec_change($raw_fields, $prefix, $pk){
		$record_main_block_id=$this->full_object->get_main_block_id($pk);
		$parent_id=$raw_fields[$prefix.metadata::$objects[$this->obj]["parent_field"]];
		if($this->parent_id!=="" && $parent_id){
			$parent_main_block_id=$this->full_object->get_main_block_id(array($this->autoinc_name=>$parent_id));
			if($parent_main_block_id!=$record_main_block_id){
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_main_block_is_not_equal"]." - {$record_main_block_id}, {$parent_main_block_id}: \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
			}
		}
		$this->inner_object->exec_change(array_merge(array('INF_BLOCK_ID'=>$record_main_block_id), $raw_fields), $prefix, $pk);
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	public function exec_delete($pk, $partial=false) {
		$this->get_needed_fields_for_log($pk);
		$this->inner_object->exec_delete($pk, $partial);
	}
	
	/**
	 * После удаления записи очищаем CONTENT_MAP (в случае полного удаления)
	 *
	 * @see table::ext_finalize_delete()
	 */
	public function ext_finalize_delete($pk, $partial=false){
		$this->inner_object->ext_finalize_delete($pk, $partial);
		$this->full_object->delete_content_cache($pk);
		if(!$partial){
			db::sql_query("
				DELETE FROM CONTENT_MAP
				WHERE CONTENT_MAP.CONTENT_ID=:content_id
					AND CONTENT_MAP.INF_BLOCK_ID IN (SELECT INF_BLOCK.INF_BLOCK_ID FROM INF_BLOCK WHERE INF_BLOCK.TE_OBJECT_ID=:te_object_id)
			", array("content_id"=>$pk[$this->autoinc_name], "te_object_id"=>$this->te_object_id));
		}
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Действие - список записей
	 *
	 * @see table::action_index()
	 */
	public function action_index()
	{
		if ( metadata::$objects[$this->obj]['only_one'] && $_REQUEST['_f_INF_BLOCK_ID'] )
			if ( $this -> full_object -> get_index_counter( array( '_f_INF_BLOCK_ID' => $_REQUEST['_f_INF_BLOCK_ID'] ), 'index', '' ) )
				metadata::$objects[$this->obj]['no_add'] = 1;
		
		$this -> inner_object -> action_index();
	}

	/**
	 * Действие - карточка добавления
	 *
	 * Если не указан блок, то выводим поле с выбором блоков с помощью модификации метаданных - делаем виртуальное поле INF_BLOCK_ID добавляемым
	 *
	 * @metadatamod меняем описание поля INF_BLOCK_ID, для иерархической таблицы фиксируем выбор родителя указанным блоком
	 * @see table::action_add()
	 */
	public function action_add()
	{
		$this->set_inf_block_field();
		$this->inner_object->action_add();
	}
	
	public function action_copy()
	{
		$this->set_inf_block_field();
		$this->inner_object->action_copy();
	}	
	
	

	/**
	 * Действие - карточка изменения
	 *
	 * Разрешаем изменять родителя записи только в пределах блока
	 *
	 * @metadatamod для иерархической таблицы фиксируем выбор родителя главным блоком записи
	 * @see table::action_change()
	 */
	public function action_change(){
		if($this->parent_id!==""){
			$pk=$this->primary_key->get_from_request();
			$main_block_id=$this->full_object->get_main_block_id($pk, true);
			metadata::$objects[$this->obj]["fields"][metadata::$objects[$this->obj]["parent_field"]]["list_mode"]=array("INF_BLOCK_ID"=>$main_block_id);
		}
		$this->inner_object->action_change();
	}

	/**
	 * Дополняем стандартное добавление информацией о блоке, в который мы добавляем запись
	 *
	 * @see table::action_added()
	 */
	public function action_added()
	{
		// Блок может быть получен как из формы, так и из фильтра. У формы приоритет, но если в форме блока не было, то подмешиваем его из фильтра
		if(!$_REQUEST["_form_INF_BLOCK_ID"]){
			$_REQUEST["_form_INF_BLOCK_ID"]=$_REQUEST["_f_INF_BLOCK_ID"];
		}
		
		if ( metadata::$objects[$this->obj]['only_one'] && $_REQUEST['_form_INF_BLOCK_ID'] )
			if ( $this -> full_object -> get_index_counter( array( '_f_INF_BLOCK_ID' => $_REQUEST['_form_INF_BLOCK_ID'] ), 'index', '' ) )
				metadata::$objects[$this->obj]['no_add'] = 1;
		
		$this->inner_object->action_added();
	}

	/**
	 * Скрываем фильтр по модулям во вкладке правязки к блокам
	 *
	 * @see table::action_m2m()
	 */
	public function action_m2m()
	{
		if ( $_REQUEST['m2m'] == 'CONTENT_MAP' )
			metadata::$objects['INF_BLOCK']['fields']['PRG_MODULE_ID']['filter_short'] = 0;
		
		$this->inner_object->action_m2m();
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Добавляем INNER JOIN по CONTENT_MAP, если в фильтре выбран какой-либо блок, заодно осуществляется ограничение выборки по правам на просмотр контента блоков
	 * 
	 * get_auth_clause_and_binds() в этом декораторе не применяется, так как нужно встроиться в INNER JOIN, а не в кляузу WHERE как в общем случае
	 *
	 * @see table::get_index_query_components()
	 */
	public function get_index_query_components(&$request, $mode, $list_mode){
		// формат переменной $components - array($fields, $joins, $where, $binds)
		$components=$this->inner_object->get_index_query_components($request, $mode, $list_mode);
		// Главный администратор должен увидеть даже те записи, которые не привязаны ни к одному блоку, если 1) фильтре блок не выбран, 2) нет указания ограничиться конкретным блоком
		if(!$this->auth->is_main_admin || $request["_f_INF_BLOCK_ID"] || $list_mode["INF_BLOCK_ID"]){
			// Строим ограничительную кляузу с учетом выбранного блока
			if($request["_f_INF_BLOCK_ID"] || $list_mode["INF_BLOCK_ID"]){
				$components[1].="
					INNER JOIN CONTENT_MAP ON 
						CONTENT_MAP.CONTENT_ID={$this->obj}.{$this->autoinc_name}
						AND CONTENT_MAP.INF_BLOCK_ID=:inf_block_id
						AND CONTENT_MAP.INF_BLOCK_ID IN (SELECT INF_BLOCK_ID FROM INF_BLOCK WHERE INF_BLOCK.TE_OBJECT_ID=:te_object_id_cm)
					";
				$components[3]["te_object_id_cm"]=$this->te_object_id;
				// Добавляем переменную привязки с блоком из фильтра или из $list_mode
				if($request["_f_INF_BLOCK_ID"]){
					$components[3]["inf_block_id"]=$request["_f_INF_BLOCK_ID"];
				}elseif($list_mode["INF_BLOCK_ID"]){
					$components[3]["inf_block_id"]=$list_mode["INF_BLOCK_ID"];
				}
			}
		}
		return $components;
	}

	/**
	 * Получение идентификатора главного блока записи (или ноль, если главного блока не обнаружилось)
	 * 
	 * @param array $pk						первичный ключ записи
	 * @param boolean $throw_exception		бросать ли исключение, если главного блока у записи не обнаружилось
	 * @return int
	 */
	public function get_main_block_id($pk, $throw_exception=false){
		$main_block_id=db::sql_select("
			SELECT CONTENT_MAP.INF_BLOCK_ID
			FROM CONTENT_MAP, INF_BLOCK, TE_OBJECT
			WHERE
				CONTENT_MAP.CONTENT_ID=:content_id AND
				CONTENT_MAP.IS_MAIN=1 AND
				CONTENT_MAP.INF_BLOCK_ID=INF_BLOCK.INF_BLOCK_ID AND
				INF_BLOCK.TE_OBJECT_ID=TE_OBJECT.TE_OBJECT_ID AND
				TE_OBJECT.SYSTEM_NAME=:obj
			", array("content_id"=>$pk[$this->autoinc_name], "obj"=>$this->obj));
			
		if($throw_exception && !$main_block_id[0]["INF_BLOCK_ID"]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_main_block_not_found"].": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
		}
		return (int)$main_block_id[0]["INF_BLOCK_ID"];
	}
	
	/**
	 * Получение идентификаторов всех блоков, к которым привязана запись
	 * 
	 * @param array $pk						первичный ключ записи
	 * @return array
	 */
	public function get_block_ids($pk){
		$block_ids=db::sql_select("
			SELECT CONTENT_MAP.INF_BLOCK_ID
			FROM CONTENT_MAP, INF_BLOCK, TE_OBJECT
			WHERE
				CONTENT_MAP.CONTENT_ID=:content_id AND
				CONTENT_MAP.INF_BLOCK_ID=INF_BLOCK.INF_BLOCK_ID AND
				INF_BLOCK.TE_OBJECT_ID=TE_OBJECT.TE_OBJECT_ID AND
				TE_OBJECT.SYSTEM_NAME=:obj
			", array("content_id"=>$pk[$this->autoinc_name], "obj"=>$this->obj));
		return array_keys( lib::array_reindex( $block_ids, "INF_BLOCK_ID" ) );
	}
	
	/**
	 * Очищает кэш всех блоков, к которым привязана запись
	 * 
	 * @param array $pk						первичный ключ записи
	 * @return array
	 */
	public function delete_content_cache( $pk )
	{
		$inf_block_obj = object::factory( 'INF_BLOCK' );
		foreach ( $this -> full_object -> get_block_ids( $pk ) as $block_id )
			$inf_block_obj -> exec_delete_cache( array( 'INF_BLOCK_ID' => $block_id ) );
		$inf_block_obj -> __destruct();
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Возвращает кляузу для выборки только тех записей, которые можно смотреть данному пользователю
	 *
	 * Заменяет собой get_auth_clause_and_binds(), так как нужно встроиться в INNER JOIN в обход стандартного механизма
	 *
	 * @return array
	 */
	public function get_auth_for_cm(){
		if($this->auth->is_main_admin){ // Для главного администратора ограничений нет
			return array("", array());
		}else{
			list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, $this->full_object->get_privs("view", ""), ($this->decorators["workflow"] ? "workflow" : "inf_block"), "INF_BLOCK");
			return array("
				AND (
					(CONTENT_MAP.INF_BLOCK_ID IN (SELECT INF_BLOCK.INF_BLOCK_ID FROM {$auth_tables} WHERE {$auth_clause} AND INF_BLOCK.TE_OBJECT_ID=:te_object_id1))
					OR
					(CONTENT_MAP.INF_BLOCK_ID IN (SELECT INF_BLOCK.INF_BLOCK_ID FROM INF_BLOCK WHERE INF_BLOCK.SITE_ID IN ({$this->auth->sites_in}) AND INF_BLOCK.TE_OBJECT_ID=:te_object_id2))
				)
			", $auth_binds+array("te_object_id1"=>$this->te_object_id, "te_object_id2"=>$this->te_object_id));
		}
	}

	/**
	 * Возвращает кляузу для выборки только тех записей, которые можно смотреть данному пользователю
	 *
	 * @return array
	 */
	public function get_auth_clause_and_binds(){
		list($clause, $binds)=$this->full_object->get_auth_for_cm();
		$clause=" AND {$this->obj}.{$this->autoinc_name} IN (SELECT CONTENT_MAP.CONTENT_ID FROM CONTENT_MAP WHERE 1=1 {$clause})";
		return array($clause, $binds);
	}

	/**
	 * Просмотр контента в блоках и операции над ним доступны только в том случае, если есть права на соответствующий блок
	 *
	 * Внимание: для проверки прав на непосредственное добавление нужно обязательно указывать в $pk значение INF_BLOCK_ID, куда
	 * будет добавляться запись. В противном случае будет проверяться возможность добавления в любой блок данной таблицы, не важно какой
	 *
	 * @see object::is_permitted_to()
	 */
	public function is_permitted_to($ep_type, $pk="", $throw_exception=false){
		if($this->auth->is_main_admin || ($ep_type=="view" && auth::get_system_privilege($this->auth->user_roles_in, "add_block"))){ // Просмотр разрешаем в том числе обладателям права добавление блоков
			$is_permitted=true;
		}else{
			list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, $this->full_object->get_privs($ep_type, $pk, $throw_exception), ($this->decorators["workflow"] ? "workflow" : "inf_block"), "INF_BLOCK");
			if($ep_type=="view" || ($ep_type=="add" && !$pk["INF_BLOCK_ID"])){ // Просмотр и добавление возможны, если хотя бы на один из блоков этой таблицы есть права (смотреть можно также, если есть право на добавление блоков - см. выше)
				// Кляуза для администраторов сайтов
				if($this->auth->sites_in){
					$site_clause="
						UNION
							SELECT DISTINCT INF_BLOCK.INF_BLOCK_ID FROM INF_BLOCK, TE_OBJECT
							WHERE INF_BLOCK.TE_OBJECT_ID=TE_OBJECT.TE_OBJECT_ID AND TE_OBJECT.SYSTEM_NAME=:obj2 AND INF_BLOCK.SITE_ID IN ({$this->auth->sites_in})
					";
					$site_binds=array("obj2"=>$this->obj);
				}else{
					$site_binds=array();
				}
				// Проверка прав
				$rights=db::sql_select("
					SELECT COUNT(*) AS COUNTER FROM 
						(SELECT DISTINCT INF_BLOCK.INF_BLOCK_ID
						FROM {$auth_tables}, TE_OBJECT
						WHERE {$auth_clause} AND INF_BLOCK.TE_OBJECT_ID=TE_OBJECT.TE_OBJECT_ID AND TE_OBJECT.SYSTEM_NAME=:obj
						{$site_clause}
						) T
				", array_merge(array("obj"=>$this->obj), $auth_binds)+$site_binds);
			}elseif($ep_type=="add" && $pk["INF_BLOCK_ID"]){ // Добавление в конкретный блок возможно, если на этот блок есть права
				$is_permitted=auth::is_site_admin_for("INF_BLOCK", $pk["INF_BLOCK_ID"]);
				if(!$is_permitted){
					$rights=db::sql_select("SELECT COUNT(*) AS COUNTER FROM {$auth_tables}
						WHERE {$auth_clause}
							AND INF_BLOCK.INF_BLOCK_ID=:inf_block_id
					", array_merge(array("inf_block_id"=>$pk["INF_BLOCK_ID"]), $auth_binds));
				}else{
					$rights[0]["COUNTER"]=1; // Для простоты
				}
			}else{ // Все прочие типы операций разрешаем только если есть права на главный блок записи
				$is_permitted=auth::is_site_admin_for($this->obj, $pk[$this->autoinc_name], true);
				if(!$is_permitted){
					$rights=db::sql_select("SELECT COUNT(*) AS COUNTER FROM {$auth_tables}, CONTENT_MAP
						WHERE {$auth_clause}
							AND INF_BLOCK.TE_OBJECT_ID=:te_object_id
							AND CONTENT_MAP.INF_BLOCK_ID=INF_BLOCK.INF_BLOCK_ID
							AND CONTENT_MAP.CONTENT_ID=:id
							AND CONTENT_MAP.IS_MAIN=1
					", array("id"=>$pk[$this->autoinc_name], "te_object_id"=>$this->te_object_id)+$auth_binds);
				}else{
					$rights[0]["COUNTER"]=1; // Для простоты
				}
			}
			$is_permitted=(bool)$rights[0]["COUNTER"];
		}
		if(!$is_permitted && $throw_exception){
			// Название записи в сообщении не выводится по соображениям защиты данных
			$pk_message=($pk[$this->autoinc_name] ? ": (".$this->primary_key->pk_to_string($pk).")" : "");
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_operation_not_permitted_".$ep_type].$pk_message);
		}
		return $is_permitted;
	}

	/**
	 * Любые операции разрешаем только в том случае, если есть права на доступ к главным блокам записей
	 *
	 * @see object::is_permitted_to_mass()
	 * @todo Дистинкт применяется, надо понаблюдать за производительностью
	 */
	public function is_permitted_to_mass($ep_type, $ids=array(), $throw_exception=false){
		$not_allowed=array();
		if($this->auth->is_main_admin){
			// Можно все
		}else{
			// Кляуза для администраторов сайтов
			$in=(is_array($ids) && count($ids)>0 ? join(", ", $ids) : 0);
			if($this->auth->sites_in){
				$site_clause="
					UNION
					SELECT DISTINCT CONTENT_MAP.CONTENT_ID FROM INF_BLOCK, CONTENT_MAP
					WHERE INF_BLOCK.SITE_ID IN ({$this->auth->sites_in})
						AND INF_BLOCK.TE_OBJECT_ID=:te_object_id2
						AND CONTENT_MAP.INF_BLOCK_ID=INF_BLOCK.INF_BLOCK_ID
						AND CONTENT_MAP.CONTENT_ID IN ({$in})
						AND CONTENT_MAP.IS_MAIN=1
				";
				$site_binds=array("te_object_id2"=>$this->te_object_id);
			}else{
				$site_binds=array();
			}
			// Проверка прав
			list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, "access", "inf_block", "INF_BLOCK");
			$rights=db::sql_select("SELECT DISTINCT CONTENT_MAP.CONTENT_ID FROM {$auth_tables}, CONTENT_MAP
				WHERE {$auth_clause}
					AND INF_BLOCK.TE_OBJECT_ID=:te_object_id
					AND CONTENT_MAP.INF_BLOCK_ID=INF_BLOCK.INF_BLOCK_ID
					AND CONTENT_MAP.CONTENT_ID IN ({$in})
					AND CONTENT_MAP.IS_MAIN=1
					{$site_clause}
				", $auth_binds+array("te_object_id"=>$this->te_object_id)+$site_binds);
			$not_allowed=$this->full_object->is_permitted_to_mass_report($ids, $rights, "CONTENT_ID");
			// для случая с $throw_exception
			$is_permitted=!(bool)count($not_allowed);
			if(!$is_permitted && $throw_exception){
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_mass_operation_not_permitted_".$ep_type].": ".join(", ", $not_allowed));
			}
		}
		return $not_allowed;
	}
	
	/**
	* Для блоков записи может разблокировать так же администратор сайта
	* @see table::is_checkinout_admin
	* @return boolean
	*/
	
	public function is_checkinout_admin() {
		$pk = $this->primary_key->get_from_request();
		return $this->call_parent('is_checkinout_admin') || auth::is_site_admin_for($this->obj, $pk[$this->autoinc_name], true);
	}
	

	/**
	 * Возвращает список системных названий прав, которые имеют права осуществлять элементарную операцию $mode
	 *
	 * Нужен для удобства работы блоков с Воркфлоу, подробное описание можно посмотреть там же
	 *
	 * @param string $mode	режим работы метода - режимы в точности такие как и элементарные права системы
	 * @param array $pk		первичный ключ записи (если это имеет смысл для данного действия). В некоторых случаях (добавление) здесь может передаваться не собственно первичный ключ, а информация о месте, куда добавляется запись - идентификатор родителя, идентификатор блока и другие данные, помогающие определить - есть права или нет. А еще может передаваться идентификатор резолюции для resolve
	 * @return mixed
	 */
	public function get_privs($mode, $pk){
		return "access";
	}

	/**
	* Получает дополнительные данные для лога
	*/	
	public function get_additional_info_for_log($fields) {
		if (is_array($fields['log_params']['log_info']))
			$fields['log_params']['log_info'] += $this->get_needed_fields_for_log($fields);
		else 
			$fields['log_params']['log_info'] = $this->get_needed_fields_for_log($fields);

		return $this->inner_object->get_additional_info_for_log($fields);
	}
	
	/**
	* Регистрация события в журнале
	* Для того чтобы зарегистрировать добавление чуть позже прибегаем к трюку - блокируем событие add и вызываем
	* его для события add_block, которое вызываем в нужном месте
	* Параметры см. {@link table::log_register}
	*/
	
	public function log_register($type, $fields) {
		if ($type=='add') return;
		if ($type=='add_block') $type='add';
		$this->inner_object->log_register($type, $fields);
	}
	
	/**
	* Возвращает данные для записи в лог
	* @return array
	*/
	
	private function get_needed_fields_for_log($fields) {
		if ($this->log_fields) return $this->log_fields;
		
		$main_block = $this->full_object->get_main_block_id($fields['pk']?$fields['pk']:$fields);
		if (!$main_block) {
			$this->log_fields['inf_block_title']=metadata::$lang['lang_has_no_main_block'];
		}
		else {
			$inf_block_data = db::sql_select('SELECT INF_BLOCK_ID, TITLE AS INF_BLOCK_TITLE, PRG_MODULE_ID FROM INF_BLOCK  WHERE INF_BLOCK_ID=:inf_block_id', array('inf_block_id'=>$main_block));
			
			if ($inf_block_data) {
				$this->log_fields['inf_block_id']=$inf_block_data[0]['INF_BLOCK_ID'];
				$this->log_fields['inf_block_title']=$inf_block_data[0]['INF_BLOCK_TITLE'];
				$this->log_fields['inf_block_table']=$this->obj;
				$this->log_fields['prg_module_id']=$inf_block_data[0]['PRG_MODULE_ID'];
				
				$prg_module_obj = object::factory( 'PRG_MODULE' );
				list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
					 $prg_module_obj -> ext_field_selection( 'TITLE', 1 );
				$prg_module_obj -> __destruct();	 
					 
				$prg_module = db::sql_select( '
					select ' . $dec_field . ' as TITLE from PRG_MODULE ' . $dec_join[0] . ' where PRG_MODULE_ID=:prg_module_id',
						$dec_binds + array('prg_module_id'=>$inf_block_data[0]['PRG_MODULE_ID']) );
				$this->log_fields['prg_module_title']=$prg_module[0]['TITLE'];
			}
		}
		
		return $this->log_fields;
	}
	
	/**
	* Данные для экспорта дополняются информацией о главных и неглавных блоках записей. При этом для простоты и удобности алгоритма 
	* информация о неглавных блоках включается вся вне зависимости от того, экспортируются ли данные о них или нет
	*/
	
	public function get_export_add_data_xml($pk) {
		$xml = $this->inner_object->get_export_add_data_xml($pk);
		
		$blocks=db::sql_select("
			SELECT 
				CM.INF_BLOCK_ID, CM.IS_MAIN
			FROM 
				CONTENT_MAP CM
					INNER JOIN
						INF_BLOCK IB
					ON	
						(CM.INF_BLOCK_ID = IB.INF_BLOCK_ID)
			WHERE
				CM.CONTENT_ID=:content_id 
					AND
						IB.TE_OBJECT_ID = :obj_id
			", array("content_id"=>$pk[$this->autoinc_name], "obj_id"=>object_name::$te_object_ids[$this->obj]['TE_OBJECT_ID'])
		);
			
		for ($i=0, $n=sizeof($blocks); $i<$n; $i++)
			$xml .= "<".(($blocks[$i]['IS_MAIN'])?'MAIN_':'')."BLOCK_ID ID=\"{$blocks[$i]['INF_BLOCK_ID']}\" />\n";
		
		return $xml;
	}
	
	
	/**
	* Метод импорта данных из XML - унаследованный метод от table
	* Дополняем импорт импортом данных о привязке к блокам
	* @param array $xml_arr массив данных одной записи, возвращаемый ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return array карта соответствия между id записей из файла импорта и реальными
	*/

	public function import_from_xml ($xml_arr, &$import_data) {
		$id_map = $this->inner_object->import_from_xml($xml_arr, $import_data);
		$this->import_blocks(current($id_map), $xml_arr['children'], $import_data);
		return $id_map;
	}

	/**
	* Возвращает поля для вставки в таблицу в процессе импорта - унаследованный метод от table
	* Дополняет функционал добавлением поля INF_BLOCK_ID с данными о главном блоке записи
	* @param array $main_children данные обо всех потомках массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return array Подготовленные данные для вставки в таблицу
	*/

	public function get_import_field_values($main_children, &$import_data) {
		$fields = $this->inner_object->get_import_field_values($main_children, $import_data);

		for ($i=0, $n=sizeof($main_children); $i<$n; $i++) 
			if ($main_children[$i]['tag']=='MAIN_BLOCK_ID')
				$fields['INF_BLOCK_ID']=$this->full_object->get_import_new_id($main_children[$i]['attributes']['ID'], 'INF_BLOCK', $import_data);

		return $fields;
	}
	
	/**
	* Привязка элементов контекста к неглавным блокам
	* @param int $content_id ID вставленной записи
	* @param array $main_children данные обо всех потомках массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	*/
	
	private function import_blocks($content_id, $main_children, &$import_data) {
		$block_ids=array();
		for ($i=0, $n=sizeof($main_children); $i<$n; $i++) 
			if ($main_children[$i]['tag']=='BLOCK_ID') 
				// не пользуемся методом из table, потому что нам не нужен тут exception, блоков может и не быть
				if ($block_id=$import_data['id_maps']['INF_BLOCK'][$main_children[$i]['attributes']['ID']])
					$block_ids[]=$block_id; 
		
		for ($i=0, $n=sizeof($block_ids); $i<$n; $i++) 
			db::insert_record("CONTENT_MAP", array("INF_BLOCK_ID"=>$blocks_id[$i], "CONTENT_ID"=>$content_id, "IS_MAIN"=>0));
	}
	
	/**
	 * Учет информационного блока при автозаполнении поля порядок
	 *
	 * @see table::get_group_where()
	 */
	public function get_group_where( $group_by, &$group_values, $prefix )
	{
		list( $where, $joins, $binds ) = $this -> inner_object -> get_group_where( $group_by, $group_values, $prefix );
		
		if ( $group_values[$prefix . 'INF_BLOCK_ID'] )
		{
			$joins .= ' inner join CONTENT_MAP on CONTENT_MAP.CONTENT_ID = ' . $this -> obj . '.' . $this -> autoinc_name. ' and CONTENT_MAP.INF_BLOCK_ID = :g_join_inf_block_id ';
			$binds['g_join_inf_block_id'] = $group_values[$prefix . 'INF_BLOCK_ID'];
		}
		
		return array( $where, $joins, $binds );
	}
	
	private function set_inf_block_field() {
		if ( metadata::$objects[$this->obj]['only_one'] && $_REQUEST['_f_INF_BLOCK_ID'] )
			if ( $this -> full_object -> get_index_counter( array( '_f_INF_BLOCK_ID' => $_REQUEST['_f_INF_BLOCK_ID'] ), 'index', '' ) )
				metadata::$objects[$this->obj]['no_add'] = 1;
		
		if ( metadata::$objects[$this->obj]['only_one'] )
			metadata::$objects[$this->obj]["fields"]["INF_BLOCK_ID"]["list_mode"] =
				array_merge( metadata::$objects[$this->obj]["fields"]["INF_BLOCK_ID"]["list_mode"], array( "only_one" => 1 ) );
		
		if(!$_REQUEST["_f_INF_BLOCK_ID"]){
			metadata::$objects[$this->obj]["fields"]["INF_BLOCK_ID"]["no_add"]=0;
			if ( $this -> parent_id !== '' )
				$_REQUEST["_f_INF_BLOCK_ID"]=$this->full_object->get_main_block_id(array($this->autoinc_name=>$this -> parent_id));
		}elseif($this->parent_id!==""){
			metadata::$objects[$this->obj]["fields"][metadata::$objects[$this->obj]["parent_field"]]["list_mode"]=array("INF_BLOCK_ID"=>$_REQUEST["_f_INF_BLOCK_ID"]);
		}		
		
	}
	
}
?>
