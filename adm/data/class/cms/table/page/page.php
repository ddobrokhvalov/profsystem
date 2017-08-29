<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Разделы"
 *
 * @package		RBC_Contents_5_0
 * @subpackage cms
 * @copyright	Copyright (c) 2006 RBC SOFT
 * @todo Сделать редиректилку в клиентской части из корня в /ru/
 * @todo При изменении раздела типа папка ошибка с array_diff. Подозрение на то, что к разделу типа "папка" пытаются примениться шаблоны разделов.
 */
class page extends table_version
{
	/**
	 * Форсируется выставление типа страницы и настройки полей для этого типа. Производятся дополнительные проверки (специфичные для разделов)
	 *
	 * Поскольку методы API могут вызываться откуда угодно, то нельзя рассчитывать на уточнение метаданных, которое производится
	 * в {@link page::apply_inner_object()}. Требуется сделать это в данном методе заново.
	 *
	 * @see table::exec_add()
	 * @metadatamod изменяется описание полей
	 */
	public function exec_add($raw_fields, $prefix){
		// При создании главной страницы проверяем, чтобы уже не было страниц для этого сайта и этого языка
		if ($raw_fields[$prefix."PARENT_ID"]==0){
			$this->check_main_page($raw_fields[$prefix."SITE_ID"], $raw_fields[$prefix."LANG_ID"]);
		// Для неглавной проверяем, чтобы был валидный родитель
		}else{
			$parent_page=db::sql_select("SELECT SITE_ID, LANG_ID FROM PAGE WHERE PAGE_ID=:parent_id ORDER BY VERSION DESC", array("parent_id"=>$raw_fields[$prefix."PARENT_ID"]));
			if(count($parent_page)==0){
				throw new Exception($this->te_object_name." (".metadata::$lang["lang_adding"]."): ".metadata::$lang["lang_parent_page_not_found"].": (".$raw_fields[$prefix."PARENT_ID"].")");
			}
		}
		// Добавляем тип, если его еще нет
		$p_type=$raw_fields[$prefix."PAGE_TYPE"];
		if(!$p_type=="folder" && !$p_type=="link"){
			$raw_fields[$prefix."PAGE_TYPE"]="page";
		}
		// Достраиваем нужные поля
		$this->adjust_page_fields(true, $raw_fields[$prefix."PARENT_ID"], $p_type, true);
		// Собственно добавление
		$last_insert_id=parent::exec_add($raw_fields, $prefix);
		// Выставляем язык и сайт для свежедобавленного раздела, а также директорию для главной страницы
		if($raw_fields[$prefix."PARENT_ID"]!=0){
			db::update_record("PAGE", array("SITE_ID"=>$parent_page[0]["SITE_ID"], "LANG_ID"=>$parent_page[0]["LANG_ID"]), "", array("PAGE_ID"=>$last_insert_id));
		}else{
			$lang=db::sql_select("SELECT ROOT_DIR FROM LANG WHERE LANG_ID=:lang_id", array("lang_id"=>$raw_fields[$prefix."LANG_ID"]));
			db::update_record("PAGE", array("DIR_NAME"=>$lang[0]["ROOT_DIR"]), "", array("PAGE_ID"=>$last_insert_id));
		}
		// Клонируем права от родителя. Прошита намертво тестовая версия, чтобы исключить дупликацию. Ситуацию с одной рабочей считаем нештатной
		list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, array("change", "publish", "meta_change"), "page", "PAGE");
		$rights=db::sql_select("SELECT AUTH_ACL.* FROM {$auth_tables} WHERE {$auth_clause} AND AUTH_ACL.OBJECT_ID=:page_id AND PAGE.VERSION=1", $auth_binds+array("page_id"=>$raw_fields[$prefix."PARENT_ID"]));
		foreach($rights as $right){
			db::insert_record("AUTH_ACL", array_merge($right, array("OBJECT_ID"=>$last_insert_id)));
		}
		
		// exec_gen_page может выдать исключение, но запись в БД уже добавлена
		// Поскольку транзакции пока что у нас не поддерживаются, решаем
		// данную проблему здесь локально - удаляем добавленную запись.

		try {
			$this->exec_gen_page($last_insert_id, 1);
		}
		catch (Exception $e) {
			$this->exec_delete(array ('PAGE_ID' => $last_insert_id));
			throw $e;
		}		
		
		return $last_insert_id;
	}

	/**
	 * При копировании клонируются параметры областей раздела
	 *
	 * @see table::exec_copy()
	 * @todo Подумать и проверить, как тут с языками, а то нехорошо русскую, например, страницу копировать в английскую версию.
	 */
	public function exec_copy($raw_fields, $prefix, $pk){
		$source_page=$this->get_change_record($pk);
		// Проверяем попытку сменить сайт
		if($raw_fields[$prefix."SITE_ID"] && $raw_fields[$prefix."SITE_ID"] != $source_page["SITE_ID"]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_change_site"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")");
		}
		// Проверяем попытку сменить язык
		if($raw_fields[$prefix."LANG_ID"] && $raw_fields[$prefix."LANG_ID"] != $source_page["LANG_ID"]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_change_lang"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")");
		}
		// Проверяем попытку смены языковой версии и сайта
		if ( $raw_fields[$prefix."PARENT_ID"] )
		{
			$check_lang = db::sql_select( "select SITE_ID, LANG_ID from PAGE where PAGE_ID = :page_id", array( "page_id" => $raw_fields[$prefix."PARENT_ID"] ) );
			if($check_lang[0]["SITE_ID"] != $source_page["SITE_ID"]){
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_change_site"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")");
			}
			if($check_lang[0]["LANG_ID"] != $source_page["LANG_ID"]){
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_change_lang"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")");
			}
		}
		// Собственно копирование
		$page_id=$this->call_parent('exec_copy', array($raw_fields, $prefix, $pk));
		// Донастройка параметров и генережка
		$this->clone_page_params($source_page["PAGE_ID"], $source_page["VERSION"], $page_id, 1);
		$this->cut_unneeded_areas($page["PAGE_ID"], 1, $source_page["TEMPLATE_ID"], $raw_fields[$prefix."TEMPLATE_ID"]);
		
		// Не нужно ловить исключение при генерации страницы, ибо только что она успешно сгенерилась в exec_add()
		$this->exec_gen_page($page_id, 1);
		
		return $page_id;
	}

	/**
	 * Форсируются настройки полей для текущего типа страницы. Производятся дополнительные проверки (специфичные для разделов)
	 *
	 * Если у раздела изменяется расположение в иерархии, то рабочая версия также перемещается в новое место!
	 *
	 * Поскольку методы API могут вызываться откуда угодно, то нельзя рассчитывать на уточнение метаданных, которое производится
	 * в {@link page::apply_inner_object()}. Требуется сделать это в данном методе заново.
	 *
	 * При смене типа раздела со страницы на папку области не удаляются намеренно, что бы облегчить жизнь пользователю
	 *
	 * @see table::exec_change()
	 * @metadatamod изменяется описание полей
	 */
	public function exec_change($raw_fields, $prefix, $pk){
		$this->adjust_page_fields(false, $raw_fields[$prefix."PARENT_ID"], $raw_fields[$prefix."PAGE_TYPE"]);
		// Сбор данных
		$page=$this->get_change_record($pk);
		$old_page_path=$this->get_page_path($page["PAGE_ID"], $page["VERSION"], true, true);
		$both_versions=db::sql_select("SELECT * FROM PAGE WHERE PAGE_ID=:page_id ORDER BY VERSION DESC", array("page_id"=>$pk["PAGE_ID"]));
		if($both_versions[1]["PAGE_ID"]){
			$old_work_page_path=$this->get_page_path($page["PAGE_ID"], 0, true, true);
		}
		// Для главной страницы проверяем, чтобы уже не было страниц для этого сайта и этого языка
		if($page["PARENT_ID"]==0){
			$this->check_main_page($raw_fields[$prefix."SITE_ID"], $raw_fields[$prefix."LANG_ID"], $pk);
		}
		// Проверяем попытку переместить страницу на верхний уровень
		if($raw_fields[$prefix."PARENT_ID"]==0 && $page["PARENT_ID"]!=0){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_move_to_root"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")");
		}
		// Проверяем попытку сменить сайт
		if($raw_fields[$prefix."SITE_ID"] && $raw_fields[$prefix."SITE_ID"] != $page["SITE_ID"]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_change_site"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")");
		}
		// Проверяем попытку сменить язык
		if($raw_fields[$prefix."LANG_ID"] && $raw_fields[$prefix."LANG_ID"] != $page["LANG_ID"]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_change_lang"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")");
		}
		// Проверяем попытку смены языковой версии и сайта
		if ( $raw_fields[$prefix."PARENT_ID"] )
		{
			$check_lang = db::sql_select( "select SITE_ID, LANG_ID from PAGE where PAGE_ID = :page_id", array( "page_id" => $raw_fields[$prefix."PARENT_ID"] ) );
			if($check_lang[0]["SITE_ID"] != $page["SITE_ID"]){
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_change_site"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")");
			}
			if($check_lang[0]["LANG_ID"] != $page["LANG_ID"]){
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_change_lang"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")");
			}
		}
		// Если раздел перемещается, то контролируем, чтобы его рабочая версия могла быть тоже корректно перемещена в новое место иерархии
		if($both_versions[1]["PAGE_ID"] && $page["PARENT_ID"]!=$raw_fields[$prefix."PARENT_ID"]){
			$check_dirs=db::sql_select("SELECT COUNT(*) AS COUNTER FROM PAGE WHERE PARENT_ID=:parent_id AND DIR_NAME=:dir_name AND VERSION=0", array("parent_id"=>$raw_fields[$prefix."PARENT_ID"], "dir_name"=>$both_versions[1]["DIR_NAME"]));
			if($check_dirs[0]["COUNTER"]>0){
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_work_version_dir_name_problem"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name]."), ".metadata::$lang["lang_directory"].": \"{$both_versions[1]["DIR_NAME"]}\"");
			}
		}
		
		// Меняем метаданные в зависимости от типа раздела
		if ( $page['PAGE_TYPE'] != 'link' )
		{
			$this->change_field_visibility( array( 'TEMPLATE_ID', 'IS_TITLE_SHOWED', 'PAGE_TYPE' ), 0, 0 );
			$this->adjust_page_fields(false, $page['PARENT_ID'], $raw_fields[$prefix.'PAGE_TYPE'], true);
			metadata::$objects['PAGE']['fields']['PAGE_TYPE']['value_list'] = array(
				array( 'title' => metadata::$lang['lang_page_type_page'], 'value' => 'page' ),
				array( 'title' => metadata::$lang['lang_page_type_folder'], 'value' => 'folder' ) );
		}
		else
			$this->adjust_page_fields(false, $page['PARENT_ID'], $page['PAGE_TYPE']);
		
		// Собственно изменение
		parent::exec_change($raw_fields, $prefix, $pk);
		// Отрезаем ненужные области
		$this->cut_unneeded_areas($page["PAGE_ID"], $page["VERSION"], $page["TEMPLATE_ID"], $raw_fields[$prefix."TEMPLATE_ID"]);
		// Если родитель или директория не совпадают с исходными, то делается перемещение директории раздела на новое место вместе со всеми детьми
		if($page["PAGE_TYPE"]!="link"){
			if($page["PARENT_ID"]!=0 && ($page["DIR_NAME"]!=$raw_fields[$prefix."DIR_NAME"] || $page["PARENT_ID"]!=$raw_fields[$prefix."PARENT_ID"])){
				$new_page_path=$this->get_page_path($page["PAGE_ID"], $page["VERSION"], true);
				@rename($old_page_path, $new_page_path);
				// Делаем то же самое с рабочей версией, если она существует, но только для иерархии - директорию можно менять по версиям раздельно
				if($both_versions[1]["PAGE_ID"] && $page["PARENT_ID"]!=$raw_fields[$prefix."PARENT_ID"]){
					db::update_record("PAGE", array("PARENT_ID"=>$raw_fields[$prefix."PARENT_ID"]), "", array("PAGE_ID"=>$page["PAGE_ID"], "VERSION"=>0));
					$new_work_page_path=$this->get_page_path($page["PAGE_ID"], 0, true);
					@rename($old_work_page_path, $new_work_page_path);
					$this->exec_gen_page($page["PAGE_ID"], 0);
				}
			}
			$this->exec_gen_page($page["PAGE_ID"], $page["VERSION"]);
		}
	}

	/**
	 * Генерируется опубликованный раздел. В том числе переименовывается директория, если путь изменился и перекладываются параметры
	 *
	 * @see table_version::exec_publish()
	 */
	public function exec_publish($pk){
		$work_page=db::sql_select("SELECT * FROM PAGE WHERE PAGE_ID=:page_id AND VERSION=:version", array("page_id"=>$pk["PAGE_ID"], "version"=>0));
		if($work_page[0]["PAGE_ID"]){
			$old_page_path=$this->get_page_path($pk["PAGE_ID"], 0, true, true);
		}
		$page=$this->get_change_record($pk, false);
		parent::exec_publish($pk);
		if($page["PAGE_TYPE"]!="link"){
			$new_page_path=$this->get_page_path($pk["PAGE_ID"], 0, true);
			if($old_page_path && $old_page_path!=$new_page_path){
				@rename($old_page_path, $new_page_path);
			}
			if($page["VERSION"]==1){ // Если тестовая версия была, то клонируем ее параметры в рабочую
				$this->clone_page_params($pk["PAGE_ID"], 1);
			}else{ // А если не было, то восстанавливаем ее
				$this->clone_page_params($pk["PAGE_ID"], 0);
				$this->exec_gen_page($pk["PAGE_ID"], 1);
			}
			$this->exec_gen_page($pk["PAGE_ID"], 0);
		}
	}

	/**
	 * Удаляется с файловой системы рабочая версия раздела и ее параметры из базы
	 *
	 * @see table_version::exec_unpublish()
	 */
	public function exec_unpublish($pk){
		$page=$this->get_change_record($pk);
		$path_to_del=$this->get_page_path($pk["PAGE_ID"], 0, true, true);
		// Если тестовой версии не было, то восстанавливаем ее параметры (саму запись раздела восстановит parent::exec_unpublish())
		if($page["VERSION"]==0){
			$this->clone_page_params($pk["PAGE_ID"], 0);
		}
		
		parent::exec_unpublish($pk);
		$this->delete_page_params($pk["PAGE_ID"], 0);
		if($page["PAGE_TYPE"]!="link" && $path_to_del){
			filesystem::rm_r($path_to_del, true);
		}
		// Если тестовой версии не было, то восстанавливаем ее на файловой системе
		if($page["VERSION"]==0){
			$this->exec_gen_page($pk["PAGE_ID"], 1);
		}
	}

	/**
	 * Пересобирается тестовая версия. В том числе переименовывается директория, если путь изменился
	 *
	 * @see table_version::exec_undo()
	 */
	public function exec_undo($pk){
		$page=$this->get_change_record($pk);
		$old_page_path=$this->get_page_path($pk["PAGE_ID"], 1, true, true);
		$versions=$this->full_object->get_versions($pk);
		parent::exec_undo($pk);
		if($page["PAGE_TYPE"]!="link"){
			$new_page_path=$this->get_page_path($pk["PAGE_ID"], 1, true);
			if($old_page_path && $old_page_path!=$new_page_path){
				@rename($old_page_path, $new_page_path);
			}
			if($versions[0]["VERSION"]==="0" || $versions[1]["VERSION"]==="0"){ // Чтобы не происходила поломка, если рабочей версии не было
				$this->clone_page_params($pk["PAGE_ID"], 0);
			}
			$this->exec_gen_page($pk["PAGE_ID"], 1);
		}
	}

	/**
	 * Добавить (или сменить) блок в область раздела, в случае неуспеха вызвает исключение
	 *
	 * Версия раздела выбирается автоматически по общему правилу (приоритет тестовой).
	 * Вначале происходит очистка параметров предыдущего блока, а затем назначается новый блок и его дефолтные параметры
	 *
	 * @param array $pk			первичный ключ, который определяет раздел, в который помещается блок
	 * @param int $area_id		идентификатор области шаблона (TEMPLATE_AREA_ID)
	 * @param int $block_id		идентификатор блока (INF_BLOCK_ID)
	 * @param boolean $safe		если истинно, то при попытке добавить тот же блок, что уже назначен этой области, ничего не происходит. Сделано для защиты от нажатия не на ту кнопку в раскладке
	 */
	public function exec_block_change($pk, $area_id, $block_id, $safe=false){
		// Проверки всех компонентов на существование и получение страницы
		$this->full_object->is_permitted_to("change", $pk, true);
		$page=$this->full_object->get_change_record($pk, true);
		$page_area_pk=array("PAGE_ID"=>$page["PAGE_ID"], "VERSION"=>$page["VERSION"], "TEMPLATE_AREA_ID"=>$area_id);
		lib::is_record_exists("TEMPLATE_AREA", array("TEMPLATE_AREA_ID"=>$area_id), true);
		lib::is_record_exists("INF_BLOCK", array("INF_BLOCK_ID"=>$block_id), true);
		// Если блок другой или его не было, то заменяем
		$check_block=db::sql_select("SELECT INF_BLOCK_ID FROM PAGE_AREA WHERE PAGE_ID=:PAGE_ID AND VERSION=:VERSION AND TEMPLATE_AREA_ID=:TEMPLATE_AREA_ID", $page_area_pk);
		if($check_block[0]["INF_BLOCK_ID"]!=$block_id || !$safe){
			db::delete_record("PAGE_AREA", $page_area_pk);
			db::insert_record("PAGE_AREA", array_merge($page_area_pk, array("INF_BLOCK_ID"=>$block_id)));
			$this->exec_block_params($pk, $area_id, "", "", true);
			$this->full_object->touch($pk, $page["VERSION"]);
			$this->exec_gen_page($page["PAGE_ID"], $page["VERSION"]);
			$this->full_object->log_register('block_params', array('pk'=>$pk));
		}
	}

	/**
	 * Назначение параметров блоку в области
	 *
	 * Версия раздела выбирается автоматически по общему правилу (приоритет тестовой)
	 *
	 * @param array $pk					первичный ключ, который определяет раздел, в который помещается блок
	 * @param int $area_id				идентификатор области шаблона (TEMPLATE_AREA_ID)
	 * @param array $record				"запись" со значениями параметров в формате {@link get_params_descr_and_values()}, в том числе подходит $_REQUEST
	 * @param string $record_prefix		префикс, которым дополнены данные записи, например, _f_ для фильтра
	 * @param boolean $default			игнорировать $record и помещать в параметры значения по умолчанию
	 */
	public function exec_block_params($pk, $area_id, $record, $record_prefix="", $default=false){
		// Проверки всех компонентов на существование, получение нужных данных
		$this->full_object->is_permitted_to("change", $pk, true);
		$page=$this->full_object->get_change_record($pk, true);
		$page_area_pk=array("PAGE_ID"=>$page["PAGE_ID"], "VERSION"=>$page["VERSION"], "TEMPLATE_AREA_ID"=>$area_id);
		$page_area=db::sql_select("SELECT * FROM PAGE_AREA WHERE PAGE_ID=:PAGE_ID AND VERSION=:VERSION AND TEMPLATE_AREA_ID=:TEMPLATE_AREA_ID", $page_area_pk);
		lib::is_record_exists("TEMPLATE_AREA", array("TEMPLATE_AREA_ID"=>$area_id), true);
		lib::is_record_exists("INF_BLOCK", array("INF_BLOCK_ID"=>$page_area[0]["INF_BLOCK_ID"]), true);
		list($params, $field_descr, $old_record)=$this->full_object->get_params_descr_and_values($page["PAGE_ID"], $page["VERSION"], $area_id);
		// Удаляем старое
		db::delete_record("PAGE_AREA_PARAM", $page_area_pk);
		// Добавляем новое
		if(!$default){ // Обычным образом
			foreach($field_descr as $field_name=>$descr){
				$prepared_fields[$field_name]=$this->field->get_prepared($record[$record_prefix.$field_name], $descr);
			}
			foreach($prepared_fields as $field_name=>$value){
				db::insert_record("PAGE_AREA_PARAM", array_merge($page_area_pk, array("MODULE_PARAM_ID"=>$field_descr[$field_name]["module_param_id"], "VALUE"=>$value)));
			}
		}else{ // Значения по умолчанию
			// Здесь делается хитрый маневр - вынимаются все значения, но с сортировкой по IS_DEFAULT, поэтому при переиндексации по MODULE_PARAM_ID дефолтные значения затирают все предыдущие, и получается именно то, что нам надо
			// Выборка данных
			$params_in=lib::array_make_in($params, "MODULE_PARAM_ID");
			$param_values=db::sql_select("SELECT * FROM PARAM_VALUE WHERE MODULE_PARAM_ID IN ({$params_in}) ORDER BY IS_DEFAULT");
			$r_param_values=lib::array_reindex($param_values, "MODULE_PARAM_ID");
			// Сбор значений
			foreach($field_descr as $field_name=>$descr){
				$param_type=$params[$descr["module_param_id"]]["PARAM_TYPE"];
				if($param_type=="select" || $param_type=="template"){
					$value=$r_param_values[$descr["module_param_id"]]["PARAM_VALUE_ID"];
				}elseif($param_type=="table" || $param_type=="page"){
					$value=0; // Для параметров "таблица" и "страница" дефолтным значением является 0, то есть никакая запись и текущая страница соответственно
				}else{
					$value=$params[$descr["module_param_id"]]["DEFAULT_VALUE"];
				}
				// Собственно вставка
				db::insert_record("PAGE_AREA_PARAM", array_merge($page_area_pk, array("MODULE_PARAM_ID"=>$descr["module_param_id"], "VALUE"=>$value)));
			}
		}
		$this->full_object->touch($pk, $page["VERSION"]);
		$this->exec_gen_page($page["PAGE_ID"], $page["VERSION"]);
		$this->full_object->log_register('block_params', array('pk'=>$pk));
	}

	/**
	 * Отвязать блок от данной области
	 *
	 * Версия раздела выбирается автоматически по общему правилу (приоритет тестовой)
	 * Вначале происходит очистка блока, а потом и его параметров
	 *
	 * @param array $pk			первичный ключ, который определяет раздел, в который помещается блок
	 * @param int $area_id		идентификатор области шаблона (TEMPLATE_AREA_ID)
	 */
	public function exec_block_unlink($pk, $area_id)
	{
		// Проверки всех компонентов на существование
		$this->full_object->is_permitted_to("change", $pk, true);
		$page=$this->full_object->get_change_record($pk, true);
		
		$this -> delete_page_params( $page["PAGE_ID"], $page["VERSION"], $area_id );
		
		$this->full_object->touch($pk, $page["VERSION"]);
		$this->exec_gen_page($page["PAGE_ID"], $page["VERSION"]);
		$this->full_object->log_register('block_params', array('pk'=>$pk));
	}
	
	/**
	 * Редактирование метатегов
	 */
	public function exec_meta_change( $raw_fields, $prefix, $pk )
	{
		$this -> full_object -> is_permitted_to( 'meta_change', $pk, true );
		$versions = $this -> full_object -> get_versions( $pk );
		
		// Обновление метатегов в тестовой версии
		if ( $versions[0]['VERSION'] == 1 )
		{
			db::update_record( 'PAGE', array(
				'META_TITLE' => $raw_fields[$prefix . 'META_TITLE'], 'META_KEYWORDS' => $raw_fields[$prefix . 'META_KEYWORDS'],
				'META_DESCRIPTION' => $raw_fields[$prefix . 'META_DESCRIPTION'] ), '', $pk + array( 'VERSION' => 1 ) );
			
			// Если метатеги меняются в обоих версиях, TIMESTAMP не меняем
			if ( !( $versions[1]['VERSION'] === '0' && $raw_fields[$prefix . 'PUBLISH_METATAGS'] ) )
				$this -> full_object -> touch( $pk, 1 );
			
			$this -> exec_gen_page( $pk['PAGE_ID'], 1 );
		}
		
		// Обновление метатегов в рабочей версии
		if ( $versions[0]['VERSION'] === '0' || ( $versions[1]['VERSION'] === '0' && $raw_fields[$prefix . 'PUBLISH_METATAGS'] ) )
		{
			db::update_record( 'PAGE', array(
				'META_TITLE' => $raw_fields[$prefix . 'META_TITLE'], 'META_KEYWORDS' => $raw_fields[$prefix . 'META_KEYWORDS'],
				'META_DESCRIPTION' => $raw_fields[$prefix . 'META_DESCRIPTION'] ), '', $pk + array( 'VERSION' => 0 ) );
			
			// Если метатеги меняются в обоих версиях, TIMESTAMP не меняем
			if ( !( $versions[1]['VERSION'] === '0' && $raw_fields[$prefix . 'PUBLISH_METATAGS'] ) )
				$this -> full_object -> touch( $pk, 0 );
			
			$this -> exec_gen_page( $pk['PAGE_ID'], 0 );
		}
	}
	
	/**
	 * Генерация клиентского раздела на файловой системе. Возвращает true в случае успеха.
	 *
	 * @param int $page_id	идентификатор раздела
	 * @param int $version	версия
	 */
	public function exec_gen_page($page_id, $version){
		//static $template_area_info;
		lib::is_record_exists("PAGE", array("PAGE_ID"=>$page_id, "VERSION"=>$version), true);
		// Данные о разделе и его областях
		$page=db::sql_select("
			SELECT PAGE.*, TEMPLATE.TEMPLATE_DIR, SITE.TITLE AS SITE_TITLE, SITE.TITLE_SEPARATOR, TEMPLATE.DOCTYPE, LANG.ROOT_DIR, LANG.PRIORITY
			FROM SITE, LANG, PAGE
			LEFT JOIN TEMPLATE ON TEMPLATE.TEMPLATE_ID=PAGE.TEMPLATE_ID
			WHERE PAGE.PAGE_ID=:page_id AND PAGE.VERSION=:version AND SITE.SITE_ID=PAGE.SITE_ID AND PAGE.LANG_ID=LANG.LANG_ID
		", array("page_id"=>$page_id, "version"=>$version));
		// Если страница является ссылкой, то молча выходим - ссылки не генерируются
		if($page[0]["PAGE_TYPE"]=="link"){
			return;
		}
		$areas=$this->get_page_areas($page_id, $version, false);
		$page_path=$this->get_page_path($page_id, $version, true);
		// Пересоздать директорию раздела и удалить то, что там было раньше
		@mkdir($page_path, 0777, true);
		@chmod($page_path, 0777);
		filesystem::rm_r($page_path, true, true);
		// В корневые директории неглавных языков укладываем соответствующие .htaccess
		if ( !$page[0]["PARENT_ID"] && !$page[0]["PRIORITY"] )
			$this -> write_htaccess( $page_path, $page[0]["ROOT_DIR"] );
		// Если страница является папкой, то укладываем редиректилку вместо обычного раздела
		if($page[0]["PAGE_TYPE"]=="folder"){
			$folder_tpl=new smarty_ee();
			$folder_tpl->left_delimiter="{{";
			$folder_tpl->right_delimiter="}}";
			$folder_tpl->assign("params_path", params::$params["common_data_server"]["value"]."config/params.php");
			$folder_tpl->assign("page_id", $page[0]["PAGE_ID"]);
			$folder_tpl->assign("version", $page[0]["VERSION"]);
			
			$folder_body = $folder_tpl->fetch(dirname(__FILE__)."/folder.php");
			if(!@file_put_contents($page_path."index.php", $folder_body)){
				throw new Exception($this->get_generate_page_message($page[0], false));
			}
			unset($folder_tpl);
		// Обычный раздел
		}else{
			// Файлы шаблона
			$template_files=filesystem::ls_r(params::$params["adm_data_server"]["value"]."page_tpl/".$page[0]["TEMPLATE_DIR"], 1, 1);
			
			// Для областей с модулем "Текст" вычисляем идентификаторы элементов контента
			$content_text_blocks = array();
			foreach ( $areas as $area )
				if ( $area['PRG_MODULE_SYSTEM_NAME'] == 'CONTENT_TEXT' )
					$content_text_blocks[] = $area['INF_BLOCK_ID'];
			$content_text_blocks = lib::array_reindex(
				db::sql_select( 'select * from CONTENT_MAP where INF_BLOCK_ID in ( ' .
					lib::array_make_in( $content_text_blocks ) . ' )' ), 'INF_BLOCK_ID' );
			
			// Бежим по областям и собираем их параметры
			foreach($areas as $area){
				if($area["INF_BLOCK_ID"]){
					$area["instance_name"]="area_".$area["SYSTEM_NAME"]."_".$area["TEMPLATE_AREA_ID"];
					$toolbar_span_block = $area["IS_ELEMENTS"] ? "<span id=\"block.{$area["INF_BLOCK_ID"]}.{$area["PRG_MODULE_SYSTEM_NAME"]}.{$area["TEMPLATE_AREA_ID"]}".(isset($content_text_blocks[$area["INF_BLOCK_ID"]])?".{$content_text_blocks[$area["INF_BLOCK_ID"]]["CONTENT_ID"]}":"")."\"></span>\n" : "";
					$areas_echo_body[$area["SYSTEM_NAME"]]=$toolbar_span_block."<?"."php echo \${$area["instance_name"]}->get_body(); ?".">";
					// Параметры представления
					list($params, $field_descr, $record)=$this->get_params_descr_and_values($page_id, $version, $area["TEMPLATE_AREA_ID"]);
					$view_param=array();
					foreach($params as $param){
						$view_param[$param["SYSTEM_NAME"]]=($param["PARAM_TYPE"]=="select" || $param["PARAM_TYPE"]=="template" ? $param["PARAM_VALUE"] : $param["VALUE"]);
					}
					$area["view_param"]=var_export($view_param, 1);
					// Переменные окружения
					$env=array(
						"page_id"=>(int)$page[0]["PAGE_ID"], "version"=>(int)$page[0]["VERSION"], "lang_id"=>(int)$page[0]["LANG_ID"], "lang_root_dir"=>$page[0]["ROOT_DIR"], "site_id"=>(int)$page[0]["SITE_ID"],
						"area_id"=>(int)$area["TEMPLATE_AREA_ID"], "block_id"=>(int)$area["INF_BLOCK_ID"], "cache_time"=>(int)$area["CACHE_TIME"], "is_main"=>(int)$area["IS_MAIN"],
					);
					$area["env"]=preg_replace("/\s+/", " ", var_export($env, 1));
					$areas_with_block[]=$area;
				}
			}
			
			// Массив общих для всех шаблонов параметров заголовка страницы
			$common_header_params = array(
				"params_path" => params::$params["common_data_server"]["value"]."config/params.php",
				"site_title" => $page[0]["SITE_TITLE"],
				"title_separator" => $page[0]["TITLE_SEPARATOR"]?$page[0]["TITLE_SEPARATOR"]:params::$params["title_separator"]["value"], //$page[0]["SITE_TITLE"]?$page[0]["SITE_TITLE"]:params::$params["title_separator"]["value"],
				"page" => $page[0] );
				
				
			// Собираем окончательные файлы и укладываем их на файловую систему
			$module_syswords_var="module_syswords_".$page[0]["ROOT_DIR"];
			include(params::$params["common_data_server"]["value"]."prebuild/{$module_syswords_var}.php");
			foreach($template_files as $template_file)
			{
				if ( !is_file( $template_file["name"] ) ) continue;
				
				// Извлекаем из файла шаблона список присутствующих в нем областей
				$template_content = file_get_contents( $template_file['name'] );
				preg_match_all( '/\{\$areas\.([a-z0-9_]+)\}/isU', $template_content, $matches, PREG_SET_ORDER );
				$template_areas = lib::array_reindex( $matches, 1 );
				
				// Выделяем из полного массива областей только те области, которые присутствуют в даннном шаблоне
				$template_areas_with_block = array();
				if ( count( $areas_with_block ) )
					foreach( $areas_with_block as $area )
						if ( isset( $template_areas[$area['SYSTEM_NAME']] ) )
							$template_areas_with_block[] = $area;
				
				// Вгенеряем в шаблон заголовка выбранные области, их параметры и данные о разделе
				$header_tpl=new smarty_ee();
				$header_tpl->left_delimiter="{{";
				$header_tpl->right_delimiter="}}";
				$header_tpl->assign($common_header_params);
				$header_tpl->assign("areas", $template_areas_with_block);
				/*$header_tpl->assign("TOOLBAR_INFO", "rbccontents5_ee|".md5(params::$params["adm_htdocs_http"]["value"]));
				$header_tpl->assign("TOOLBAR_PAGE_INFO", "params=".$page[0]["PAGE_ID"]."|".$page[0]["SITE_ID"]."|".$page[0]["PARENT_ID"]."|".$page[0]["LANG_ID"]);
				*/
				$page_header = $header_tpl->fetch(dirname(__FILE__)."/page_header_template.php");
				
				$tpl=new smarty_ee($$module_syswords_var);
				$tpl->current_lang=$page[0]["ROOT_DIR"];
				$tpl->assign("header", $page_header);
				$tpl->assign("title", "<?"."php echo \$title; ?".">");
				$tpl->assign("keywords", "<?"."php echo \$keywords; ?".">");
				$tpl->assign("description", "<?"."php echo \$description; ?".">");
				$tpl->assign("areas", $areas_echo_body);
				$tpl->assign("page", $page[0]);
				
				$page_body = $tpl->fetch($template_file["name"]);
				if ( $page_body !== "" )
					if ( !file_put_contents($page_path.$template_file["pure_name"], $page_body) ) {
						echo $page_path.$template_file["pure_name"];
						throw new Exception( $this -> get_generate_page_message( $page[0], false ) );
					}
				unset($header_tpl);
				unset($tpl);
			}
		}
	}

	/**
	 * Метод возвращет сообщение о результате генерации страницы
	 *
	 * @param array $page			Описание раздела
	 * @param boolean $result		Флаг успешности операции
	 * @return string
	 */
	public function get_generate_page_message( $page, $result = true )
	{
		$page_name = htmlspecialchars($page["TITLE"]." (".($page["VERSION"] ? metadata::$lang["lang_test_version"] : metadata::$lang["lang_work_version"]).")", ENT_QUOTES);
		return $page_name . ': ' . ( $result ? metadata::$lang["lang_page_refresh_success"] : metadata::$lang["lang_page_refresh_error"] );
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Наследник диспетчера, обеспечивает блокирование только для metadata_change, в другом случае выводим сообщение об ошибке
	* 
	* @todo В table дополнительно проверяется is_checkinout_table, а здесь нет. Привести к единому механизму.
	* @see table::dispatcher
	*/

	public function dispatcher () {
		$method_name=preg_replace("/[^a-z0-9_]+/i", "", $_REQUEST["action"]);
		
		if (in_array($method_name, $this->full_object->get_lock_actions())) {
			$pk = $this->primary_key->get_from_request(true);
			if ($method_name!='meta_change') 
				$this->is_permitted_to('change', $pk, true);
		}
        
        $this->call_parent('dispatcher');
	}		

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Добавляем колонку с ссылками на раскладку блоков
	 */
	public function ext_index_header( $mode )
	{
		$headers = $this -> call_parent( 'ext_index_header', array( $mode ) );
		
		$headers = array_merge( $headers,
			array( 'link_to_block' => array( 'title' => metadata::$lang['lang_blocks'], 'type'=>'_link' ) ) );
		
		return $headers;
	}
	
	/**
	 * Добавляем колонки с ссылками на материалы, рабочую и тестовую версии раздела
	 */
	public function get_index_header( $mode )
	{
		$headers = $this -> call_parent( 'get_index_header', array( $mode ) );
		
		if ( $mode == 'tree' )
			$headers = array_merge( $headers,
				array( 'link_to_block' => array( 'title' => metadata::$lang['lang_block'], 'type' => '_link' ) ),
				array( 'link_to_module' => array( 'title' => metadata::$lang['lang_module'], 'type' => 'text' ) ),
				array( 'link_to_preview' => array( 'title' => metadata::$lang['lang_preview'], 'type' => '_list' ) ) );
		
		return $headers;
	}
	
	/**
	 * Добавляем ссылки на раскладку блоков и на редактирование метаданных
	 */
	public function get_index_ops( $record )
	{
		$ops = parent::get_index_ops( $record );
		$pk = $this -> primary_key -> get_from_record( $record );
		
		// Добавляем ссылку на вкладку редактирования блоков
		if ( $this -> full_object -> is_ops_permited( 'change', $pk[$this -> autoinc_name] ) && $record['PAGE_TYPE'] == 'page' )
			$ops = array_merge( array( 'link_to_block' => array( 'url' => $record['BLOCK_LINK'] ) ), $ops );
		
		// Добавляем ссылку на вкладку редактирования метатегов
		if ( !$this -> full_object -> is_ops_permited( 'change', $pk[$this -> autoinc_name] ) &&
				$this -> full_object -> is_ops_permited( 'meta_change', $pk[$this -> autoinc_name] ) && $record['PAGE_TYPE'] == 'page' )
			$ops['_ops'] = array_merge( array( array( 'name' => 'change', 'alt' => metadata::$lang['lang_change'],
				'url' => $this -> url -> get_url( 'meta_change', array( 'pk' => $pk ) ) ) ), $ops['_ops'] );
		
		return $ops;
	}

	/**
	 * Не выводим ссылки в списках select2
	 *
	 * @see table::index_by_list_mode()
	 * @todo убедиться, что у нас нет таких select2 с разделами, которым нужны были бы ссылки
	 */
	public function ext_index_by_list_mode($mode, $list_mode){
		list($where, $binds)=$this->call_parent('ext_index_by_list_mode', array($mode, $list_mode));
		// Фильтрация списка разделов по типу страницы (выкидываются ссылки)
		if($list_mode["no_links"]){
			$where .= " AND PAGE.PAGE_TYPE <> 'link' ";
		}
		// Фильтрация списка разделов по сайту
		if($list_mode["SITE_ID"]){
			$where .= " AND PAGE.SITE_ID = :list_mode_site_id ";
			$binds += array( "list_mode_site_id" => $list_mode["SITE_ID"] );
		}
		return array($where, $binds);
	}

	/**
	 * Получение потушенных чекбоксов для привязки раздачи прав на блоки
	 *
	 * @param int $primary_id	идентификатор записи из первичной таблицы (системной роли)
	 * @return array
	 */
	public function ext_disabled_ACL_PAGE($primary_id){
		return auth::get_disabled_for_auth($primary_id, $this->index_records_in, "page", "PAGE");
	}

	/**
	 * Получение потушенных чекбоксов для привязки раздачи прав пользователей на разделы
	 *
	 * @param int $primary_id	идентификатор записи из первичной таблицы (системной роли)
	 * @return array
	 */
	public function ext_disabled_CL_CLIENT_PAGE( $primary_id )
	{
		return $this -> get_disabled_pages();
	}

	/**
	 * Получение потушенных чекбоксов для привязки раздачи прав групп пользователей на разделы
	 *
	 * @param int $primary_id	идентификатор записи из первичной таблицы (системной роли)
	 * @return array
	 */
	public function ext_disabled_CL_GROUP_PAGE( $primary_id )
	{
		return $this -> get_disabled_pages();
	}

	/**
	 * Список незащищенных разделов для потушки оных при раздаче прав пользователям и группам пользователей
	 *
	 * @return array
	 */
	public function get_disabled_pages()
	{
		$index_where = $this -> full_object -> ext_index_query();
		$index_binds = $this -> full_object -> ext_index_query_binds();
		
		$not_protected_pages = db::sql_select( 'select PAGE_ID from PAGE where IS_PROTECTED = 0 and PAGE_ID in ( ' . $this -> index_records_in . ' )' . $index_where, $index_binds );
		
		$disabled_pages = array();
		foreach ( $not_protected_pages as $not_protected_page )
			$disabled_pages[$not_protected_page['PAGE_ID']] = 0;
		
		return $disabled_pages;
	}

	/**
	 * Удаляется директория раздела, параметры областей и связанные записи из таблицы AUTH_ACL
	 *
	 * @see table::ext_finalize_delete()
	 */
	public function ext_finalize_delete($pk, $partial=false){
		$this->call_parent('ext_finalize_delete', array($pk, $partial));
		$pages=db::sql_select("SELECT PAGE.* FROM PAGE WHERE PAGE_ID=:page_id", array("page_id"=>$pk["PAGE_ID"]));
		foreach($pages as $key=>$page){
			$pages[$key]["path_to_del"]=$this->get_page_path($page["PAGE_ID"], $page["VERSION"], true, true);
		}
		if($pages[0]["PAGE_TYPE"]!="link"){
			$this->delete_page_params($pk["PAGE_ID"]);
			foreach($pages as $page){
				filesystem::rm_r($page["path_to_del"], true);
			}
		}
		auth::clear_AUTH_ACL( $pk['PAGE_ID'], 'PAGE' );
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Фиксируется поле PAGE_TYPE для того, чтобы его значение было передано методу добавления записи
	 * 
	 * @see table::action_add()
	 * @metadatamod Для выдачи поля PAGE_TYPE меняются метаданные
	 */
	public function action_add(){
		$parent_page=$this->get_change_record(array("PAGE_ID"=>$this->parent_id), false);
		
		metadata::$objects["PAGE"]["fields"]["TEMPLATE_ID"]["list_mode"]=array("LANG_ID"=>$parent_page["LANG_ID"]);
		if ( $this->parent_id )
			metadata::$objects["PAGE"]["fields"]["PARENT_ID"]["list_mode"]["without_root"] = 1;
		
		$page_type=($this->parent_id && in_array($_REQUEST["PAGE_TYPE"], array("page", "folder", "link")))?$_REQUEST["PAGE_TYPE"]:"page";
		
		$this->adjust_page_fields(true, $this->parent_id, $page_type, true);
		metadata::$objects["PAGE"]["fields"]["PAGE_TYPE"]["value_list"]=array(array("title"=>$page_type, "value"=>$page_type, "selected"=>1));
		metadata::$objects["PAGE"]["fields"]["PAGE_TYPE"]["vars"]["display"]="none";
		
		$this->call_parent('action_add');
	}

	/**
	 * Изменяется заполняемость полей
	 * 
	 * @see table::action_copy()
	 * @metadatamod Для выдачи поля PAGE_TYPE меняются метаданные
	 */
	public function action_copy(){
		$source_pk=$this->primary_key->get_from_request(true);
		$source_page=$this->full_object->get_change_record($source_pk);
		$this->adjust_page_fields(true, $source_page["PARENT_ID"], $source_page["PAGE_TYPE"], true);
		metadata::$objects["PAGE"]["fields"]["PARENT_ID"]["list_mode"]=array("LANG_ID"=>$source_page["LANG_ID"], "SITE_ID"=>$source_page["SITE_ID"], "only_this_site"=>1);
		metadata::$objects["PAGE"]["fields"]["TEMPLATE_ID"]["list_mode"]=array("TEMPLATE_ID"=>$source_page["TEMPLATE_ID"], "LANG_ID"=>$source_page["LANG_ID"]);
		metadata::$objects["PAGE"]["fields"]["PAGE_TYPE"]["value_list"]=array(array("title"=>$source_page["PAGE_TYPE"], "value"=>$source_page["PAGE_TYPE"], "selected"=>1));
		metadata::$objects["PAGE"]["fields"]["PAGE_TYPE"]["vars"]["display"]="none";
		
		if ( $source_page["PARENT_ID"] )
			metadata::$objects["PAGE"]["fields"]["PARENT_ID"]["list_mode"]["without_root"] = 1;
		$this->call_parent('action_copy');
	}

	/**
	 * Делается подсветка шаблонов относительно текущего шаблона
	 * 
	 * @see table::action_change()
	 * @metadatamod шаблоны подсвечиваются
	 */
	public function action_change()
	{
		$pk=$this->primary_key->get_from_request(true);
		$page=$this->get_change_record($this->primary_key->get_from_request());
		$this->adjust_page_fields(true, $page["PARENT_ID"], $page["PAGE_TYPE"]);
		metadata::$objects["PAGE"]["fields"]["PARENT_ID"]["list_mode"]=array("LANG_ID"=>$page["LANG_ID"], "SITE_ID"=>$page["SITE_ID"]);
		metadata::$objects["PAGE"]["fields"]["TEMPLATE_ID"]["list_mode"]=array("TEMPLATE_ID"=>$page["TEMPLATE_ID"], "LANG_ID"=>$page["LANG_ID"]);
		if ( $page["PARENT_ID"] )
			metadata::$objects["PAGE"]["fields"]["PARENT_ID"]["list_mode"]["without_root"] = 1;
		
		// Для папок и страниц отменяем внесенные ранее изменения метаданных.
		// Дополнительные проверки и скрытия ненужных полей производятся на клиенте.
		// Разрешаем также менять тип раздела с "папки" на "страницу" и наоборот.
		if ( $page['PAGE_TYPE'] != 'link' )
		{
			$this -> change_field_visibility( array( 'TEMPLATE_ID', 'IS_TITLE_SHOWED', 'IS_MENU_PUBLISHED', 'PAGE_TYPE' ), 0, 0 );
			$this -> add_field_error( array( 'PAGE_TYPE', 'TEMPLATE_ID' ), _nonempty_ );
			metadata::$objects['PAGE']['fields']['PAGE_TYPE']['value_list'] = array(
				array( 'title' => metadata::$lang['lang_page_type_page'], 'value' => 'page' ),
				array( 'title' => metadata::$lang['lang_page_type_folder'], 'value' => 'folder' ) );
		}
		
		$this->call_parent('action_change');
	}

	/**
	 * Действие - карточка массового перемещения записей
	 * 
	 * @see table::action_group_move()
	 */
	public function action_group_move(){
		$group_pks=$this->primary_key->get_group_from_request();
		foreach( $group_pks[0]["pk"] as $pk_name => $pk_value )
			$request[$pk_name] = $pk_value;
		
		$pk=$this->primary_key->get_from_record($request);
		$page=$this->full_object->get_change_record($pk, true);
		
		metadata::$objects["PAGE"]["fields"]["PARENT_ID"]["list_mode"]=array("LANG_ID"=>$page["LANG_ID"], "SITE_ID"=>$page["SITE_ID"]);
		
		$this->call_parent('action_group_move');
	}

	/**
	 * Действие - Смотреть на раскладку блоков и параметры блоков
	 */
	public function action_block(){
		// Получаем необходимые данные из БД
		$pk=$this->primary_key->get_from_request(true);
		$this->full_object->is_permitted_to("change", $pk, true);
		$page=$this->full_object->get_change_record($pk);
		$areas=$this->full_object->get_page_areas($page["PAGE_ID"], $page["VERSION"]);
		$r_areas=lib::array_reindex($areas, "TEMPLATE_AREA_ID");
		$template_type=db::sql_select("SELECT HTML_MAP FROM TEMPLATE_TYPE WHERE TEMPLATE_TYPE_ID=:template_type_id", array("template_type_id"=>$areas[0]["TEMPLATE_TYPE_ID"]));
		// Определяемся с областью, на которую мы смотрим
		if($r_areas[$_REQUEST["TEMPLATE_AREA_ID"]]){
			$selected_area=$_REQUEST["TEMPLATE_AREA_ID"];
		}else{
			$selected_area=$areas[0]["TEMPLATE_AREA_ID"];
		}
		$cur_area=$r_areas[$selected_area];
		// Помещаем компоненты раскладки в обрамляющий шаблон раскладки
		$block_tpl=new smarty_ee(metadata::$lang);
		$block_tpl->assign("html_map", $this->html_map($pk, $template_type[0]["HTML_MAP"], $areas, $selected_area));
		$block_tpl->assign("block_change", $this->html_block_change($pk, $cur_area));
		$block_tpl->assign("block_params", $this->html_block_params($page, $cur_area));
		$block_tpl->assign("cur_area", $cur_area);
		
		if (!$this->is_record_blocked($pk, false)) {
			$inf_block_object = object::factory( 'INF_BLOCK' );
			if ( $inf_block_object -> is_permitted_to("add") )
				$block_tpl->assign("add_block_link", lib::make_request_uri( array( 'action' => 'block_add', 'TEMPLATE_AREA_ID' => $cur_area['TEMPLATE_AREA_ID']	) ) );
			$inf_block_object -> __destruct();
			if ( $this->auth->is_main_admin )
				$block_tpl->assign("copy_block_link", lib::make_request_uri( array( 'action' => 'block_copy', 'TEMPLATE_AREA_ID' => $cur_area['TEMPLATE_AREA_ID'] ) ) );
			if ( $cur_area['INF_BLOCK_ID'] )
				$block_tpl->assign("unlink_block_link", lib::make_request_uri( array( 'action' => 'block_unlink', 'TEMPLATE_AREA_ID' => $cur_area['TEMPLATE_AREA_ID'] ) ) );
		}
		
		$form = $block_tpl->fetch($this->tpl_dir."cms/page/block.tpl");
		
		$title = metadata::$lang["lang_change_page_blocks"];
		$card_title = $this -> full_object -> get_record_title( $pk );
		
		$this -> path_id_array[] = array( 'TITLE' => $card_title );
		
		// Дополняем статусную строку путем от текущего уровня до корня
		if ( metadata::$objects[$this->obj]['parent_field'] && $this -> parent_id )
			$this -> path_id_array = array_merge( $this -> path_id_array,
				$this -> full_object -> get_parents_list( $this -> full_object -> get_parent_info( $this -> parent_id ) ) );
		
		$tpl = new smarty_ee( metadata::$lang );

		$tpl -> assign( 'title', $card_title );
		$tpl -> assign( 'tabs', $this -> get_header_tabs( $pk, 'block' ) );
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $this -> get_record_operations(), 'label' => 'top' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl') );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $this -> get_record_operations(), 'label' => 'bottom' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl') );
		$tpl -> assign( 'form',	$form );
		$tpl -> assign('block_forms', array('form_block', 'form_1'));
		$this->set_blocked_tpl_params ($tpl, $pk);
		$this -> title = $title;
		$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' );
	}

	/**
	 * Действие - Сменить блок в области раздела
	 */
	public function action_block_change(){
		$pk=$this->primary_key->get_from_request(true);
		$this->full_object->exec_block_change($pk, $_REQUEST["TEMPLATE_AREA_ID"], $_REQUEST["_form_INF_BLOCK_ID"], true);
		$pk+=array("TEMPLATE_AREA_ID"=>$_REQUEST["TEMPLATE_AREA_ID"]);
		$this->url->redirect("block", array("pk"=>$pk));
	}

	/**
	 * Карточка создания нового блока и привязки его к заданной области
	 */
	public function action_block_add()
	{
		$pk=$this->primary_key->get_from_request(true);
		$this->full_object->is_permitted_to("change", $pk, true);
		$page=$this->full_object->get_change_record($pk);
		
		// Проверка права на добавление блоков
		$inf_block_object = object::factory( 'INF_BLOCK' );
		$inf_block_object -> is_permitted_to("add", "", true);
		
		// Право задавать сайт новому блоку есть только у главного администратора
		metadata::$objects['INF_BLOCK']['fields']['SITE_ID']['no_add'] = !$this->auth->is_main_admin;
		
		// Таким вот варварским методом меняем местами поля PRG_MODULE_ID и WF_WORKFLOW_ID
		metadata::$objects['INF_BLOCK']['fields'] = array(
			'INF_BLOCK_ID' => metadata::$objects['INF_BLOCK']['fields']['INF_BLOCK_ID'],
			'TITLE' => metadata::$objects['INF_BLOCK']['fields']['TITLE'],
			'TE_OBJECT_ID' => metadata::$objects['INF_BLOCK']['fields']['TE_OBJECT_ID'],
			'PRG_MODULE_ID' => metadata::$objects['INF_BLOCK']['fields']['PRG_MODULE_ID'], 
			'WF_WORKFLOW_ID' => metadata::$objects['INF_BLOCK']['fields']['WF_WORKFLOW_ID'],
			'CACHE_TIME' => metadata::$objects['INF_BLOCK']['fields']['CACHE_TIME'],
			'SITE_ID' => metadata::$objects['INF_BLOCK']['fields']['SITE_ID'] );
		
		// Выкидываем из списка модули без элементов контента, для которых уже существуют блоки
		metadata::$objects['INF_BLOCK']['fields']['PRG_MODULE_ID']['list_mode']['with_elements'] = 1;
		
		// Составляем список модулей с цепочками публикаций по блокам
		$prg_module_list = db::sql_select( 'select PRG_MODULE_ID, SYSTEM_NAME from PRG_MODULE' );
		
		$module_workflow = array();
		foreach ( $prg_module_list as $prg_module_item )
			if ( isset( metadata::$objects[$prg_module_item['SYSTEM_NAME']]['decorators']['workflow'] ) &&
					metadata::$objects[$prg_module_item['SYSTEM_NAME']]['workflow_scope'] == 'block' )
				$module_workflow[] = "'" . $prg_module_item['PRG_MODULE_ID'] . "': true";
		
		// Собираем форму добавления блока на основнании метаданных таблицы INF_BLOCK
		$form_name = html_element::get_next_form_name();
		
		$block_field = html_element::html_fields($inf_block_object->get_form_fields("add", "_form_",
			array('CACHE_TIME'=>params::$params['default_cache_time']['value'],
				'WF_WORKFLOW_ID' => $this -> get_default_workflow_id(),
				'SITE_ID' => $page['SITE_ID']), "", metadata::$objects['INF_BLOCK']['fields']),
			$this->tpl_dir."core/html_element/html_fields.tpl", $this->field);
		$inf_block_object -> __destruct();
		
		$block_add = html_element::html_form( $block_field,
			$this->url->get_hidden("block_added", array("pk"=>array_merge($pk, array("TEMPLATE_AREA_ID"=>$_REQUEST["TEMPLATE_AREA_ID"])))),
			$this->tpl_dir."core/html_element/html_form.tpl", true);
		
		$operations = $this -> get_record_operations( $form_name );
		
		$title = metadata::$lang['lang_blocks'];
		$card_title = $this -> full_object -> get_record_title( $pk );
		
		$this -> path_id_array[] = array( 'TITLE' => $card_title );
		
		// Дополняем статусную строку путем от текущего уровня до корня
		if ( metadata::$objects[$this->obj]['parent_field'] && $this -> parent_id )
			$this -> path_id_array = array_merge( $this -> path_id_array,
				$this -> full_object -> get_parents_list( $this -> full_object -> get_parent_info( $this -> parent_id ) ) );
		
		// Выводим в шаблон информацию о вкладках, заголовке карточки и саму форму
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'title', $card_title );
		$tpl -> assign( 'tabs', $this -> get_header_tabs( $pk, 'block' ) );
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir.'core/html_element/html_operation.tpl') );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir.'core/html_element/html_operation.tpl') );
		$tpl -> assign( 'form', $block_add );
		$tpl -> assign( 'form_name', $form_name );
		
		$this->set_blocked_tpl_params ($tpl, $pk);		
		
		$this -> title = $title;
		
		$form_name = html_element::get_form_name();
		$module_workflow_array = join( ', ', $module_workflow );
		
		$html = <<<HTM
<script type="text/javascript" language="JavaScript">
	var oForm = document.forms['{$form_name}'];
	
	var oModuleWorkflow = { {$module_workflow_array} };
	
	var oModuleSelect = oForm['_form_PRG_MODULE_ID'];
	var oWorkflowSelect = oForm['_form_WF_WORKFLOW_ID'];
	var oWorkflowRow = document.getElementById( '_form_WF_WORKFLOW_ID' );
	
	if ( oModuleSelect && oWorkflowSelect )
	{
		addListener( oModuleSelect, 'change', changeWorkflow ); changeWorkflow();
	}
	
	function changeWorkflow()
	{
		var iModuleId = oModuleSelect.options[ oModuleSelect.selectedIndex ].value;
		
		oWorkflowRow.style.display = oModuleWorkflow[iModuleId] ? '' : 'none';
		oWorkflowSelect.setAttribute( 'lang', '_int_' + ( oModuleWorkflow[iModuleId] ? '_nonempty_' : '' ) );
	}
</script>
HTM;
				
		$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' ) . $html;
	}

	/**
	 * Действие - создание нового блока и привязки его к заданной области
	 */
	public function action_block_added()
	{
		$pk=$this->primary_key->get_from_request(true);
		$this->full_object->is_permitted_to("change", $pk, true);
		$page=$this->full_object->get_change_record($pk);
		
		// Право задавать сайт новому блоку есть только у главного администратора
		if ( !isset( $_REQUEST['_form_SITE_ID'] ) || !$this->auth->is_main_admin )
			$_REQUEST['_form_SITE_ID'] = $page['SITE_ID'];
		
		// Контролируем возможность смены цепочки публикаций
		$prg_module_record = db::sql_select( '
				select SYSTEM_NAME from PRG_MODULE where PRG_MODULE_ID = :prg_module_id',
			array( 'prg_module_id' => $_REQUEST['_form_PRG_MODULE_ID'] ) );
		$module_system_name = $prg_module_record[0]['SYSTEM_NAME'];

		if ( !isset( metadata::$objects[$module_system_name]['decorators']['workflow'] ) ||
				metadata::$objects[$module_system_name]['workflow_scope'] != 'block' )
			metadata::$objects['INF_BLOCK']['fields']['WF_WORKFLOW_ID']['no_add'] = true;
		
		$inf_block_object = object::factory( 'INF_BLOCK' );
		$new_block_id = $inf_block_object -> exec_add( $_REQUEST, '_form_' );
		$inf_block_object -> __destruct();
		
		$pk = $this->primary_key->get_from_request(true);
		$this -> full_object -> exec_block_change($pk, $_REQUEST["TEMPLATE_AREA_ID"], $new_block_id, true);
		$pk += array("TEMPLATE_AREA_ID"=>$_REQUEST["TEMPLATE_AREA_ID"]);
		$this->url->redirect("block", array("pk"=>$pk));
	}

	/**
	 * Карточка формы разнесения блока
	 */
	public function action_block_copy()
	{
		$pk = $this -> primary_key -> get_from_request( true );
		if ( !$this->auth->is_main_admin )
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_operation_not_permitted_block_copy"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")");
		$page = $this -> full_object -> get_change_record( $pk );
		
		$prg_module_obj = object::factory( 'PRG_MODULE' );
		list( $dec_field1, $dec_where_search1, $dec_join1, $dec_binds1 ) =
			$prg_module_obj -> ext_field_selection( 'TITLE', 1 );
		$prg_module_obj -> __destruct();
		
		$template_area_obj = object::factory( 'TEMPLATE_AREA' );
		list( $dec_field2, $dec_where_search2, $dec_join2, $dec_binds2 ) =
			$template_area_obj -> ext_field_selection( 'TITLE', 2 );
		$template_area_obj -> __destruct();
		
		// Получаем информацию о выбранной области и о привязанном к ней блоке
		$cur_area = db::sql_select( '
			select
				PAGE_WITH_AREA.*,
				INF_BLOCK.INF_BLOCK_ID,
				INF_BLOCK.TITLE as INF_BLOCK_TITLE,
				PRG_MODULE.PRG_MODULE_ID,
				' . $dec_field1 . ' as PRG_MODULE_TITLE
			from
				(select
					PAGE.PAGE_ID, PAGE.VERSION,
					PAGE.TITLE as PAGE_TITLE,
					TEMPLATE_AREA.TEMPLATE_AREA_ID,
					' . $dec_field2 . ' as TEMPLATE_AREA_TITLE,
					TEMPLATE_AREA.SYSTEM_NAME as TEMPLATE_SYSTEM_NAME
				from
					PAGE, TEMPLATE, TEMPLATE_AREA_MAP, TEMPLATE_AREA
					' . $dec_join2[0] . '
				WHERE
					PAGE.PAGE_ID = :page_id and PAGE.VERSION = :version
					and TEMPLATE_AREA.TEMPLATE_AREA_ID = :template_area_id
					and TEMPLATE_AREA.TEMPLATE_AREA_ID = TEMPLATE_AREA_MAP.TEMPLATE_AREA_ID
					and TEMPLATE_AREA_MAP.TEMPLATE_TYPE_ID = TEMPLATE.TEMPLATE_TYPE_ID
					and TEMPLATE.TEMPLATE_ID = PAGE.TEMPLATE_ID) as PAGE_WITH_AREA
				left join PAGE_AREA
					on PAGE_AREA.PAGE_ID = PAGE_WITH_AREA.PAGE_ID
						and PAGE_AREA.VERSION = PAGE_WITH_AREA.VERSION
						and PAGE_AREA.TEMPLATE_AREA_ID = PAGE_WITH_AREA.TEMPLATE_AREA_ID
				left join INF_BLOCK
					on PAGE_AREA.INF_BLOCK_ID = INF_BLOCK.INF_BLOCK_ID
				left join PRG_MODULE
					on INF_BLOCK.PRG_MODULE_ID = PRG_MODULE.PRG_MODULE_ID
				' . $dec_join1[0],
			array( 'page_id' => $page['PAGE_ID'], 'version' => $page['VERSION'], 'template_area_id' => $_REQUEST['TEMPLATE_AREA_ID'] ) + $dec_binds1 + $dec_binds2 );
		
		// Вызываем исключение, если такая область не найдена на данной странице
		if ( !count( $cur_area ) )
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_area_not_found"].": \"".htmlspecialchars($this->full_object->get_record_title($pk))."\" (".$this->primary_key->pk_to_string($pk).") : ".metadata::$lang["lang_area"]." (".$_REQUEST['TEMPLATE_AREA_ID'].")");
		
		// Заполняем массив страниц, содержащих заданную область
		$pages_with_area = db::sql_select( '
			select
				PAGE.PAGE_ID, PAGE.VERSION
			from
				PAGE, TEMPLATE, TEMPLATE_TYPE, TEMPLATE_AREA_MAP, TEMPLATE_AREA
			where
				PAGE.TEMPLATE_ID = TEMPLATE.TEMPLATE_ID
				and TEMPLATE_TYPE.TEMPLATE_TYPE_ID = TEMPLATE.TEMPLATE_TYPE_ID
				and TEMPLATE_AREA_MAP.TEMPLATE_TYPE_ID = TEMPLATE_TYPE.TEMPLATE_TYPE_ID
				and TEMPLATE_AREA.TEMPLATE_AREA_ID = TEMPLATE_AREA_MAP.TEMPLATE_AREA_ID
				and TEMPLATE_AREA.TEMPLATE_AREA_ID = :template_area_id',
			array( 'template_area_id' => $_REQUEST['TEMPLATE_AREA_ID'] ) );
		
		// Заполняем массив страниц, содержащих в заданной области какой-нибудь блок
		$pages_with_block = db::sql_select( '
			select
				PAGE_AREA.PAGE_ID, PAGE_AREA.VERSION,
				INF_BLOCK.INF_BLOCK_ID,
				INF_BLOCK.TITLE as INF_BLOCK_TITLE,
				PRG_MODULE.PRG_MODULE_ID,
				' . $dec_field1 . ' as PRG_MODULE_TITLE
			from 
				PAGE_AREA, INF_BLOCK, PRG_MODULE
				' . $dec_join1[0] . '
			where
				PAGE_AREA.TEMPLATE_AREA_ID = :template_area_id
				and INF_BLOCK.INF_BLOCK_ID = PAGE_AREA.INF_BLOCK_ID
				and PRG_MODULE.PRG_MODULE_ID = INF_BLOCK.PRG_MODULE_ID',
			array( 'template_area_id' => $_REQUEST['TEMPLATE_AREA_ID'] ) + $dec_binds1 );
		
		// Получаем массив параметров типа "раздел" модуля блока, привязанного к заданной области
		$page_module_params = $this -> get_page_module_params( $page['PAGE_ID'], $page['VERSION'], $_REQUEST['TEMPLATE_AREA_ID'] );
		
		// Получаем дополнительные ограничения по версиям разделов
		$version_where_clause = $this -> full_object -> version_where_clause();
		$version_where_clause_binds = $this -> full_object -> version_where_clause_binds();
		
		// Получаем список всех разделов (кроме ссылок) на выбранном языке
		$page_list = db::sql_select( '
			select * from PAGE where LANG_ID = :lang_id and PAGE_TYPE <> :page_type ' . $version_where_clause,
			array( 'lang_id' => $page['LANG_ID'], 'page_type' => 'link' ) + $version_where_clause_binds );
		
		// Получаем массив разделов, сгруппированный по номеру раздела
		$page_list = lib::array_reindex( $page_list, 'PAGE_ID' );
		
		// Получаем список разделов, сгруппированный по родителю и номеру раздела
		$parent_list = lib::array_group( $page_list, 'PARENT_ID' );
		
		// В массиве страниц проставляем признак совпадения области
		foreach( $pages_with_area as $pwa )
			if ( $page_list[$pwa['PAGE_ID']]['VERSION'] == $pwa['VERSION'] )
				$page_list[$pwa['PAGE_ID']]['THIS_AREA'] = 1;
		
		// В массив страниц добавляем информацию о блоке и модуле
		foreach ( $pages_with_block as $pwb )
			if ( $page_list[$pwb['PAGE_ID']]['VERSION'] == $pwb['VERSION'] )
				$page_list[$pwb['PAGE_ID']] +=
					array( 'INF_BLOCK_ID' => $pwb['INF_BLOCK_ID'], 'INF_BLOCK_TITLE' => $pwb['INF_BLOCK_TITLE'],
						'PRG_MODULE_ID' => $pwb['PRG_MODULE_ID'], 'PRG_MODULE_TITLE' => $pwb['PRG_MODULE_TITLE'] );
		
		// Строим дерево разделов, используя библиотечную функцию
		$page_tree = get_tree::get( $page_list, 'PAGE_ID', 'PARENT_ID', 'PAGE_ORDER' );
		
		// Бежим по полученному дереву, добавляя необходимую информацию
		$counter = 1;
		foreach ( $page_tree as &$record )
		{
			// Счетчик разделов
			$record['_number'] = $counter++;
			
			$checkbox = true;
			if ( $record['PAGE_ID'] == $page['PAGE_ID'] ) // Та же страница
			{
				$record['SELECTED'] = 1; $record['class'] = 'block_this_page'; $checkbox = false;
			}
			elseif ( $record['PRG_MODULE_ID'] && $record['PRG_MODULE_ID'] == $cur_area[0]['PRG_MODULE_ID'] ) // Тот же модуль (зеленый)
			{
				$record['class'] = 'block_this_module';
			}
			elseif ( $record['PRG_MODULE_ID'] && $record['PRG_MODULE_ID'] != $cur_area[0]['PRG_MODULE_ID'] ) // Другой модуль (красный)
			{
				$record['class'] = 'block_other_module';
			}
			elseif ( !$record['PRG_MODULE_ID'] && !$cur_area[0]['PRG_MODULE_ID'] ) // Пустая область
			{
				$record['class'] = 'block_empty_area'; $checkbox = false;
			}
			elseif ( $record['THIS_AREA'] == 1 ) // Та же область (желтый)
			{
				$record['class'] = 'block_this_area';
			}
			else // Область отсутствует
			{
				$record['class'] = 'block_not_area'; $checkbox = false;
			}
			
			// Чекбокс появляется не для всех разделов, только для избранных
			if ( $checkbox )
				$record['_group'] = array( 'id' => $record['PAGE_ID'] . '_' . $record['VERSION'] );
			
			// Иконки разделов выбираются в зависимости от типа раздела и наличия у него потомков
			if ( $record['PAGE_TYPE'] == 'link' )
				$record['_icon'] = 'link';
			elseif ( $record['PAGE_TYPE'] == 'folder' )
				$record['_icon'] = isset( $parent_list[$record['PAGE_ID']] ) ? 'folder' : 'leaf_folder';
			elseif ( $record['PAGE_TYPE'] == 'page' )
				$record['_icon'] = isset( $parent_list[$record['PAGE_ID']] ) ? 'page' : 'leaf_page';
			
			// Выводится в шаблон информация о блоке и модуле
			$record['BLOCK'] = $record['INF_BLOCK_TITLE'] . ( $record['PRG_MODULE_TITLE'] ? ' (' . $record['PRG_MODULE_TITLE'] . ')' : '' );
		}
		
		$headers = array(
			'_number' => array ( 'title' => 'N' ),
			'TITLE' => array ( 'title' => metadata::$lang['lang_name'], 'is_main' => '1' ),
			'BLOCK' => array ( 'title' => metadata::$lang['lang_inf_block'] . ' (' . metadata::$lang['lang_module'] . ')' ),
			'_group' => array( 'title' => '', 'type' => '_group' ) );
		
		$page_header_tpl = new smarty_ee( metadata::$lang );
		$page_header_tpl -> assign( $cur_area[0] );
		$page_header_tpl -> assign( 'page_param_exists', count( $page_module_params ) ? 1 : 0 );
		$html_page_header = $page_header_tpl -> fetch( $this -> tpl_dir . 'cms/page/block_copy.tpl' );
		
		$html_table_body = html_element::html_table( array( 'header' => $headers, 'list' => $page_tree, 'counter' => $counter - 1, 'html_hidden' => $this -> url -> get_hidden( 'block_copied', array( 'pk' => array( 'for_subtrees' => '0', 'all_version' => '1' ) ) ) ), $this -> tpl_dir . 'core/html_element/html_table.tpl', $this -> field );
		
		$operations = array();
		if (!$this->is_record_blocked($pk, false)) 
			$operations[] = array( 'name' => 'apply', 'alt' => metadata::$lang['lang_action_apply'], "onClick"=>"javascript:if ( CheckFill() ) {remove_unblock_record (); document.forms['checkbox_form'].submit() };  return false" );

		$operations = array_merge( $operations, $this -> get_record_operations() );
		
		$title = metadata::$lang['lang_copy_block'];
		$card_title = $this -> full_object -> get_record_title( $pk );
		
		$this -> path_id_array[] = array( 'TITLE' => $card_title );
		
		// Дополняем статусную строку путем от текущего уровня до корня
		if ( metadata::$objects[$this->obj]['parent_field'] && $this -> parent_id )
			$this -> path_id_array = array_merge( $this -> path_id_array,
				$this -> full_object -> get_parents_list( $this -> full_object -> get_parent_info( $this -> parent_id ) ) );
		
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'title', $card_title );
		$tpl -> assign( 'tabs', $this -> get_header_tabs( $pk, 'block' ) );
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir.'core/html_element/html_operation.tpl') );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir.'core/html_element/html_operation.tpl') );
		$tpl -> assign( 'form', $html_page_header . $html_table_body );
		$tpl -> assign( 'form_name', 'checkbox_form' );
		
		$this -> set_blocked_tpl_params( $tpl, $pk );
		
		$this -> title = $title;
		$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' );
	}

	/**
	 * Действие - разнесение блока
	 */
	public function action_block_copied()
	{
		$pk = $this -> primary_key -> get_from_request( true );
		if ( !$this->auth->is_main_admin )
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_operation_not_permitted_block_copy"].": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")");
		$page = $this -> full_object -> get_change_record( $pk );
		
		// Получаем массив параметров типа "раздел" модуля блока, привязанного к заданной области
		$page_module_params = $this -> get_page_module_params( $page['PAGE_ID'], $page['VERSION'], $_REQUEST['TEMPLATE_AREA_ID'] );
		
		// Получаем информацию о выбранной области
		$cur_area = db::sql_select( '
			select
				PAGE_AREA.*
			from
				PAGE_AREA
			where
				PAGE_AREA.PAGE_ID = :page_id
				and PAGE_AREA.VERSION = :version
				and PAGE_AREA.TEMPLATE_AREA_ID = :template_area_id',
			array( 'page_id' => $page['PAGE_ID'], 'version' => $page['VERSION'], 'template_area_id' => $_REQUEST['TEMPLATE_AREA_ID'] ) );
		
		// Получаем информацию о параметрах выбранной области
		$cur_area_params = db::sql_select( '
			select
				PAGE_AREA_PARAM.*
			from
				PAGE_AREA_PARAM
			where
				PAGE_AREA_PARAM.PAGE_ID = :page_id
				and PAGE_AREA_PARAM.VERSION = :version
				and PAGE_AREA_PARAM.TEMPLATE_AREA_ID = :template_area_id',
			array( 'page_id' => $page['PAGE_ID'], 'version' => $page['VERSION'], 'template_area_id' => $_REQUEST['TEMPLATE_AREA_ID'] ) );
		
		// Делаем копию массива параметров, в которой параметры типа "раздел" оставляем пустыми
		$cur_area_params_other_site = $cur_area_params;
		foreach ( $cur_area_params_other_site as &$cur_area_param )
			if( is_array( $page_module_params[ $cur_area_param['MODULE_PARAM_ID'] ] ) )
				$cur_area_param['VALUE'] = '';
		
		// Получаем список всех разделов на выбранном языке
		$pages_all = db::sql_select( '
			select * from PAGE where LANG_ID = :lang_id and PAGE_TYPE <> :page_type',
			array( 'lang_id' => $page['LANG_ID'], 'page_type' => 'link' ) );
		
		// Получаем список шаблонов, которые содержат выбранную область
		$templates_allow = db::sql_select( '
			select
				TEMPLATE.TEMPLATE_ID
			from
				TEMPLATE, TEMPLATE_AREA_MAP
			where
				TEMPLATE.TEMPLATE_TYPE_ID = TEMPLATE_AREA_MAP.TEMPLATE_TYPE_ID and
				TEMPLATE_AREA_MAP.TEMPLATE_AREA_ID = :template_area_id',
			array( 'template_area_id' => $_REQUEST['TEMPLATE_AREA_ID'] ) );
		$templates_allow = lib::array_reindex( $templates_allow, 'TEMPLATE_ID' );
		
		// Получаем массив разделов, сгруппированный по номеру и версии раздела
		$this -> page_id_array = lib::array_reindex( $pages_all, 'PAGE_ID', 'VERSION' );
		// Получаем список разделов, сгруппированный по родителю и номеру раздела
		$this -> parent_id_array = lib::array_reindex( $pages_all, 'PARENT_ID', 'PAGE_ID' );
		
		// Собираем массив разделов, область которых нужно вставить выбранный блок
		$page_id_version_array = array();
		
		// За основу берем список идентификаторов разделов и версий, переданных в запросе
		foreach ( $this -> primary_key -> get_group_from_request() as $group_pk )
		{
			list( $page_id, $version ) = split( '_', $group_pk['pk']['PAGE_ID'] );
			
			$page_id_version_array[$page_id][$version] = 1;
			
			// В случае установленного флажка "Все версии" по возможности добавляем в массив рабочую версию раздела
			if ( $_REQUEST['all_version'] && $version == 1 && isset( $this -> page_id_array[$page_id][0] ) )
				$page_id_version_array[$page_id][0] = 1;
			
			// В случае установленного флажка "Поддеревья" добавляем в массив информацию о потомках
			if ( $_REQUEST['for_subtrees'] )
				$page_id_version_array += $this -> get_subtrees( $page_id, $_REQUEST['all_version'] );
		}
		
		// Определяем права на изменение и публикацию разделов
		$page_ids = array_keys( $page_id_version_array );
		
		$errors = array();
		foreach ( $page_id_version_array as $page_id => $versions )
		{
			foreach ( $versions as $version_id => $version_value )
			{
				// Пропускаем страницы, с шаблоном, тип которого отличается от типа шаблона текущей страницы
				if ( !isset( $templates_allow[$this -> page_id_array[$page_id][$version_id]['TEMPLATE_ID']] ) )
					continue;
				
				// Составляем уникальный идентификатор заданной области текущего раздела
				$insert_pk = array(
					'PAGE_ID' => $page_id, 'VERSION' => $version_id, 'TEMPLATE_AREA_ID' => $_REQUEST['TEMPLATE_AREA_ID'] );
				
				// Удаление прежних данных о заданной области текущего раздела
				$this -> delete_page_params( $insert_pk['PAGE_ID'], $insert_pk['VERSION'], $insert_pk['TEMPLATE_AREA_ID'] );
				
				// Определяем принадлежность страницы текущему сайту
				$this_site = $page['SITE_ID'] == $this -> page_id_array[$page_id][$version_id]['SITE_ID'];
				
				// Вставка информации о параметрах заданной области
				lib::inserter( 'PAGE_AREA_PARAM', $this_site ? $cur_area_params : $cur_area_params_other_site, $insert_pk );
				// Вставка информации о заданной области
				lib::inserter( 'PAGE_AREA', $cur_area, $insert_pk );
				
				// Обновление времени изменения раздела
				db::update_record( 'PAGE', array( 'TIMESTAMP' => time() ), '',
					 array( 'PAGE_ID' => $page_id, 'VERSION' => $version_id ) );
			}
		}
		
		$this -> url -> redirect( 'block_copy', array( 'pk' => $pk + array( 'TEMPLATE_AREA_ID' => $_REQUEST['TEMPLATE_AREA_ID'] ) ) );
	}

	/**
	 * Действие - Назначение параметров блоку в области раздела
	 */
	public function action_block_params(){
		$pk=$this->primary_key->get_from_request(true);
		$this->full_object->exec_block_params($pk, $_REQUEST["TEMPLATE_AREA_ID"], $_REQUEST, "_form_");
		$pk+=array("TEMPLATE_AREA_ID"=>$_REQUEST["TEMPLATE_AREA_ID"]);
		$this->url->redirect("block", array("pk"=>$pk));
	}

	/**
	 * Действие - Отвязать блок от данной области
	 */
	public function action_block_unlink(){
		$pk=$this->primary_key->get_from_request(true);
		$this->full_object->exec_block_unlink($pk, $_REQUEST["TEMPLATE_AREA_ID"]);
		$pk+=array("TEMPLATE_AREA_ID"=>$_REQUEST["TEMPLATE_AREA_ID"]);
		$this->url->redirect("block", array("pk"=>$pk));
	}

	/**
	 * Действие - материалы на странице
	 */
	public function action_content()
	{
		$pk=$this->primary_key->get_from_request(true);
		$this->full_object->is_permitted_to("change", $pk, true);
		$page=$this->full_object->get_change_record($pk);
		
		$prg_module_obj = object::factory( 'PRG_MODULE' );
		list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
			$prg_module_obj -> ext_field_selection( 'TITLE', 1 );
		$prg_module_obj -> __destruct();
		
		// Получаем список информационных блоков в данном разделе
		$content_list = db::sql_select("
			select
				INF_BLOCK.TITLE, INF_BLOCK.INF_BLOCK_ID, INF_BLOCK.PRG_MODULE_ID, PRG_MODULE.SYSTEM_NAME,
				PRG_MODULE.PRG_MODULE_TITLE, count( CONTENT_MAP.CONTENT_ID ) as CONTENT_COUNT
			from
				( select PRG_MODULE.PRG_MODULE_ID, PRG_MODULE.SYSTEM_NAME,
						" . $dec_field . " as PRG_MODULE_TITLE, IS_ELEMENTS
					from PRG_MODULE " . $dec_join[0] . " ) PRG_MODULE,
				PAGE_AREA, INF_BLOCK
			left join CONTENT_MAP on CONTENT_MAP.INF_BLOCK_ID = INF_BLOCK.INF_BLOCK_ID
			where
				PAGE_AREA.INF_BLOCK_ID = INF_BLOCK.INF_BLOCK_ID and
				PRG_MODULE.PRG_MODULE_ID = INF_BLOCK.PRG_MODULE_ID and
				PAGE_AREA.PAGE_ID = :page_id and PAGE_AREA.VERSION = '1' and
				PRG_MODULE.IS_ELEMENTS = '1'
			group by
				INF_BLOCK.TITLE, INF_BLOCK.INF_BLOCK_ID, INF_BLOCK.PRG_MODULE_ID, PRG_MODULE.SYSTEM_NAME, PRG_MODULE.PRG_MODULE_TITLE",
			array( "page_id" => $_REQUEST["PAGE_ID"] ) + $dec_binds );
		
		// Вручную формируем заголовок таблицы материалов
		$headers = array(
			'TITLE' => array( 'title' => metadata::$lang['lang_block_name'], 'type' => '_link', 'is_main' => 1 ),
			'PRG_MODULE_TITLE' => array( 'title' => metadata::$lang['lang_module'], 'type' => 'text' ),
			'CONTENT_COUNT' => array( 'title' => metadata::$lang['lang_page_content_count'], 'type' => 'text' ),
			'_ops' => array( 'title' => metadata::$lang['lang_operations'], 'type' => '_ops' )
		);
		
		// Вручную формируем содержимое таблицы материалов
		include_once(params::$params["adm_data_server"]["value"]."class/te/table/inf_block/inf_block.php");
		foreach ( $content_list as &$content_item )
		{
			$content_url = inf_block::get_link_to_content( $content_item['INF_BLOCK_ID'], $content_item['SYSTEM_NAME'] );
			$content_item['TITLE'] = array( 'title' => $content_item['TITLE'], 'url' => $content_url );
			
			$operation_array = array();
			$operation_array[] = "{ 'title': '" . metadata::$lang['lang_change'] . "', 'image': '/common/adm/img/menu/change.gif', 'object': window, 'method': 'redirect', 'param': { 'url': 'index.php?&obj=INF_BLOCK&action=change&INF_BLOCK_ID={$content_item['INF_BLOCK_ID']}' } }";
			$operation_array[] = "{ 'title': '" . metadata::$lang['lang_delete_cache'] . "', 'image': '/common/adm/img/clear.gif', 'object': window, 'method': 'redirect', 'param': { 'url': '" . lib::make_request_uri( array( 'action' => 'delete_cache', 'INF_BLOCK_ID' => $content_item['INF_BLOCK_ID'] ) ) . "' } }";
			if ( !( metadata::$objects[$content_item['SYSTEM_NAME']]['only_one'] && $content_item['CONTENT_COUNT'] ) )
				$operation_array[] = "{ 'title': '" . metadata::$lang['lang_add'] . "', 'image': '/common/adm/img/menu/add.gif', 'object': window, 'method': 'redirect', 'param': { 'url': '" . inf_block::get_link_to_content( $content_item['INF_BLOCK_ID'], $content_item['SYSTEM_NAME'], 'add' ) . "' } }";
			$content_item['_ops'] = join( ',', $operation_array );
		}
		
		$operations = $this -> get_record_operations();
		
		$title = metadata::$lang['lang_change_page_contents'];
		$card_title = $this -> full_object -> get_record_title( $pk );
		
		$this -> path_id_array[] = array( 'TITLE' => $card_title );
		
		// Дополняем статусную строку путем от текущего уровня до корня
		if ( metadata::$objects[$this->obj]['parent_field'] && $this -> parent_id )
			$this -> path_id_array = array_merge( $this -> path_id_array,
				$this -> full_object -> get_parents_list( $this -> full_object -> get_parent_info( $this -> parent_id ) ) );
		
		// Выводим данные в шаблон
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'title', $card_title );
		$tpl -> assign( 'tabs', $this -> get_header_tabs( $pk, 'content' ) );
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl') );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl') );
		$tpl -> assign( 'form',	html_element::html_table( array( 'header' => $headers, 'list' => $content_list, 'counter' => count( $content_list ) ), $this->tpl_dir . 'core/html_element/html_table.tpl' ) );
		
		$this -> set_blocked_tpl_params( $tpl, $pk );
		
		$this -> title = $title;
		$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' );
	}

	/**
	 * Действие - удаление кэша. После чего возвращаемся к просмотру материалов на странице
	 */
	public function action_delete_cache()
	{
		// Для очитски кэша информационного блока используем соответствующий метод родного объекта
		$inf_block_obj = object::factory( 'INF_BLOCK' );
		$inf_block_obj -> exec_delete_cache( array( 'INF_BLOCK_ID' => $_REQUEST['INF_BLOCK_ID'] ) );
		$inf_block_obj -> __destruct();
		
		$pk = $this -> primary_key -> get_from_request();
		$this -> url -> redirect( 'content', array( 'pk' => $pk ) );
	}
	
	/**
	 * Действие - форма редактирования метатегов
	 */
	public function action_meta_change()
	{
		$pk = $this -> primary_key -> get_from_request( true );
		$this -> full_object -> is_permitted_to( 'meta_change', $pk, true );
		$page = $this -> get_change_record( $pk );
		
		$title = metadata::$lang['lang_metatags'];
		$card_title = $this -> full_object -> get_record_title( $pk );
		
		$this -> path_id_array[] = array( 'TITLE' => $card_title );
		
		// Дополняем статусную строку путем от текущего уровня до корня
		if ( metadata::$objects[$this->obj]['parent_field'] && $this -> parent_id )
			$this -> path_id_array = array_merge( $this -> path_id_array,
				$this -> full_object -> get_parents_list( $this -> full_object -> get_parent_info( $this -> parent_id ) ) );
		
		foreach ( metadata::$objects['PAGE']['fields'] as $field_name => $field_desc )
			metadata::$objects['PAGE']['fields'][$field_name]['no_change'] =
				!in_array( $field_name, array( 'META_TITLE', 'META_KEYWORDS', 'META_DESCRIPTION' ) );
		
		$versions = $this -> full_object -> get_versions( $pk );
		
		// Если есть тестовая и рабочая версии, то нужно спросить разрешения на публикацию изменений
		if ( $versions[1]['VERSION'] === '0' )
		{
			metadata::$objects['PAGE']['fields']['PUBLISH_METATAGS'] =
				array( 'title' => metadata::$lang['lang_metatags_publish'], 'type'=>'checkbox', 'value' => 1 );
			$page['PUBLISH_METATAGS'] = 1;
		}
		
		$meta_fields = html_element::html_fields(
			$this -> get_form_fields( 'change', '_form_', $page, '', metadata::$objects['PAGE']['fields'] ),
				$this -> tpl_dir . 'core/html_element/html_fields.tpl', $this -> field );
		
		$meta_form = html_element::html_form( $meta_fields,
			$this -> url -> get_hidden( 'meta_changed', array( 'pk' => $pk ) ),
			$this -> tpl_dir . 'core/html_element/html_form.tpl', true );
		
		$form_name = html_element::get_form_name();
		$operations = $this -> get_record_operations( $form_name, 'meta_changed_apply' );
		
		$tpl = new smarty_ee( metadata::$lang );

		$tpl -> assign( 'title', $card_title );
		$tpl -> assign( 'tabs', $this -> get_header_tabs( $pk, 'meta_change' ) );
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl') );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl') );
		$tpl -> assign( 'form',	$meta_form );
		$tpl -> assign( 'form_name', $form_name);
		
		$this -> set_blocked_tpl_params( $tpl, $pk );
		
		$this -> title = $title;
		$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' );
	}
	
	/**
	 * Действие - редактирование метатегов
	 */
	public function action_meta_changed()
	{
		$pk = $this -> primary_key -> get_from_request( true );
		$this -> full_object -> exec_meta_change( $_REQUEST, '_form_', $pk );
		$this -> url -> redirect( '', array( 'restore_params' => 1 ) );
	}
	
	/**
	 * Действие - редактирование метатегов и возврат на страницу редактирования
	 */
	public function action_meta_changed_apply()
	{
		$pk = $this -> primary_key -> get_from_request( true );
		$this -> full_object -> exec_meta_change( $_REQUEST, '_form_', $pk );
		$this -> url -> redirect( 'meta_change', array( 'pk' => $pk ) );
	}
	
	/**
	 * Метод возвращает массив навигационных вкладок для карточки записи
	 * 
	 * @see table::get_header_tabs()
	 */
	public function get_header_tabs( $pk, $mark_select = 'change' )
	{
		$header_tabs = $this -> call_parent( 'get_header_tabs', array( $pk, $mark_select ) );
	
		$page = $this -> get_change_record( $pk );
		
		if ( $page['PAGE_TYPE'] == 'page' )
		{
			// Вкладки "Материалы" и "Блоки"
			if ( $this -> full_object -> is_permitted_to( 'change', $pk ) )
			{
				$header_tabs[] = array( 'title' => metadata::$lang['lang_change_page_contents'], 'url' => $this->url->get_url( 'content', array( 'pk' => $pk ) ), 'active' => $mark_select == 'content' );
				$header_tabs[] = array( 'title' => metadata::$lang['lang_change_page_blocks'], 'url' => $this->url->get_url( 'block', array( 'pk' => $pk ) ), 'active' => $mark_select == 'block' );
			}
			// Вкладка "Метаданные"
			if ( $this -> full_object -> is_permitted_to( 'meta_change', $pk ) )
			{
				$header_tabs[] = array( 'title' => metadata::$lang['lang_metatags'], 'url' => $this->url->get_url( 'meta_change', array( 'pk' => $pk ) ), 'active' => $mark_select == 'meta_change' );
			}
		}
		
		return $header_tabs;
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Дополняем записи иконками, а также модифицируем выдачу select2
	 *
	 * @see table::get_index_records()
	 * @todo для деревьев: есть ли дети можно вычислять из самого массива $records, так наверно побыстрее будет
	 */
	public function get_index_records(&$request, $mode, $list_mode, $include=0, $exclude=array()){
		// Модификация выдачи select2
		if($mode=="select2" && !$list_mode["with_links"]){
			$list_mode["no_links"]=1;
		}
		$records=parent::get_index_records($request, $mode, $list_mode, $include, $exclude);
		
		// Вычисление прав на операцию редактирования метатегов
		if ( $mode == 'index' )
			$this -> not_permited_ids['meta_change'] = $this -> full_object -> is_permitted_to_mass( 'meta_change', $this -> index_records_ids );
		
		if ( count( $records ) > 0 )
		{
			// Сбор дополинтельной информации о записях (типы и сайты)
			$page_info=db::sql_select("SELECT PAGE_ID, PAGE_TYPE, SITE_ID FROM PAGE WHERE PAGE_ID IN ({$this->index_records_in}) ORDER BY VERSION");
			$page_info=lib::array_reindex($page_info, "PAGE_ID");
			
			// Помещение названий иконок в записи
			foreach($records as $record_index => $record){
				$records[$record_index]["PAGE_TYPE"]=$page_info[$record["PAGE_ID"]]["PAGE_TYPE"];
				$records[$record_index]["SITE_ID"]=$page_info[$record["PAGE_ID"]]["SITE_ID"];
				if($records[$record_index]["PAGE_TYPE"]=="link"){
					$records[$record_index]["_icon"]="link";
				}elseif($records[$record_index]["PAGE_TYPE"]=="folder"){
					$records[$record_index]["_icon"]=($record["_icon"]=="page" ? "folder" : "leaf_folder");
				}
			}
			
			if ( $mode == 'index' )
			{
				foreach( $records as $record_index => $record )
				{
					if( $record['PAGE_TYPE'] == 'page' )
					{
						// Добавляем в список записей ссылку на раскладку блоков раздела
						$records[$record_index]['BLOCK_LINK'] =
							$this -> url -> get_url( 'block', array( 'pk' => array( 'PAGE_ID' => $record['PAGE_ID'] ) ) );
					}
				}
			}
			
			if ( ($mode == 'tree') && (!$list_mode['simple_list']) )
			{
				// Сбор информации о контенте в главной области раздела
				$page_contents = db::sql_select( '
					select
						PAGE.PAGE_ID, PAGE_AREA.INF_BLOCK_ID, INF_BLOCK.TITLE, PRG_MODULE.PRG_MODULE_ID, PRG_MODULE.SYSTEM_NAME
					from
						PAGE, TEMPLATE, TEMPLATE_TYPE, TEMPLATE_AREA_MAP, TEMPLATE_AREA, PAGE_AREA, INF_BLOCK
						left join PRG_MODULE on PRG_MODULE.PRG_MODULE_ID = INF_BLOCK.PRG_MODULE_ID
					where
						TEMPLATE.TEMPLATE_ID = PAGE.TEMPLATE_ID and
						TEMPLATE_TYPE.TEMPLATE_TYPE_ID = TEMPLATE.TEMPLATE_TYPE_ID and
						TEMPLATE_AREA_MAP.TEMPLATE_TYPE_ID = TEMPLATE_TYPE.TEMPLATE_TYPE_ID and
						TEMPLATE_AREA.TEMPLATE_AREA_ID = TEMPLATE_AREA_MAP.TEMPLATE_AREA_ID and
						PAGE_AREA.TEMPLATE_AREA_ID = TEMPLATE_AREA.TEMPLATE_AREA_ID and
						PAGE_AREA.PAGE_ID = PAGE.PAGE_ID and PAGE_AREA.VERSION = PAGE.VERSION and
						INF_BLOCK.INF_BLOCK_ID = PAGE_AREA.INF_BLOCK_ID and
						PAGE.PAGE_ID IN (' . $this -> index_records_in . ') and
						PAGE.VERSION = :version and TEMPLATE_AREA.IS_MAIN = :is_main',
					array( 'version' => 1, 'is_main' => 1 ) );
				$page_contents = lib::array_reindex( $page_contents, 'PAGE_ID' );
				
				// Сбор информации об установленных модулях
				$prg_module_obj = object::factory( 'PRG_MODULE' );
				$prg_module_list = lib::array_reindex( $prg_module_obj -> get_index_records( $none, 'select2', '' ), 'PRG_MODULE_ID' );
				$prg_module_obj -> __destruct();
				
				include_once( params::$params['adm_data_server']['value'] . 'class/te/table/inf_block/inf_block.php' );
				
				// Добавляем в список записей ссылку на контент в главной области раздела
				foreach( $records as $record_index => $record )
				{
					if( $record['PAGE_TYPE'] == 'page' && isset( $page_contents[$record['PAGE_ID']] ) )
					{
						$records[$record_index]['link_to_block'] = array( 'title' => $page_contents[$record['PAGE_ID']]['TITLE'], 'url' => ( $link_to_content = inf_block::get_link_to_content( $page_contents[$record['PAGE_ID']]['INF_BLOCK_ID'], $page_contents[$record['PAGE_ID']]['SYSTEM_NAME'] ) ) ? $link_to_content : '' );
						$records[$record_index]['link_to_module'] = $prg_module_list[$page_contents[$record['PAGE_ID']]['PRG_MODULE_ID']]['_TITLE'];
					}
				}
				
				// Сбор информации для построения ссылок на тестовую и рабочую версии разделов
				
				// Получаем информацию о разделах. Группируем по номеру раздела и версии
				$record_list = lib::array_reindex(  db::sql_select( 'select * from PAGE where PAGE_ID in ( ' .
					$this -> index_records_in . ' )' ), 'PAGE_ID', 'VERSION' );
				
				// Получаем информацию о сайтах. Группируем по номеру сайта
				$site_list = lib::array_reindex( db::sql_select( 'select SITE_ID, HOST, TEST_HOST from SITE' ), 'SITE_ID' );
				
				// Строим абсолютные URL отдельно для каждой версией раздела
				foreach( $record_list as $versions )
				{
					foreach( $versions as $record )
					{
						$page_id = $record['PAGE_ID'];
						$path = array( $record_list[$page_id][$record['VERSION']]['DIR_NAME'] );
						
						// Поднимаемся вверх по дереву разделов, запоминаем путь
						while( $parent_id = $record_list[$page_id][$record['VERSION']]['PARENT_ID'] )
							$path[] = $record_list[$page_id = $parent_id][$record['VERSION']]['DIR_NAME'];
						
						// Если успешно достигли корневой записи
						if ( !is_null( $parent_id ) )
						{
							// Добавляем в начало и конец пути URL сайта и index.php
							$path[] = 'http' . ( $_SERVER['HTTPS'] == 'on' ? 's' : '' ) . '://' .
								$site_list[$record['SITE_ID']][$record['VERSION'] ? 'TEST_HOST' : 'HOST'];
							array_unshift( $path, 'index.php' ); 
							
							// Собираем и запоминаем абсолютный URL для данной версии раздела
							$record_list[$record['PAGE_ID']][$record['VERSION']]['PATH'] =
								join( '/', array_reverse( $path ) );
						}
					}
				}
				
				// Добавление информации о ссылках в список записей
				foreach( $records as $record_index => $record )
				{
					if ( $record['PAGE_TYPE'] != 'link' )
					{
						$records[$record_index]['link_to_preview'] = array();
						
						// Ссылка на рабочую версию
						if ( isset( $record_list[$record['PAGE_ID']][0]['PATH'] ) )
							$records[$record_index]['link_to_preview'][] =
								array( 'title' => metadata::$lang['lang_work_version'], 'url' => $record_list[$record['PAGE_ID']][0]['PATH'] );
						
						// Ссылка на тестовую версию
						if ( isset( $record_list[$record['PAGE_ID']][1]['PATH'] ) )
							$records[$record_index]['link_to_preview'][] =
								array( 'title' => metadata::$lang['lang_test_version'], 'url' => $record_list[$record['PAGE_ID']][1]['PATH'] );
					}
				}
			}
		}
		
		return $records;
	}
	
	/**
	 * Подготовка списка операций над записями
	 * 
	 * @return array
	 */
	public function get_index_operations()
	{
		$operations = $this -> call_parent( 'get_index_operations' );
		
		// Заполняем массив операций добавления, для объединения их в одной менюшке
		if ( !metadata::$objects[$this -> obj]['no_add'] && $this -> full_object -> is_permitted_to( 'add', array( 'PARENT_ID' => $this -> parent_id ) ) )
		{
			$add_operations = array();
			
			$add_operations[] = "{ 'title': '" . metadata::$lang['lang_add'] . "', 'image': '/common/adm/img/buttons/add.gif', 'object': window, 'method': 'redirect', 'param': { 'url': '{$this -> url -> get_url('add')}' } }";
			$add_operations[] = "{ 'title': '" . metadata::$lang['lang_add_page'] . "', 'image': '/common/adm/img/buttons/add_page.gif', 'object': window, 'method': 'redirect', 'param': { 'url': '{$this -> url -> get_url('add')}' } }";
			
			if ( $this -> parent_id > 0 )
			{
				$add_operations[] = "{ 'title': '" . metadata::$lang['lang_add_link'] . "', 'image': '/common/adm/img/buttons/add_link.gif', 'object': window, 'method': 'redirect', 'param': { 'url': '{$this -> url -> get_url('add')}&PAGE_TYPE=link' } }";
				$add_operations[] = "{ 'title': '" . metadata::$lang['lang_add_folder'] . "', 'image': '/common/adm/img/buttons/add_folder.gif', 'object': window, 'method': 'redirect', 'param': { 'url': '{$this -> url -> get_url('add')}&PAGE_TYPE=folder' } }";
			}
			$operations['add'] = array( 'name' => 'add', 'menu' => join( ',', $add_operations ) );
		}
		
		if ( !$this -> parent_id ) unset( $operations['group_move'] );
		
		return $operations;
	}

	/**
	 * Для главных страниц проверку не делаем - для них делается спецпроверка непосредственно при добавлении/изменении
	 *
	 * @see table field_group_check()
	 */
	public function field_group_check($prepared_fields, $mode, $pk=array()){
		if($prepared_fields["PARENT_ID"]){
			$this->call_parent('field_group_check', array($prepared_fields, $mode, $pk));
		}
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Раскладка блоков. Сбор собственно раскладки блоков из HTML_MAP типа шаблона
	 *
	 * @param array $pk				первичный ключ раздела
	 * @param string $html_map		HTML_MAP из типа шаблона
	 * @param array $areas			список записей областей с присоединенной к ним дополнительной информацией
	 * @param int $selected_area	идентификатор выбранной области (TEMPLATE_AREA_ID)
	 * @return string
	 */
	protected function html_map($pk, $html_map, $areas, $selected_area)
	{
		$html_map=preg_replace("/<table/i", "<table style=\"width: 100%\"", $html_map);
		
		// Бежим по областям, помещая их отображаемую форму в HTML-карту
		foreach($areas as $area)
		{
			$is_content = $area["INF_BLOCK_ID"];
			$selected = (int) $area["TEMPLATE_AREA_ID"] == $selected_area;
			$params_url = $this->url->get_url("block", array("pk"=>array_merge($pk, array("TEMPLATE_AREA_ID"=>$area["TEMPLATE_AREA_ID"]))));
			
			$area_tpl=new smarty_ee(metadata::$lang);
			$area_tpl->assign("area", $area);
			$area_tpl->assign("selected", $selected);
			$area_tpl->assign("is_content", $is_content);
			if ( $area['IS_ELEMENTS'] ) 
			{
				$content_url = "index.php?obj={$area["PRG_MODULE_SYSTEM_NAME"]}" .
					( metadata::$objects[$area["PRG_MODULE_SYSTEM_NAME"]]['decorators']['block'] ? "&_f_INF_BLOCK_ID={$area["INF_BLOCK_ID"]}" : "" ) .
					( metadata::$objects[$area["PRG_MODULE_SYSTEM_NAME"]]['decorators']['lang'] ? "&_f_LANG_ID={$area["LANG_ID"]}" : "" );
				
				$area_tpl->assign( "content_url", $content_url );
				if ( !( metadata::$objects[$area["PRG_MODULE_SYSTEM_NAME"]]["only_one"] && $area["CONTENT_COUNT"] ) )
					$area_tpl->assign( "add_url", $content_url . "&action=add" );
			}
			$area_done=$area_tpl->fetch($this->tpl_dir."cms/page/area.tpl");
			
			$pattern="/<TD(.*)>(\{\\\$areas\.".$area["SYSTEM_NAME"]."\})<\/TD>/i";
			$cell_class = $selected ? "block_selected" : ( $is_content ? "block_content" : "block_empty" );
			$html_map=preg_replace($pattern, "<td\$1 class=\"{$cell_class}\"" . ( !$selected ? " onclick=\"document.location.href = '" . $params_url . "'\"" : "" ) . "><div><div>" . $area_done . "</div></div></td>", $html_map);
		}
		
		$pattern="/<TD(.*)>(\{.+\})<\/TD>/i"; $cell_class = "block_error";
		$html_map=preg_replace($pattern, "<td\$1 class=\"{$cell_class}\">" . metadata::$lang['lang_area_not_from_this_template'] . "</td>", $html_map);
		
		return $html_map;
	}

	/**
	 * Раскладка блоков. Выбор блока
	 *
	 * @param array $pk				первичный ключ раздела
	 * @param array $cur_area		запись текущей области с присоединенной дополнительной информацией
	 * @return string
	 */
	protected function html_block_change($pk, $cur_area)
	{
		$block_list_obj = object::factory( 'INF_BLOCK' );
		$block_list = $block_list_obj -> get_index_records( $_REQUEST, 'select2', '' );
		$block_list_obj -> __destruct();
		
		$block_array = array(); $block_array[] = "'': { 'title': '', 'module': '' }";
		foreach ( $block_list as $block_index => $block_item )
		{
			$block_list[$block_index]['SELECTED'] = $block_item['INF_BLOCK_ID'] == $cur_area['INF_BLOCK_ID'];
			$block_array[] = "{$block_item['INF_BLOCK_ID']}: { 'title': '" . htmlspecialchars( $block_item['_TITLE'], ENT_QUOTES ) .
				"', 'module': '" . $block_item['_PRG_MODULE_ID'] . "'" . ( $block_item['INF_BLOCK_ID'] == $cur_area['INF_BLOCK_ID'] ? ", selected: '1'" : "" ) . " }";
		}
		
		$prg_module_obj = object::factory( 'PRG_MODULE' );
		$module_list = $prg_module_obj -> get_index_records( $_REQUEST, 'select2', '' );
		$prg_module_obj -> __destruct();
		
		foreach ( $module_list as $module_index => $module_item )
			$module_list[$module_index]['SELECTED'] = $module_item['PRG_MODULE_ID'] == $cur_area['PRG_MODULE_ID'];
		
		$block_change_tpl = new smarty_ee( metadata::$lang );
		
		$block_change_tpl -> assign( 'block_list', $block_list );
		$block_change_tpl -> assign( 'module_list', $module_list );
		
		$block_change_tpl -> assign( 'block_array', '{ ' . join( ', ', $block_array ) . ' }' );
		
		$block_change_tpl -> assign( 'html_hidden', $this->url->get_hidden("block_change", array("pk"=>array_merge($pk, array("TEMPLATE_AREA_ID"=>$cur_area["TEMPLATE_AREA_ID"])))));
		
		return $block_change_tpl -> fetch( $this -> tpl_dir . 'cms/page/block_change.tpl' );
	}

	/**
	 * Раскладка блоков. Параметры блока
	 *
	 * @param array $page			запись раздела
	 * @param array $cur_area		запись текущей области с присоединенной дополнительной информацией
	 * @return string
	 */
	protected function html_block_params($page, $cur_area){
		// Если область пуста, то сразу возвращаем пустую строку
		if(!$cur_area["INF_BLOCK_ID"]){
			return "";
		}
		html_element::get_next_form_name();
		list($params, $field_descr, $record)=$this->full_object->get_params_descr_and_values($page["PAGE_ID"], $page["VERSION"], $cur_area["TEMPLATE_AREA_ID"]);
		$block_field=html_element::html_fields($this->full_object->get_form_fields("change", "_form_", $record, "", $field_descr), $this->tpl_dir."cms/page/block_fields.tpl", $this->field);
		$block_change=html_element::html_form(
			$block_field,
			$this->url->get_hidden("block_params", array("pk"=>array_merge($this->primary_key->get_from_record($page), array("TEMPLATE_AREA_ID"=>$cur_area["TEMPLATE_AREA_ID"])))),
			$this->tpl_dir."cms/page/block_form.tpl");
		return $block_change;
	}

	/**
	 * Получение списка всех областей страницы с информацией о вставленных блоках и принадлежности к типу шаблона
	 *
	 * @param int $page_id				идентификатор раздела
	 * @param int $version				версия раздела
	 * @param boolean $sort_by_main		сортировать так, чтобы главная область шла первой (а потом по AREA_ORDER), в противном случае сразу по полю AREA_ORDER
	 */
	protected function get_page_areas($page_id, $version, $sort_by_main=true){
		$order_clause=($sort_by_main ? "ORDER BY PAGE.IS_MAIN DESC, PAGE.AREA_ORDER" : "ORDER BY PAGE.AREA_ORDER");
		
		$prg_module_obj=object::factory( 'PRG_MODULE' );
		$template_area_obj=object::factory( 'TEMPLATE_AREA' );
		list( $dec_field1, $dec_where_search1, $dec_join1, $dec_binds1 ) = $prg_module_obj -> ext_field_selection( 'TITLE', 1 );
		list( $dec_field2, $dec_where_search2, $dec_join2, $dec_binds2 ) = $prg_module_obj -> ext_field_selection( 'ELEMENT_NAME', 2 );
		list( $dec_field3, $dec_where_search3, $dec_join3, $dec_binds3 ) = $template_area_obj -> ext_field_selection( 'TITLE', 3 );
		$prg_module_obj->__destruct();
		$template_area_obj->__destruct();
		
		$areas=db::sql_select("
			SELECT
				PAGE.*,
				INF_BLOCK.INF_BLOCK_ID, INF_BLOCK.TITLE AS INF_BLOCK_TITLE, INF_BLOCK.CACHE_TIME,
				LOWER( " . $dec_field2 . " ) AS ELEMENT_NAME, PRG_MODULE.PRG_MODULE_ID, PRG_MODULE.SYSTEM_NAME AS PRG_MODULE_SYSTEM_NAME,
				" . $dec_field1 . " AS PRG_MODULE_TITLE, PRG_MODULE.IS_ELEMENTS, COUNT( CONTENT_MAP.CONTENT_ID ) AS CONTENT_COUNT
			FROM
				(SELECT
					PAGE.PAGE_ID,
					PAGE.LANG_ID,
					PAGE.VERSION,
    				TEMPLATE.TEMPLATE_TYPE_ID,
			     	TEMPLATE_AREA.TEMPLATE_AREA_ID,
     				TEMPLATE_AREA.SYSTEM_NAME,
     				TEMPLATE_AREA.IS_MAIN,
     				" . $dec_field3 . " AS TITLE,
     				TEMPLATE_AREA.AREA_ORDER
				FROM TEMPLATE_AREA_MAP, TEMPLATE, PAGE, TEMPLATE_AREA
					" . $dec_join3[0] . "
				WHERE PAGE.PAGE_ID=:page_id AND PAGE.VERSION=:version
					AND TEMPLATE_AREA.TEMPLATE_AREA_ID=TEMPLATE_AREA_MAP.TEMPLATE_AREA_ID
					AND TEMPLATE_AREA_MAP.TEMPLATE_TYPE_ID=TEMPLATE.TEMPLATE_TYPE_ID
					AND TEMPLATE.TEMPLATE_ID=PAGE.TEMPLATE_ID
				) PAGE
			LEFT JOIN PAGE_AREA ON PAGE_AREA.PAGE_ID=PAGE.PAGE_ID AND PAGE_AREA.VERSION=PAGE.VERSION AND PAGE_AREA.TEMPLATE_AREA_ID=PAGE.TEMPLATE_AREA_ID
			LEFT JOIN INF_BLOCK ON PAGE_AREA.INF_BLOCK_ID=INF_BLOCK.INF_BLOCK_ID
			LEFT JOIN PRG_MODULE ON INF_BLOCK.PRG_MODULE_ID=PRG_MODULE.PRG_MODULE_ID
			LEFT JOIN CONTENT_MAP on CONTENT_MAP.INF_BLOCK_ID = INF_BLOCK.INF_BLOCK_ID
			" . $dec_join1[0] . " " . $dec_join2[0] . "
			GROUP BY
				PAGE.PAGE_ID, PAGE.LANG_ID, PAGE.VERSION, PAGE.TEMPLATE_TYPE_ID, PAGE.TEMPLATE_AREA_ID, PAGE.SYSTEM_NAME, PAGE.IS_MAIN, PAGE.TITLE, PAGE.AREA_ORDER,
  				INF_BLOCK.INF_BLOCK_ID, INF_BLOCK.TITLE, INF_BLOCK.CACHE_TIME,
  				" . $dec_field2 . ", PRG_MODULE.PRG_MODULE_ID,
  				PRG_MODULE.SYSTEM_NAME, " . $dec_field1 . ", PRG_MODULE.IS_ELEMENTS
			{$order_clause}
		", array("page_id"=>$page_id, "version"=>$version) + $dec_binds1 + $dec_binds2 + $dec_binds3 );
		
		
		return $areas;
	}

	/**
	 * Получение параметров модуля (переиндексированных по идентификатору и вместе со значениями для данной страницы), а так же на их основе описания полей и "записи" (как если бы эти параметры были полями таблицы)
	 *
	 * Если значений еще нет, то $record будет массивом с пустыми значениями.
	 * Названия полей образуются как "field_" с присоединенным идентификатором параметра модуля.
	 * То есть метод имеет двойное назначение - получить просто набор параметров для указанного блока или
	 * этот же набор дополненный значениями, если указанная область существует. Если в указанной области нет блока,
	 * то метод вернет пустые массивы
	 *
	 * @param int $page_id				идентификатор раздела
	 * @param int $version				версия раздела
	 * @param int $template_area_id		идентификатор области
	 * @param array
	 * @todo когда будет сделан параметр поля таблицы is_string, нужно будет не забыть подключить его к местной текстарии
	 */
	protected function get_params_descr_and_values($page_id, $version, $area_id){
		$module_param_obj=object::factory( 'MODULE_PARAM' );
		list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =$module_param_obj -> ext_field_selection( 'TITLE', 1 );
		$module_param_obj->__destruct();
		
		// Параметры и их значения
		$params=db::replace_field(db::sql_select("
			SELECT MODULE_PARAM.*, " . $dec_field .  " as \"_TITLE\", PAGE_AREA_PARAM.VALUE, PARAM_VALUE.VALUE AS PARAM_VALUE
			FROM
				(SELECT MODULE_PARAM.*, INF_BLOCK.INF_BLOCK_ID, PAGE_AREA.TEMPLATE_AREA_ID, PAGE_AREA.PAGE_ID, PAGE_AREA.VERSION
				FROM MODULE_PARAM, INF_BLOCK, PAGE_AREA
				WHERE PAGE_AREA.PAGE_ID=:page_id
					AND PAGE_AREA.VERSION=:version
					AND PAGE_AREA.TEMPLATE_AREA_ID=:area_id
					AND INF_BLOCK.INF_BLOCK_ID=PAGE_AREA.INF_BLOCK_ID
					AND MODULE_PARAM.PRG_MODULE_ID=INF_BLOCK.PRG_MODULE_ID
				)MODULE_PARAM
			LEFT JOIN PAGE_AREA_PARAM ON
				PAGE_AREA_PARAM.PAGE_ID=MODULE_PARAM.PAGE_ID
				AND PAGE_AREA_PARAM.VERSION=MODULE_PARAM.VERSION
				AND PAGE_AREA_PARAM.TEMPLATE_AREA_ID=MODULE_PARAM.TEMPLATE_AREA_ID
				AND PAGE_AREA_PARAM.MODULE_PARAM_ID=MODULE_PARAM.MODULE_PARAM_ID
			LEFT JOIN PARAM_VALUE ON
				(PARAM_VALUE.PARAM_VALUE_ID=PAGE_AREA_PARAM.VALUE)
			" . $dec_join[0] . "
			ORDER BY " . $dec_field . "
			", array("page_id"=>$page_id, "version"=>$version, "area_id"=>$area_id) + $dec_binds ), 'TITLE', '_TITLE');
		// Отсюда получим дополнительную информацию о целевой странице
		$page=db::sql_select("SELECT * FROM PAGE WHERE PAGE_ID=:page_id AND VERSION=:version", array("page_id"=>$page_id, "version"=>$version));
		// Формирование описания полей и "записи" для формы редактирования
		$field_descr=array();
		$record=array();
		foreach($params as $param){
			$key="field_".$param["MODULE_PARAM_ID"];
 			if($param["PARAM_TYPE"]=="select"){ // select
 				$field_descr[$key]=array("title"=>$param["TITLE"], "type"=>"select2", "fk_table"=>"PARAM_VALUE", "list_mode"=>array("MODULE_PARAM_ID"=>$param["MODULE_PARAM_ID"]), "errors"=>_nonempty_ );
 			}elseif($param["PARAM_TYPE"]=="template"){ // template
 				$prg_module=db::sql_select("SELECT SYSTEM_NAME FROM PRG_MODULE WHERE PRG_MODULE_ID=:prg_module_id", array("prg_module_id"=>$param["PRG_MODULE_ID"]));
 				$field_descr[$key]=array("title"=>$param["TITLE"], "type"=>"select2", "fk_table"=>"PARAM_VALUE", "list_mode"=>array("MODULE_PARAM_ID"=>$param["MODULE_PARAM_ID"]), "errors"=>_nonempty_, "vars"=>array( "template_url"=>"index.php?obj=FM&path=".urlencode( "/module_tpl/" . strtolower( $prg_module[0]["SYSTEM_NAME"] ) . "/" .$param["PARAM_VALUE"] ) ));
			}elseif($param["PARAM_TYPE"]=="table"){	// table
				$field_descr[$key]=array("title"=>$param["TITLE"], "type"=>"select2", "fk_table"=>$param["TABLE_NAME"], "list_mode"=>array("LANG_ID"=>($param["IS_LANG"] ? $page[0]["LANG_ID"] : "")));
			}elseif($param["PARAM_TYPE"]=="textarea"){	// textarea
				$field_descr[$key]=array("title"=>$param["TITLE"], "type"=>"textarea");
			}elseif($param["PARAM_TYPE"]=="int"){	// int
				$field_descr[$key]=array("title"=>$param["TITLE"], "type"=>"int");
			}elseif($param["PARAM_TYPE"]=="float"){	//float
				$field_descr[$key]=array("title"=>$param["TITLE"], "type"=>"float");
			}elseif($param["PARAM_TYPE"]=="page"){	// page
				// Выбираются все разделы кроме текущего, у которых в главной области помещен тот же самый блок, что в текущем разделе. Причем в том же языке и сайте, что и текущий раздел
				$this_module_pages=db::sql_select("
					SELECT PAGE.* FROM PAGE, PAGE_AREA, TEMPLATE_AREA
					WHERE
						PAGE.PAGE_ID<>:page_id
						AND PAGE.PAGE_ID=PAGE_AREA.PAGE_ID
						AND PAGE.VERSION=PAGE_AREA.VERSION
						AND PAGE_AREA.INF_BLOCK_ID=:inf_block_id
						AND PAGE_AREA.TEMPLATE_AREA_ID=TEMPLATE_AREA.TEMPLATE_AREA_ID
						AND TEMPLATE_AREA.IS_MAIN=1
						AND PAGE.LANG_ID=:lang_id
						AND PAGE.SITE_ID=:site_id
						".$this->full_object->version_where_clause()."
					ORDER BY PAGE.TITLE
				", array("inf_block_id"=>$param["INF_BLOCK_ID"], "page_id"=>$page_id, "lang_id"=>$page[0]["LANG_ID"], "site_id"=>$page[0]["SITE_ID"])+$this->full_object->version_where_clause_binds());
				// Составление описания поля с первым значением "Текущий раздел"
				$value_list=array(array("title"=>metadata::$lang["lang_current_page"], "value"=>"0"));
				foreach($this_module_pages as $tmp){
					$value_list[]=array("title"=>$tmp["TITLE"], "value"=>$tmp["PAGE_ID"]);
				}
				$field_descr[$key]=array("title"=>$param["TITLE"], "type"=>"select1", "value_list"=>$value_list, "errors"=>_number_|_nonempty_);
			}else{	// varchar
				$field_descr[$key]=array("title"=>$param["TITLE"], "type"=>"text");
			}
			$field_descr[$key]["module_param_id"]=$param["MODULE_PARAM_ID"]; // Вольная трактовка описания полей. Сделано для удобства получения идентификатора параметра модуля прямо из описания поля
			$record[$key]=$param["VALUE"];
		}
		// Параметры для удобства дальнейшего использования переиндексируются по идентификатору
		$params=lib::array_reindex($params, "MODULE_PARAM_ID");
		return array($params, $field_descr, $record);
	}

	/**
	 * Удаление данных об областях (PAGE_AREA, PAGE_AREA_PARAM) для указанного раздела
	 *
	 * @param int $page_id				идентификатор раздела
	 * @param int $version				версия; если не передана, то данные будут удалены из обеих версий
	 * @param int $area_id				область; если не передана, то данные будут удалены для всех областей
	 */
	public function delete_page_params($page_id="", $version="", $area_id=""){
		$pk=array("PAGE_ID"=>$page_id);
		if($version!==""){
			$pk+=array("VERSION"=>$version);
		}
		if($area_id!==""){
			$pk+=array("TEMPLATE_AREA_ID"=>$area_id);
		}
		db::delete_record("PAGE_AREA", $pk);
		db::delete_record("PAGE_AREA_PARAM", $pk);
	}

	/**
	 * Копирование данных об областях (PAGE_AREA, PAGE_AREA_PARAM) из одного раздела в другой
	 *
	 * Если $to_page_id не указан, то целевым разделом будет считаться раздел с таким же идентификатором как исходный, но с противоположной версией
	 *
	 * @param int $from_page_id				идентификатор исходного раздела
	 * @param int $from_version				версия исходного раздела
	 * @param int $to_page_id				идентификатор целевого раздела
	 * @param int $to_version				версия целевого раздела
	 */
	public function clone_page_params($from_page_id, $from_version, $to_page_id="", $to_page_version=""){
		// Уточняем целевой раздел и ключи разделов
		if(!$to_page_id){
			$to_page_id=$from_page_id;
			$to_version=1-$from_version;
		}
		$from_pk=array("PAGE_ID"=>$from_page_id, "VERSION"=>$from_version);
		$to_pk=array("PAGE_ID"=>$to_page_id, "VERSION"=>$to_version);
		// Удаляем старые параметры
		$this->delete_page_params($to_page_id, $to_version);
		// Перекладываем PAGE_AREA
		$records=db::sql_select("SELECT * FROM PAGE_AREA WHERE PAGE_ID=:PAGE_ID AND VERSION=:VERSION", $from_pk);
		lib::inserter("PAGE_AREA", $records, $to_pk);
		// Перекладываем PAGE_AREA_PARAM
		$records=db::sql_select("SELECT * FROM PAGE_AREA_PARAM WHERE PAGE_ID=:PAGE_ID AND VERSION=:VERSION", $from_pk);
		lib::inserter("PAGE_AREA_PARAM", $records, $to_pk);
	}

	/**
	 * Изменение заполняемости полей страницы под нужды типа страницы (в том числе с учетом главная это страница или нет)
	 *
	 * @param boolean $is_add			добавление записи (а также копирование) или изменение
	 * @param int $parent_id			идентификатор родителя страницы, используется для определения главная это страница или нет
	 * @param string $page_type			тип страницы, может быть "link", "folder", "page" (пустая строка и любое другое значение трактуются как "page")
	 * @param boolean $force_page_type	делать поле PAGE_TYPE обязательным для заполнения
	 */
	protected function adjust_page_fields($is_add, $parent_id, $page_type, $force_page_type=false){
		// Для начала выставляем заполненность всем полям, чтобы потом индивидуально снять
		$this->change_field_visibility(array("URL", "DIR_NAME", "SITE_ID", "LANG_ID", "TEMPLATE_ID", "IS_TITLE_SHOWED", "PAGE_TYPE", "PARENT_ID"), 0, 0);
		// Снятие заполняемости
		if($parent_id==0){ // Главная страница
			$this->change_field_visibility(array("URL", "DIR_NAME"), 1, 1);
			if(!$is_add){
				$this->change_field_visibility(array("SITE_ID", "LANG_ID", "DIR_NAME"), 1, 1, 1);
			}
		}else{ // Страницы внутренних уровней
			$this->change_field_visibility(array("SITE_ID", "LANG_ID"), 1, 1, (int)!$is_add);
			// Модифицируем заполняемость полей
			if($page_type=="link"){ // Ссылка
				$this->change_field_visibility(array("TEMPLATE_ID", "DIR_NAME", "IS_TITLE_SHOWED"), 1, 1);
			}elseif($page_type=="folder"){ // Папка
				$this->change_field_visibility(array("TEMPLATE_ID", "IS_TITLE_SHOWED", "URL"), 1, 1);
			}else{ // Обычный раздел
				$this->change_field_visibility(array("URL"), 1, 1);
			}
		}
		if($force_page_type){
			$this->change_field_visibility(array("PAGE_TYPE"), 0, 0);
		}else{
			$this->change_field_visibility(array("PAGE_TYPE"), 1, 1, 1);
		}
	}

	/**
	 * Специальная проверка существование главной страницы с указанными языком и сайтом. Если уже есть такая страница, то бросает исключение
	 *
	 * @param int $site_id	идентификатор сайта
	 * @param int $lang_id	идентификатор языка
	 * @param array $pk		первичный ключ раздела (указывается только при изменении - нужен для того, чтобы исключить текущую изменяемую главную страницу из проверки)
	 */
	public function check_main_page($site_id, $lang_id, $pk=""){
		$main_pages=db::sql_select("SELECT COUNT(*) AS COUNTER FROM PAGE WHERE LANG_ID=:lang_id AND SITE_ID=:site_id AND PAGE_ID<>:page_id AND PARENT_ID=0", array("lang_id"=>$lang_id, "site_id"=>$site_id, "page_id"=>$pk["PAGE_ID"]));
		if($main_pages[0]["COUNTER"]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_main_page_exists"].". ".metadata::$lang["lang_lang"].": ({$lang_id}), ".metadata::$lang["lang_site"].": ({$site_id})");
		}
	}

	/**
	 * Возвращает путь к разделу от корня сайта - "ru/page1/page2/page3/"
	 *
	 * Если нет родительского раздела в той версии, которая передана в $version, то метод будет смотреть в другую версию.
	 * Если в другой версии также не окажется раздела, то будет брошено исключение
	 *
	 * @param int $page_id			идентификатор раздела
	 * @param int $version			версия
	 * @param boolean $full_path	строить абсолютный путь (от корня на файловой системе)
	 * @param boolean $allow_links	если страницы нет, или она является ссылкой, то не бросать исключение, а молча возвращать пустую строку
	 */
	public function get_page_path($page_id, $version, $full_path, $allow_links=false){
		// Целевая страница
		$cur_page=db::sql_select("SELECT PAGE.*, SITE.PATH AS SITE_PATH, SITE.TEST_PATH AS SITE_TEST_PATH FROM PAGE, SITE WHERE PAGE.PAGE_ID=:page_id AND PAGE.VERSION=:version AND SITE.SITE_ID=PAGE.SITE_ID", array("page_id"=>$page_id, "version"=>$version));
		if(!$cur_page[0]["PAGE_ID"] || $cur_page[0]["PAGE_TYPE"]=="link"){
			if($allow_links){
				return "";
			}
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_record_not_found"].": ({$page_id})");
		}else{
			$site_root=($version ? $cur_page[0]["SITE_TEST_PATH"] : $cur_page[0]["SITE_PATH"]);
			if(!$cur_page[0]["DIR_NAME"]){
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_page_without_dir"].": \"".$this->get_record_title(array("PAGE_ID"=>$page_id))."\" (".$page_id.")");
			}
			$path=$cur_page[0]["DIR_NAME"]."/".$path;
		}
		// Сортировка - чтобы можно было выбрать сразу две версии раздела, но с приоритетом той версии, что указана в параметрах
		$order_clause=($version ? "ORDER BY VERSION DESC" : "ORDER BY VERSION");
		// Строим путь
		while($cur_page[0]["PARENT_ID"]){
			$cur_page_id=$cur_page[0]["PARENT_ID"];
			$cur_page=db::sql_select("SELECT PAGE.* FROM PAGE WHERE PAGE.PAGE_ID=:page_id {$order_clause}", array("page_id"=>$cur_page_id));
			if(!$cur_page[0]["DIR_NAME"]){
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_page_without_dir"].": \"".$this->get_record_title(array("PAGE_ID"=>$cur_page_id))."\" (".$cur_page_id.")");
			}
			$path=$cur_page[0]["DIR_NAME"]."/".$path;
		}
		// Достраиваем корень сайта, если надо
		if($full_path){
			$path=$site_root.$path;
		}
		return $path;
	}

	/**
	 * Удаление ненужных областей после изменения шаблона у раздела
	 *
	 * Если шаблон поменяли на другой, в типе которого существуют не все области исходного шаблона,
	 * то этот метод поможет удалить ставшие ненужными области
	 *
	 * @param int $page_id			идентификатор раздела
	 * @param int $version			версия
	 * @param int $old_template_id	идентификатор старого шаблона
	 * @param int $new_template_id	идентификатор нового шаблона
	 */
	protected function cut_unneeded_areas($page_id, $version, $old_template_id, $new_template_id){
		if($old_template_id!=(int)$new_template_id){
			include_once(params::$params["adm_data_server"]["value"]."class/core/object/decorator/table_translate.php");
			include_once(params::$params["adm_data_server"]["value"]."class/cms/table/template/template.php");
			list($tmpl_info, $area_info)=template::get_areas_info();
			$difference_in=lib::array_make_in(array_diff($area_info[$tmpl_info[$old_template_id]], $area_info[$tmpl_info[$new_template_id]]));
			db::sql_query("DELETE FROM PAGE_AREA_PARAM WHERE PAGE_ID=:PAGE_ID AND VERSION=:VERSION AND TEMPLATE_AREA_ID IN ({$difference_in})", array("PAGE_ID"=>$page_id, "VERSION"=>$version));
			db::sql_query("DELETE FROM PAGE_AREA WHERE PAGE_ID=:PAGE_ID AND VERSION=:VERSION AND TEMPLATE_AREA_ID IN ({$difference_in})", array("PAGE_ID"=>$page_id, "VERSION"=>$version));
		}
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Любой пользователь может видеть любой раздел. А вот сможет ли он что-то с этим разделом сделать - определяют его права
	 *
	 * Право на изменение в этом методе везде формируется не как просто "change", а как array("change", "publish"),
	 * так как publish более сильное право и включает в себя "change"
	 * @see object::is_permitted_to()
	 * @todo Здесь и в других is_permitted_to() и is_permitted_to_mass() нужно внимательно посмотреть на последовательность строчек<br>
	 * list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, array("change", "publish"), "PAGE");<br>
	 * $rights=db::sql_select("SELECT COUNT(*) AS COUNTER FROM {$auth_tables} WHERE {$auth_clause} AND PAGE.PAGE_ID=:parent_id", array_merge(array("parent_id"=>$pk["PARENT_ID"]), $auth_binds));<br>
	 * $is_permitted=(bool)$rights[0]["COUNTER"];<br>
	 * и попробовать вынести это (там, где можно) в отдельные методы в классе auth для упрощения кода
	 */
	public function is_permitted_to($ep_type, $pk="", $throw_exception=false){
		if($this->auth->is_main_admin || $ep_type=="view"){ // В таблицу разделов разрешаем смотреть всем, а главному администратору вообще все можно
			$is_permitted=true;
		}elseif($ep_type=="add"){ // Добавлять новые разделы можно в случае наличия права редактировать родительский раздел (добавлять главные страницы может только главный администратор)
			if($pk["PARENT_ID"]){
				$is_permitted=auth::is_site_admin_for("PAGE", $pk["PARENT_ID"]);
			}
			if(!$is_permitted){
				list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, array("change", "publish"), "page", "PAGE");
				$rights=db::sql_select("SELECT COUNT(*) AS COUNTER FROM {$auth_tables} WHERE {$auth_clause} AND PAGE.PAGE_ID=:parent_id", array_merge(array("parent_id"=>$pk["PARENT_ID"]), $auth_binds));
				$is_permitted=(bool)$rights[0]["COUNTER"];
			}
		}else{ // Прочие операции
			$is_permitted=auth::is_site_admin_for("PAGE", $pk["PAGE_ID"]);
			// Если по администратору сайта ничего не получилось, то проверяем обычным образом
			if(!$is_permitted){
				$page=$this->full_object->get_versions($pk);
				if($ep_type=="change" || $ep_type=="undo"){ // Изменение и Отмена
					$type=($page[0]["VERSION"] ? array("change", "publish") : "publish");
				}elseif($ep_type=="publish" || $ep_type=="unpublish"){ // Публикация и Снятие с публикации
					$type="publish";
				}elseif($ep_type=="delete"){ // Удаление
					$type=($page[0]["VERSION"] && count($page)==1 ? array("change", "publish") : "publish");
				}elseif($ep_type=="meta_change"){ // Редактирование метатегов
					$type=($page[0]["VERSION"] ? array("meta_change", "change", "publish") : array("meta_change", "publish"));
				}
				list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, $type, "page", "PAGE");
				$rights=db::sql_select("SELECT COUNT(*) AS COUNTER FROM {$auth_tables} WHERE {$auth_clause} AND PAGE.PAGE_ID=:page_id", array_merge(array("page_id"=>$pk["PAGE_ID"]), $auth_binds));
				$is_permitted=(bool)$rights[0]["COUNTER"];
			}
		}
		if(!$is_permitted && $throw_exception){
			$pk_message=($pk["PAGE_ID"] ? ": \"".$this->get_record_title($pk)."\" (".$pk[$this->autoinc_name].")" : "");
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_operation_not_permitted_".$ep_type].$pk_message);
		}
		return $is_permitted;
	}

	/**
	 * Права на массовые операции в разделах
	 *
	 * Следующая фича убрана, чтобы корректно формировать список записей: Здесь права на изменение проверяются жестче, чем в is_permitted_to(), потому что многие-ко-многим
	 * применяются сразу к обеим версиям раздела. Таким образом, если у раздела есть рабочая версия, то "change" будет
	 * проверяться по "publish"
	 *
	 * @see object::is_permitted_to_mass()
	 * @todo Дистинкт применяется, надо понаблюдать за производительностью
	 * @todo Возможно стоит все проверять здесь по "publish" и не мучиться? А то есть некоторая вероятность выдать права на публикацию, имея права на изменение. Хотя эта вероятность и очень низка.
	 * @todo разобраться с убранной фичей о проверке прав на изменение по публикации
	 */
	public function is_permitted_to_mass($ep_type, $ids=array(), $throw_exception=false){
		$not_allowed=array();
		if($this->auth->is_main_admin || !count($ids)){
			// Можно все
		}else{
			// Получаем информацию о правах на изменение и публикацию разделов
			list($auth_tables, $publish_auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, array("publish"), "page", "PAGE");
			list($auth_tables, $change_auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, array("publish", "change"), "page", "PAGE");
			list($auth_tables, $meta_change_auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, array("publish", "change", "meta_change"), "page", "PAGE");
			$in=(is_array($ids) && count($ids)>0 ? join(", ", $ids) : 0);
			// Выполняем запросы для определения прав (для администраторов сайтов готовим спецкляузу)
			$publish_rights_clause="SELECT DISTINCT PAGE.PAGE_ID FROM {$auth_tables} WHERE {$publish_auth_clause} AND AUTH_ACL.OBJECT_ID IN ({$in})";
			$change_rights_clause="SELECT DISTINCT PAGE.PAGE_ID FROM {$auth_tables} WHERE {$change_auth_clause} AND AUTH_ACL.OBJECT_ID IN ({$in})";
			$meta_change_rights_clause="SELECT DISTINCT PAGE.PAGE_ID FROM {$auth_tables} WHERE {$meta_change_auth_clause} AND AUTH_ACL.OBJECT_ID IN ({$in})";
			if($this->auth->sites_in){
				$site_union_select = " UNION SELECT DISTINCT PAGE.PAGE_ID FROM PAGE WHERE PAGE.SITE_ID IN ({$this->auth->sites_in}) AND PAGE.PAGE_ID IN ({$in})";
				$publish_rights_clause .= $site_union_select; $change_rights_clause .= $site_union_select; $meta_change_rights_clause .= $site_union_select;
			}
			$publish_rights=lib::array_reindex(db::sql_select($publish_rights_clause, $auth_binds), "PAGE_ID");
			$change_rights=lib::array_reindex(db::sql_select($change_rights_clause, $auth_binds), "PAGE_ID");
			$meta_change_rights=lib::array_reindex(db::sql_select($meta_change_rights_clause, $auth_binds), "PAGE_ID");
			// Информация о версиях разделов, на которые проверяются права
			$version_info=db::sql_select("SELECT ".$this->primary_key->select_clause()." FROM {$this->obj} WHERE {$this->obj}.{$this->autoinc_name} IN ({$in}) ORDER BY {$this->obj}.VERSION DESC");
			foreach($version_info as $vi){
				$page_versions[$vi["PAGE_ID"]]=$vi["VERSION"];
			}
			// Проверяем права и формируем список разделов, на которые нет прав
			foreach($page_versions as $page_id=>$page_version){
				$allowed_to_change=(bool)$change_rights[$page_id];
				$allowed_to_publish=(bool)$publish_rights[$page_id];
				$allowed_to_meta_change=(bool)$meta_change_rights[$page_id];
				if(!((($ep_type=="change" || $ep_type=="delete" || $ep_type=="undo" || $ep_type=="copy") && $allowed_to_change) || (($ep_type=="publish" || $ep_type=="unpublish") && $allowed_to_publish) || ($ep_type=="meta_change" && $allowed_to_meta_change))){
					$not_allowed[]=$page_id;
				}elseif($ep_type!="change" && $ep_type!="delete" && $ep_type!="publish" && $ep_type!="unpublish" && $ep_type!="undo" && $ep_type!="copy" && $ep_type!="meta_change"){ // На всякий случай любые непредвиденные операции тоже запрещаем
					$not_allowed[]=$page_id;
				}
			}
			// для случая с $throw_exception
			$is_permitted=!(bool)count($not_allowed);
			if(!$is_permitted && $throw_exception){
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_mass_operation_not_permitted_".$ep_type].": ".join(", ", $not_allowed));
			}
		}
		return $not_allowed;
	}

	/**
	 * Подменяет дефолтное системное меню. Меню содержит древовидный список разделов.
	 */
	public function get_system_menu()
	{
		// Получаем список всех разделов
		$page_object = object::factory( 'PAGE' );
		$page_object -> main_version = 1; $page_object -> parent_id = 0;
		$records = $page_object -> get_index_records( $none, 'select2', array( 'with_links' => 1 ) );
		$page_object -> __destruct();
		
		// Получаем список идентификаторов главных страниц
		$root_page = array();
		foreach( $records as $rs )
			if ( $rs['PARENT_ID'] == 0 )
				$root_page[] = $rs['PAGE_ID'];
		
		// Получаем единым запросом идентификаторы сайтов корневых страниц
		$root_page_site = db::sql_select(
			'select PAGE_ID, DIR_NAME, SITE_ID from PAGE where PAGE_ID in ( ' . lib::array_make_in( $root_page ) . ' )' );
		$root_page_site = lib::array_reindex( $root_page_site, 'PAGE_ID' );
		
		// Получаем массив сайтов, содержащих дочерние разделы
		$root_site_page = lib::array_reindex( $root_page_site, 'SITE_ID' );
		
		// Для главных страниц подменяем идентификатор их родителя и запоминаем директорию
		foreach( $records as $ri => $rs )
		{
			if ( isset( $root_page_site[$rs['PAGE_ID']] ) )
			{
				$records[$ri]['PARENT_ID'] = -$root_page_site[$rs['PAGE_ID']]['SITE_ID'];
				$records[$ri]['DIR_NAME'] = $root_page_site[$rs['PAGE_ID']]['DIR_NAME'];
			}
		}
		
		// Вводим в массив фиктивные ветки, обозначающие сайты
		$site_list = db::sql_select( 'select * from SITE order by SITE_ID' );
		foreach ( $site_list as $site )
			$records[] = array( 'PAGE_ID' => -$site['SITE_ID'], 'PARENT_ID' => 0, 'TITLE' => $site['TITLE'],
				'_icon' => 'site', 'is_children' => isset( $root_site_page[$site['SITE_ID']] ) );
		
		// Строим глобальный массив, проиндексированный по номеру раздела
		$this -> page_id_array = lib::array_reindex( $records, 'PAGE_ID' );
		
		// Определяем идентификатор текущего объекта в дереве разделов
		$current_id = intval( $_REQUEST['_f_PARENT_ID'] ? $_REQUEST['_f_PARENT_ID'] : -$_REQUEST['_f_SITE_ID'] );
		
		// Специальным методом корректируем массивы открытых и закрытых веток
		$this -> tree_state_correction( 'page', $current_id );
		
		// По умолчанию в дереве открывается только один сайт.
		// Это может быть или первый сайт из сайтов, где юзер - администратор
		$open_site = intval( $this -> auth -> sites_in );
		// ... или просто первый попавшийся сайт из таблицы SITE.
		if ( !$open_site )
			$open_site = $site_list[0]['SITE_ID'];
		// ... Для всех остальных сайтов устанавливаем атрибут "закрыт"
		foreach ( $site_list as $site )
			if ( $site['SITE_ID'] != $open_site )
				if ( !isset( $_SESSION['tree_expanded']['page'][-$site['SITE_ID']] ) )
					$_SESSION['tree_collapsed']['page'][-$site['SITE_ID']] = 1;
		
		// Строим глобальный массив, проиндексированный по родителю.
		// В него входят разделы уровня вложенности не более заданного
		// плюс разделы, расположенные на пути у текущему разделу.
		// Также учитываются массивы открытых и закрытых веток.
		$this -> parent_id_array = array();
		foreach( $records as &$rs )
			if ( ( $rs['TREE_DEEP'] < params::$params['page_tree_depth']['value'] ||
					isset( $_SESSION['tree_expanded']['page'][$rs['PARENT_ID']] ) ) &&
					!isset( $_SESSION['tree_collapsed']['page'][$rs['PARENT_ID']] ) )
				$this -> parent_id_array[$rs['PARENT_ID']][] = $rs;
		
		// На основании полученного массива строим дерево разделов
		$tree_list = $this -> get_tree_list( 0, $current_id );
		
		// Выводим дерево разделов в шаблон
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'tree_list', $tree_list );
		$tpl -> assign( 'tree_param', "{ 'name': 'page', 'url': 'index.php?obj=PAGE&action=service' }" );
		
		$tpl -> assign( 'section_title', $this -> section_title );
		$tpl -> assign( 'section_icon', $this -> section_type . '.gif' );
		
		return $tpl -> fetch( $this -> tpl_dir.'core/object/html_tree_menu.tpl');
	}

	/**
	 * Обработчик команды 'tree_open'. Возвращает список дочерних разделов
	 * 
	 * @param string $mark - уникальный идентификатор команды
	 */
	public function command_tree_open( $mark = '' )
	{
		$parent_id = intval( $_REQUEST['parent_id'] );
		$current_id = intval( $_REQUEST['current_id'] );
		
		if ( !isset( $_SESSION['tree_expanded']['page'][$parent_id] ) )
			$_SESSION['tree_expanded']['page'][$parent_id] = 1;
		unset( $_SESSION['tree_collapsed']['page'][$parent_id] );
		
		// Если запрос фиктивный, возвращаем пустой ответ
		if ( $_REQUEST['empty'] )
			return html_element::xml_response( '', $mark );
		
		// Подготавливаем массив-заменитель $_REQUEST
		$request = array( '_f_PARENT_ID' => $parent_id );
		
		// Если передан отрицательный родитель, оcуществляем фильтрацию по сайту
		if ( $parent_id < 0 )
		{
			$request['_f_SITE_ID'] = -$request['_f_PARENT_ID']; $request['_f_PARENT_ID'] = 0;
		}
		
		// Получаем список всех разделов
		$page_object = object::factory( 'PAGE' );
		$page_object -> apply_object_parameters( $request );
		$page_object -> rows_per_page = 50;
		$records = $page_object -> get_index_records( $request, 'index', '' );
		$page_object -> __destruct();
		
		// Для главных страниц подменяем идентификатор их родителя
		if ( $parent_id < 0 )
			foreach( $records as $ri => $rs )
				$records[$ri]['PARENT_ID'] = $parent_id;
		
		// Строим временный массив, проиндексированный по родителю
		$this -> parent_id_array = lib::array_reindex( $records, 'PARENT_ID', 'PAGE_ID' );
		
		// На основании полученного массива строим дерево разделов
		$tree_list = $this -> get_tree_list( $parent_id, $current_id );
		
		// Формируем ответ сервера
		return html_element::xml_response( '<items><![CDATA[' . $tree_list . ']]></items>', $mark );
	}

	/**
	 * Рекурсивный метод постоения дерева разделов
	 * 
	 * @param int $parent_id	- идентификатор раздела, с которого начинается постоение дерева
	 * @param int $current_id	- идентификатор текущего раздела
	 */
	private function get_tree_list( $parent_id = 0, $current_id = 0 )
	{
		$children = array();
		if ( is_array( $this -> parent_id_array[$parent_id] ) )
			foreach ( $this -> parent_id_array[$parent_id] as $child )
				$children[] = "{ " .
					"'title': '" . htmlspecialchars( $child['TITLE'], ENT_QUOTES ) . "', " .
					"'image': " . ( $parent_id < 0 ? "'/common/adm/img/lang/" . $child['DIR_NAME'] . ".gif'" : "'/common/adm/img/menu/icons/" . $child['_icon'] . ".gif'" ) . ", " .
					"'object': window, " .
					"'method': 'redirect', " .
					( $parent_id ? 
						"'param': { 'url': 'index.php?obj=PAGE&_f_PARENT_ID={$child['PAGE_ID']}', " .
							"'edit_url': 'index.php?obj=PAGE&action=change&PAGE_ID={$child['PAGE_ID']}&" .
							( $current_id < 0 ? "_f_SITE_ID=" . ( -$current_id ) : "_f_PARENT_ID=" . ( $current_id ) ) . "', " .
							"'edit_title': '" . metadata::$lang["lang_change_page"] . "' }, " :
						"'param': { 'url': 'index.php?obj=PAGE&_f_SITE_ID=" . ( -$child['PAGE_ID'] ) . "' }, " ) .
					"'parent_id': '" . $child['PAGE_ID'] . "', " .
					"'current_id': '" . $current_id . "', " .
					"'is_children': " . ( $child['is_children'] || is_array( $this -> parent_id_array[$child['PAGE_ID']] ) || in_array( $child['_icon'], array( 'page', 'folder' ) ) ? '1' : '0' ) .
						( is_array( $this -> parent_id_array[$child['PAGE_ID']] ) ? ", 'items': [ " . $this -> get_tree_list( $child['PAGE_ID'], $current_id ) . " ]" : "" ) . " }";
		return join( ',', $children );
	}

	/**
	* Получает дополнительные данные для лога
	*/
	public function get_additional_info_for_log($fields) {
		if ($fields['PARENT_ID']) {
			$parent_page=db::sql_select("SELECT SITE_ID, LANG_ID FROM PAGE WHERE PAGE_ID=:parent_id ORDER BY VERSION DESC", array("parent_id"=>$fields["PARENT_ID"]));
			$fields['log_params']['log_info']['site_id']=$parent_page[0]['SITE_ID'];
		}
		else {
			$fields['log_params']['log_info']['site_id']=$fields['SITE_ID'];
		}
		
		$site = db::sql_select('SELECT TITLE FROM SITE WHERE SITE_ID=:site_id', array('site_id'=>$fields['log_params']['log_info']['site_id']));
		if ($site) 
			$fields['log_params']['log_info']['site_title']=$site[0]['TITLE'];
		
		return parent::get_additional_info_for_log($fields);
	}

	/**
	 * Метод возвращает массив параметров типа "раздел" модуля блока, провязанного к заданной области
	 * 
	 * @param int $page_id		идентификатор раздела
	 * @param int $version		версия раздела
	 * @param int $page_id		идентификатор области
	 * @return array
	 */
	public function get_page_module_params( $page_id = '', $version = '', $area_id = '' )
	{
		$module_params = db::sql_select( '
			select
					MODULE_PARAM.MODULE_PARAM_ID
				from
					PAGE_AREA_PARAM, MODULE_PARAM
				where
					PAGE_AREA_PARAM.PAGE_ID = :page_id
					and PAGE_AREA_PARAM.VERSION = :version
					and PAGE_AREA_PARAM.TEMPLATE_AREA_ID = :template_area_id
					and PAGE_AREA_PARAM.MODULE_PARAM_ID = MODULE_PARAM.MODULE_PARAM_ID
					and MODULE_PARAM.PARAM_TYPE = :param_type',
			array( 'page_id' => $page_id, 'version' => $version, 'template_area_id' => $area_id, 'param_type' => 'page' ) );
		
		return lib::array_reindex( $module_params, 'MODULE_PARAM_ID' );
	}

	/**
	 * Метод возвращает массив подразделов выбранной страницы, сгруппированный по странице и версии
	 * 
	 * @param int $parent_id		идентификатор родительского раздела
	 * @param boolean $all_version	если true, в массив попадают обе версии
	 * @return array
	 */
	public function get_subtrees( $parent_id = '', $all_version = false )
	{
		$return_array = array();
		
		if ( is_array( $this -> parent_id_array[$parent_id] ) )
		{
			foreach( $this -> parent_id_array[$parent_id] as $page )
			{
				if ( isset( $this -> page_id_array[$page['PAGE_ID']][1] ) )
				{
					// Если существует тестовая версии, то возвращаем ее
					$return_array[$page['PAGE_ID']][1] = 1;
					// В случае "all_version" по возможности захватываем и рабочую
					if ( $all_version && isset( $this -> page_id_array[$page['PAGE_ID']][0] ) )
						$return_array[$page['PAGE_ID']][0] = 1;
				}
				else
				{
					// В противном случае берем только рабочую версию
					$return_array[$page['PAGE_ID']][0] = 1;
				}
				// Ползем дальше по дереву
				$return_array += $this -> get_subtrees( $page['PAGE_ID'], $all_version );
			}
		}
		return $return_array;
	}

	/**
	 * Укладка .htaccess с ErrorDocument в корневые директории неглавных языков
	 * 
	 * @param int $page_path	путь к корневой директории
	 * @param int $root_dir		название корневой директории
	 */
	public function write_htaccess( $page_path = '', $root_dir = '' )
	{
		$htaccess = <<<HTACCESS
ErrorDocument 401  /{$root_dir}/errors/401/
ErrorDocument 403  /{$root_dir}/errors/403/
ErrorDocument 404  /{$root_dir}/errors/404/
ErrorDocument 500  /{$root_dir}/errors/500/
HTACCESS;
		
		if( is_writable( $page_path ) )
			file_put_contents( $page_path . '.htaccess', $htaccess );
	}

	/**
	 * Добавляем в конец таблицы скрипт вызова метода для изменения набора полей
	 *
	 * @see table::html_card()
	 */
	public function html_card( $mode, &$request )
	{
		list( $title, $html ) = $this->call_parent("html_card", array($mode, &$request));
		
		$page = $this -> get_change_record( $this -> primary_key -> get_from_request() );
		if ( $mode != 'change' || $page['PAGE_TYPE'] == 'link' )
			return array( $title, $html );
		
		$form_name = html_element::get_form_name();
		
		$html .= <<<HTM
<script type="text/javascript" language="JavaScript">
	var oSelect = document.forms['{$form_name}']['_form_PAGE_TYPE'];
	if ( oSelect )
	{
		addListener( oSelect, 'change', checkPageType ); checkPageType();
	}
	
	function checkPageType()
	{
		var sPageType = oSelect.options[ oSelect.selectedIndex ].value;
		
		if ( !sPageType ) return;
		
		var oTemplateId = document.getElementById( '_form_TEMPLATE_ID' );
			var oTemplateIdInput = document.forms['{$form_name}']['_form_TEMPLATE_ID'];
		var oIsTitleShowed = document.getElementById( '_form_IS_TITLE_SHOWED' );
		
		oTemplateId.style.display = oIsTitleShowed.style.display = sPageType == 'page' ? '' : 'none';
		oTemplateIdInput.setAttribute( 'lang', '_int_' + ( sPageType == 'page' ? '_nonempty_' : '' ) );
	}
</script>
HTM;
		return array( metadata::$lang["lang_change_page"], $html );
	}
	
	/**
	* Дополняем информацию об экспорте данными об областях и параметрах областей
	*/
	
	public function get_export_add_data_xml($pk) {	
		$xml = $this -> call_parent( 'get_export_add_data_xml', array( $pk ) )."\n";
		$xml .= $this->get_export_page_area($pk);
		return $xml;		
	}
	
	/**
	* Возвращает данные в формате xml об областях и параметрах областей для данного раздела
	* @param array $pk первичный ключ записи раздела
	* @return string
	*/
	
	private function get_export_page_area($pk) {
		$xml='';
		$page_info = $this->get_change_record($pk);
		$page_areas = db::sql_select ('SELECT * FROM PAGE_AREA WHERE PAGE_ID=:page_id AND VERSION=:version', array('page_id'=>$page_info['PAGE_ID'], 'version'=>$page_info['VERSION']));
		for ($i=0, $n=sizeof($page_areas); $i<$n; $i++) {
			$xml .= "<PAGE_AREA TEMPLATE_AREA_ID=\"{$page_areas[$i]['TEMPLATE_AREA_ID']}\" INF_BLOCK_ID=\"{$page_areas[$i]['INF_BLOCK_ID']}\">\n".
						preg_replace('/^/m', '  ',$this->get_export_page_area_params($page_areas[$i])).
					"</PAGE_AREA>\n";
		}
		return $xml;
	}
	
	/**
	* Возвращает данные о параметрах областей для данной области
	* @param array $page_area_info информация об области
	* @return string
	*/
	
	private function get_export_page_area_params($page_area_info) {
		$xml = '';
		$params = db::sql_select('
						SELECT 
							MP.SYSTEM_NAME, 
							PAP.VALUE, 
							MP.MODULE_PARAM_ID 
						FROM
							MODULE_PARAM MP
								INNER JOIN 
									PAGE_AREA_PARAM PAP 
										ON (MP.MODULE_PARAM_ID=PAP.MODULE_PARAM_ID)
						WHERE PAP.PAGE_ID=:page_id AND PAP.VERSION=:version AND TEMPLATE_AREA_ID=:template_area_id',
						array ('page_id'=>$page_area_info['PAGE_ID'], 'version'=>$page_area_info['VERSION'], 'template_area_id'=>$page_area_info['TEMPLATE_AREA_ID'])
				  );
		for ($i=0, $n=sizeof($params); $i<$n; $i++) 
			$xml .= "<PARAM_VALUE MODULE_PARAM=\"{$params[$i]['SYSTEM_NAME']}\"><![CDATA[".$this->get_export_page_area_param_value($params[$i]['MODULE_PARAM_ID'], $params[$i]['VALUE'])."]]></PARAM_VALUE>\n";

		return $xml;
	}
	
	/**
	* Возвращает значение параметра для данного параметра модуля
	* @param int $module_param_id Уникальный ключ параметра модуля
	* @param mixed $param_value значение параметра
	*/
	
	private function get_export_page_area_param_value ($module_param_id, $param_value) {
		$values=db::sql_select('SELECT PARAM_VALUE_ID, VALUE FROM PARAM_VALUE WHERE MODULE_PARAM_ID=:module_param_id', array('module_param_id'=>$module_param_id));
		if (sizeof($values)) {
			// если есть инфа о данном параметре в PARAM_VALUE - то значит нужно VALUE брать оттуда (для template и select)
			$values=lib::array_reindex($values, 'PARAM_VALUE_ID');
			if ($values[$param_value])
				$param_value=$values[$param_value]['VALUE'];
		}
		return $param_value;
	}
	
	
	/**
	* Метод импорта данные из XML - унаследованный метод от table
	* Дополняет функционал импортом данных об областях разделов
	* @param array $xml_arr массив данных одной записи, возвращаемый ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return array карта соответствия между id записей из файла импорта и реальными
	*/
	
	public function import_from_xml ($xml_arr, &$import_data) {
		$id_map = $this -> call_parent( 'import_from_xml', array( $xml_arr, &$import_data ) );
		$this->import_page_areas(current($id_map), $xml_arr['children'], $import_data);
		$this->exec_gen_page(current($id_map), 1);
		return $id_map;
	}
	
	/**
	* Возвращает поля для вставки в таблицу в процессе импорта - унаследованный метод от table
	* Дополняет функционал подменой сайта и языка на данные раздела под который данный раздел импортируется
	* А также подменяются данные о иерархии на новые (PARENT_ID) при помощи карты соответствия ID
	* Кроме этого подменяются данные о ID шаблона в случае если был указан параметр импортировать шаблоны
	* @param array $main_children данные обо всех потомках массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return array Подготовленные данные для вставки в таблицу
	*/
	
	public function get_import_field_values($main_children, &$import_data) {
		$field_values = $this -> call_parent( 'get_import_field_values', array( $main_children, &$import_data ) );
		$field_values['SITE_ID']=$import_data['root_page_info']['SITE_ID'];
		$field_values['LANG_ID']=$import_data['root_page_info']['LANG_ID'];
		$field_values['PARENT_ID']=$import_data['id_maps']['PAGE'][$field_values['PARENT_ID']]?$import_data['id_maps']['PAGE'][$field_values['PARENT_ID']]:$import_data['root_page_info']['PAGE_ID'];
		if ($import_data['info_data']['TEMPLATE'] && $field_values['TEMPLATE_ID']) 
			$field_values['TEMPLATE_ID']=$this->get_import_new_id($field_values['TEMPLATE_ID'], 'TEMPLATE', $import_data);
		return $field_values;
	}
	
	
	/**
	* Метод импорта данных об областях разделов
	* @param int $page_id ID обрабатываемого раздела
	* @param array $main_children данные обо всех потомках массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	*/
	
	private function import_page_areas ($page_id, $main_children, &$import_data) {
		for ($i=0, $n=sizeof($main_children); $i<$n; $i++) 
			if ($main_children[$i]['tag']=='PAGE_AREA') {
				$inf_block_id = $main_children[$i]['attributes']['INF_BLOCK_ID'];
				if ($import_data['info_data']['INF_BLOCK'])
					$inf_block_id = $this->get_import_new_id($inf_block_id, 'INF_BLOCK', $import_data);
				
				$inf_block_obj=object::factory('INF_BLOCK');
				$inf_block_data = $inf_block_obj->get_change_record(array('INF_BLOCK_ID'=>$inf_block_id));
				$inf_block_obj->__destruct();
				unset($inf_block_obj);
				
				$page = $this->get_change_record(array('PAGE_ID'=>$page_id));
				
				$template_area_id = $main_children[$i]['attributes']['TEMPLATE_AREA_ID'];
				// если экспортировали шаблоны, то нужно подменить ID
				if ($import_data['info_data']['TEMPLATE_TYPE'])
					$template_area_id=$this->get_import_new_id($template_area_id, 'TEMPLATE_AREA', $import_data);
				
				$new_page_area_pk = array(
					'PAGE_ID' => $page_id, 
					'TEMPLATE_AREA_ID' => $template_area_id,
					'VERSION' => $page['VERSION']
				);
				
				db::insert_record(
					'PAGE_AREA', 
					$new_page_area_pk + array ('INF_BLOCK_ID' => $inf_block_id)
				);
				
				// импортируем параметры областей раздела
				if (sizeof($main_children[$i]['children']))
					foreach ($main_children[$i]['children'] as $child) 
						if ($child['tag']=='PARAM_VALUE') {
							$module_param_id = $this->get_import_module_param_id($inf_block_data['PRG_MODULE_ID'], $child['attributes']['MODULE_PARAM']);

							db::insert_record (
								'PAGE_AREA_PARAM',
								$new_page_area_pk +
								array (
									'MODULE_PARAM_ID' => $module_param_id,
									'VALUE' => $this->get_import_param_value($module_param_id, $child['value'])
								)
							);
						}
			}
	}
	
	/**
	* Возвращает id параметра модуля по id модуля и системному имени параметра
	* @param int $prg_module_id ID модуля
	* @param int $param_system_name Системное имя параметра
	* @return ID параметра модуля
	*/
	
	private function get_import_module_param_id ($prg_module_id, $param_system_name) {
		$res = db::sql_select('
			SELECT 
				MODULE_PARAM_ID
			FROM
				MODULE_PARAM
			WHERE
				PRG_MODULE_ID = :prg_module_id 
					AND	
						SYSTEM_NAME = :system_name
			',
			array (
				'prg_module_id' => $prg_module_id,
				'system_name' => $param_system_name
			)
		);
		
		return $res[0]['MODULE_PARAM_ID'];			
	}
	
	/**
	* Получает значение параметра для вставки в PAGE_AREA_PARAM
	* Если есть для данного модуля и значения есть данные в PARAM_VALUE 
	* (на данный момент для типов select и template), то возвращает PARAM_VALUE_ID
	* иначе $value без изменений
	* @param int $module_param_id ID параметра модуля
	* @param mixed $value значение
	* @return mixed значение для вставки в PAGE_AREA_PARAM
	*/
	
	private function get_import_param_value($module_param_id, $value) {
		$param_value_data = db::sql_select('
			SELECT 
				PARAM_VALUE_ID 
			FROM 
				PARAM_VALUE
			WHERE
				MODULE_PARAM_ID=:module_param_id
					AND
						 VALUE=:value
			',
			array (
				'module_param_id' => $module_param_id,
				'value' => $value
			)
			);
		if (sizeof($param_value_data)) 
			$value = $param_value_data[0]['PARAM_VALUE_ID'];
		
		return $value;		
	}
	
	
	/**
	* Необходимо кроме страницы удалить области страницы и параметры
	*/
	
	public function import_undo ($content_id) {
		db::sql_query('DELETE FROM PAGE_AREA_PARAM WHERE PAGE_ID=:page_id', array('page_id'=>$content_id));
		db::sql_query('DELETE FROM PAGE_AREA WHERE PAGE_ID=:page_id', array('page_id'=>$content_id));
		$this->call_parent('import_undo', array($content_id));
	}

	
	/**
	* Добавляем к блокирующим акциям необходимые
	* @see table::get_lock_actions
	* @return array
	*/
	
	public function get_lock_actions () {
		return array_merge($this->call_parent('get_lock_actions'), array ('content', 'block', 'block_add', 'block_copy', 'meta_change'));
	}
	
	/**
	* Добавляем права, которые должны быть у пользователя для блокировки записи
	* @see table::get_rights_for_lock
	* @return array
	*/
	
	public function get_rights_for_lock () {
		return array_merge($this->call_parent('get_rights_for_lock'), array('meta_change'));
	}
	
	
	
	/**
	* Добавляем к изменяюзим акциям необходимые
	* @see table::get_lock_actions
	* @return array
	*/
	
	public function get_commit_lock_actions() {
		return array_merge($this->call_parent('get_commit_lock_actions'), array ('block_unlink', 'block_added', 'block_copied', 'meta_changed'));;
	}
	
	/**
	* Раздел может разблокировать так же администратор сайта
	* @see table::is_checkinout_admin
	* @return boolean
	*/
	
	public function is_checkinout_admin() {
		$pk = $this->primary_key->get_from_request();
		return $this->call_parent('is_checkinout_admin') || auth::is_site_admin_for($this->obj, $pk[$this->autoinc_name]);
	}
	
}
?>
