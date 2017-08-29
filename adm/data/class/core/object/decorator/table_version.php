<?php
/**
 * Класс декоратор таблиц "Версия в первичном ключе"
 *
 * @package		RBC_Contents_5_0
 * @subpackage core
 * @copyright	Copyright (c) 2006 RBC SOFT
 * @todo сделать механизм корректного отображения записей, тестовая и рабочая версия которых разнесены на разные уровни иерархии. Сейчас выводится все довольно прямолинейно. Хотя, может быть этого и достаточно? Добавить только дополнительное сообщение о том, что версии разнесены на разные ветви иерархии, да ссылки на эти ветви. Хотя появляется возможность редактировать рабочую версию напрямую, что нехорошо. Еще в дереве отображаются обе версии на разных уровнях иерархии. С другой стороны эта ситуация сейчас считается нештатной. Наверно надо предупреждения какие-нибудь выводить о разнесенности версий?
 */
class table_version extends decorator{

	/**
	 * Главная версия - выбранная в фильтре или тестовая, если в фильтре ничего не выбрано
	 * @var int
	 */
	public $main_version;

	/**
	 * Версия выбранная в фильтре или null, если ничего не выбрано
	 * @var int
	 */
	public $version_filter;

	/**
	 * Названия версий, которые есть у записей, выбранных в списке, а также признак их одинаковости
	 * @var array
	 */
	public $r_version_info=array();

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Конструктор. Заранее определяемся с версией которая выбрана главной в данный момент
	 *
	 * @see table::__construct()
	 */
	function __construct(&$full_object, $decorators){
		parent::__construct($full_object, $decorators);
		if($_REQUEST["_f_VERSION"]==="0" || $_REQUEST["_f_VERSION"]==="1"){
			$this->version_filter=(int)$_REQUEST["_f_VERSION"];
		}
		if($_REQUEST["_f_VERSION"]==="0"){
			$this->main_version=0;
		}else{
			$this->main_version=1;
		}
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Добавить запись (после полной проверки)
	 *
	 * Здесь мы форсируем выставление тестовой версии у свежедобавленной записи, а также выставляем ей TIMESTAMP
	 *
	 * @see table::exec_add()
	 */
	public function exec_add($raw_fields, $prefix){
		$last_insert_id=$this->inner_object->exec_add($raw_fields, $prefix);
		db::update_record($this->obj, array("VERSION"=>1, "TIMESTAMP"=>time()), "", array($this->autoinc_name=>$last_insert_id));
		return $last_insert_id;
	}


	/**
	 * Изменить запись (после полной проверки)
	 *
	 * Здесь производится автовыбор версиии с приоритетом тестовой (если версии находятся на одном уровне иерархии), а также выставляется TIMESTAMP
	 *
	 * @see table::exec_change()
	 */
	public function exec_change($raw_fields, $prefix, $pk){
		$this->primary_key->is_record_exists($pk, true);
		$record=$this->full_object->get_change_record($pk);
		$this->inner_object->exec_change(array_merge(array("VERSION"=>$record['VERSION']), $raw_fields), $prefix, array_merge($pk, array("VERSION"=>$record["VERSION"])));
		$this->full_object->touch($pk, $record["VERSION"]);
		
		// Для записей из блочных таблиц очищаем кэш их блоков
		if ( $this -> decorators['block'] && $record['VERSION'] === '0' )
			$this -> full_object -> delete_content_cache( $pk );
	}


	/**
	 * Опубликовать запись
	 *
	 * Работает в обоих случаях - если у нее есть рабочая версия, и если рабочей версии нет.
	 * Если тестовой версии записи не существует, то она будет создана из рабочей, то есть произойдет починка записи.
	 * В любом случае версия, которая должна пересоздаться, вначале удаляется.
	 * Дополнительно проверяется, чтобы для рабочей версии не нарушались ограничения по group_by
	 *
	 * @param array $pk		первичный ключ записи
	 * @todo Обеспечить совместимость специальных типов колонок, в первую очередь блобов.
	 */
	public function exec_publish($pk){
		$this->primary_key->is_record_exists($pk, true);
		$this->full_object->is_permitted_to("publish", $pk, true);
		$this->full_object->is_record_blocked($pk);
		$versions=$this->full_object->get_versions($pk);
		$not_main_version=($versions[0]["VERSION"] ? 0 :1);
		$this->full_object->field_group_check($versions[0], "change", $pk);
		
		// Проверяем существование опубликованных записей в присоединяемых таблицах
		$this -> full_object -> publish_link_table_check( $versions[0] );
		
		db::delete_record($this->obj, array_merge($pk, array("VERSION"=>$not_main_version)));
		$versions[0]["VERSION"]=$not_main_version;
		db::insert_record($this->obj, $versions[0], "");
		
		// Для записей из блочных таблиц очищаем кэш их блоков
		if ( $this -> decorators['block'] )
			$this -> full_object -> delete_content_cache( $pk );
		
		$this->full_object->log_register('publish', array('pk'=>$pk));
	}
	
	/**
	 * Снять с публикации запись
	 *
	 * Если тестовой версии записи не существует, то она будет создана из рабочей, то есть произойдет починка записи
	 *
	 * @param array $pk		первичный ключ записи
	 * @todo Обеспечить совместимость специальных типов колонок, в первую очередь блобов.
	 */
	public function exec_unpublish($pk){
		$this->primary_key->is_record_exists($pk, true);
		$this->full_object->is_permitted_to("unpublish", $pk, true);
		$this->full_object->is_record_blocked($pk);
		$versions=$this->full_object->get_versions($pk);
		
		// Проверяем существование опубликованных записей в подчиненных таблицах
		$this -> full_object -> unpublish_link_table_check( $versions[0] );
		
		if($versions[0]["VERSION"]){
			db::delete_record($this->obj, array_merge($pk, array("VERSION"=>0)));
		}else{
			db::update_record($this->obj, array("VERSION"=>1), "", $pk);
		}
		
		// Для записей из блочных таблиц очищаем кэш их блоков
		if ( $this -> decorators['block'] )
			$this -> full_object -> delete_content_cache( $pk );
		
		$this->full_object->log_register('unpublish', array('pk'=>$pk));
	}
	
	/**
	 * Отменить изменения
	 *
	 * Если тестовой записи нет, то она будет создана, если нет рабочей, то ничего не произойдет.
	 * Дополнительно проверяется, чтобы для тестовой версии не нарушались ограничения по group_by
	 *
	 * @param array $pk		первичный ключ записи
	 * @todo Обеспечить совместимость специальных типов колонок, в первую очередь блобов.
	 */
	public function exec_undo($pk){
		$this->primary_key->is_record_exists($pk, true);
		$this->full_object->is_permitted_to("undo", $pk, true);
		$this->full_object->is_record_blocked($pk);
		$versions=$this->full_object->get_versions($pk);
		if($versions[0]["VERSION"]==="0" || $versions[1]["VERSION"]==="0"){
			$undo_version=$versions[($versions[0]["VERSION"]==="0" ? 0 : 1)]; // То есть, если в нулевой записи версия рабочая, то она и берется, иначе берется следущая запись - первая
			$undo_version["VERSION"]=1;
			$this->full_object->field_group_check($undo_version, "change", $pk);
			db::delete_record($this->obj, array_merge($pk, array("VERSION"=>1)));
			db::insert_record($this->obj, $undo_version, "");
			$this->full_object->log_register('undo', array('pk'=>$pk));
		}
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Действие - публикация записи
	 */
	public function action_publish(){
		$pk=$this->primary_key->get_from_request();
		$this->full_object->exec_publish($pk);
		$this->url->redirect();
	}
	
	/**
	 * Действие - публикует записи массово
	 */
	public function action_group_publish(){
		if(!metadata::$objects[$this->obj]["no_change"]){
			$this->full_object->group_action("exec_publish");
		}
	}
	
	/**
	 * Действие - снятие с публикации
	 */
	public function action_unpublish(){
		$pk=$this->primary_key->get_from_request();
		$this->full_object->exec_unpublish($pk);
		$this->url->redirect();
	}

	/**
	 * Действие - снимает записи с публикации массово
	 */
	public function action_group_unpublish(){
		if(!metadata::$objects[$this->obj]["no_change"]){
			$this->full_object->group_action("exec_unpublish");
		}
	}

	/**
	 * Действие - отмена изменений
	 */
	public function action_undo(){
		$pk=$this->primary_key->get_from_request();
		$this->full_object->exec_undo($pk);
		$this->url->redirect();
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Добавляем в списке колонку "Версия"
	 *
	 * @see table::ext_index_header()
	 */
	public function ext_index_header($mode){
		return array_merge(
			$this->inner_object->ext_index_header($mode), 
			array("version_names"=>array("title"=>metadata::$lang["lang_version"], "type"=>"_list", "escape"=>0))
		);
	}

	/**
	 * Добавляем в запрос списка ограничение по версии. Выбирается главная версия записи
	 *
	 * @see table::ext_index_query()
	 */
	public function ext_index_query(){
		return $this->inner_object->ext_index_query().$this->full_object->version_where_clause();
	}

	/**
	 * Добавляем бинды для версионного ограничения запроса списка.
	 *
	 * @see table::ext_index_query_binds()
	 */
	public function ext_index_query_binds(){
		return array_merge($this->inner_object->ext_index_query_binds(), $this->full_object->version_where_clause_binds(1));
	}

	/**
	 * Добавляем в запрос карточки записи ограничение по версии. Выбирается тестовая версия записи, а если ее нет, то рабочая.
	 *
	 * @see table::ext_change_query()
	 */
	public function ext_change_query(){
		return $this->inner_object->ext_change_query($query).$this->full_object->version_where_clause();
	}

	/**
	 * Добавляем бинды для версионного ограничения запроса карточки.
	 *
	 * @see table::ext_change_query_binds()
	 */
	public function ext_change_query_binds(){
		return array_merge($this->inner_object->ext_change_query_binds(), $this->full_object->version_where_clause_binds());
	}

	/**
	 * Дополнение присоединения таблицы специальными условиями
	 *
	 * У присоединяемой таблицы всегда выбирается версия по общему правилу
	 */
	public function ext_join($head_table, $fk_table_name){
		list($clause, $binds)=$this->inner_object->ext_join($head_table, $fk_table_name);
		$clause.=$this->full_object->version_where_clause(false, $fk_table_name);
		$binds+=$this->full_object->version_where_clause_binds();
		return array($clause, $binds);
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Добавляем в операции кнопки публикации, отмены изменений и снятия публикации, где это требуется
	 *
	 * @see table::get_index_ops()
	 */
	public function get_index_ops($record){
		$ops=$this->inner_object->get_index_ops($record);
		$pk=$this->primary_key->get_from_record($record);
		$version_info=$this->r_version_info[$this->primary_key->get_id_from_record($record)];
		
		if($this->full_object->is_applied_to("publish", false) && $version_info[1] && (!$version_info[0] || $version_info[0]["timestamp"]!==$version_info[1]["timestamp"] || ($this->decorators["external"] && $version_info[0] && $version_info[0]["deleted"])) && !($this->decorators["external"] && $version_info[1]["deleted"]) && $this -> full_object -> is_ops_permited( 'publish', $pk[$this->autoinc_name] ) ) {
			$publish_url=$this->url->get_url("publish", array("pk"=>$pk));
			$ops["_ops"][]=array("name"=>"publish", "alt"=>metadata::$lang["lang_publish"], "url"=>$publish_url);
		}
		if($this->full_object->is_applied_to("unpublish", false) && $version_info[0] && !($this->decorators["external"] && $version_info[0]["deleted"]) && $this -> full_object -> is_ops_permited( 'unpublish', $pk[$this->autoinc_name] ) ) {
			$unpublish_url=$this->url->get_url("unpublish", array("pk"=>$pk));
			$ops["_ops"][]=array("name"=>"unpublish", "alt"=>metadata::$lang["lang_unpublish"], "url"=>$unpublish_url);
		}
		if($this->full_object->is_applied_to("undo", false) && $version_info[0] && ($version_info[0]["timestamp"]!=$version_info[1]["timestamp"]) && !($this->decorators["external"] && $version_info[0]["deleted"]) && $this -> full_object -> is_ops_permited( 'undo', $pk[$this->autoinc_name] ) ) {
			$undo_url=$this->url->get_url("undo", array("pk"=>$pk));
			$ops["_ops"][]=array("name"=>"undo", "alt"=>metadata::$lang["lang_undo"], "url"=>$undo_url);
		}

		return $ops;
	}


	/**
	 * Выбираем из базы набор версий для всех выбранных записей, а также сразу помещаем эти версии в записи
	 *
	 * @see table::get_index_records()
	 */
	public function get_index_records(&$request, $mode, $list_mode, $include=0, $exclude=array()){
		$records=$this->inner_object->get_index_records($request, $mode, $list_mode, $include, $exclude);
		
		if ( $mode == 'index' )
		{
			$this -> not_permited_ids['publish'] = $this -> full_object -> is_permitted_to_mass( 'publish', $this -> index_records_ids );
			$this -> not_permited_ids['unpublish'] = $this -> full_object -> is_permitted_to_mass( 'unpublish', $this -> index_records_ids );
			$this -> not_permited_ids['undo'] = $this -> full_object -> is_permitted_to_mass( 'undo', $this -> index_records_ids );
		}
		
		if(count($records)>0 && $mode!="select2"){// Код не нужно исполнять, если ничего не выбрано, то же самое касается и селектов
			$bind_counter=0;
			foreach($records as $record){
				$ids[$bind_counter]="{$this->obj}.{$this->autoinc_name}=:id_{$bind_counter}";
				$binds["id_{$bind_counter}"]=$record[$this->autoinc_name];
				// Делаем ограничение по языку, если на объекте есть декоратор "lang"
				if($this->decorators["lang"]){
					$ids[$bind_counter]="({$ids[$bind_counter]} AND {$this->obj}.LANG_ID=:lang_{$bind_counter})";
					$binds["lang_{$bind_counter}"]=$record["LANG_ID"];
				}
				$bind_counter++;
				$selected_version[$record[$this->autoinc_name]]=$record["VERSION"];
			}
			$where=join(" OR ", $ids);
			$version_info=db::sql_select("SELECT ".$this->primary_key->select_clause()." FROM {$this->obj} WHERE {$where} ORDER BY {$this->obj}.VERSION DESC", $binds);
			// Сохраняем информацию о версиях записей, чтобы потом использовать ее при формировании кнопки перевода
			foreach($version_info as $vi){
				$selected=($selected_version[$vi[$this->autoinc_name]]==$vi["VERSION"] ? 1 : 0);
				$this->r_version_info[$vi[$this->autoinc_name]][$vi["VERSION"]]=array(
					"title"=>($vi["VERSION"] ? metadata::$lang["lang_test"] : metadata::$lang["lang_work"]),
					"timestamp"=>$vi["TIMESTAMP"],
					"selected"=>$selected) +
						($this->decorators["external"] ? array("deleted"=>$vi["EXTERNAL_IS_DELETED"]) : array());
			}
			
			// На основании информации о версиях формируем соответствующее изображение
			$version_list = array();
			foreach( $this->r_version_info as $id=>$rvi ) {
				if ($rvi[0] && $rvi[1]) {
					if ($rvi[0]['timestamp'] == $rvi[1]['timestamp']) 
						$img_src = 'equal';
					else 
						$img_src = 'not_equal';
						
					if ($rvi[0]['selected']) 
						$img_src .= '_0';
					else 
						$img_src .= '_1';
						
					if ($rvi[0]['timestamp'] == $rvi[1]['timestamp']) 
						$img_map = 'versionmap_equal';
					else 
						$img_map = 'versionmap_not_equal';
				}
				elseif ($rvi[0]) {
					$img_src = 'only_release';
					$img_map = 'versionmap_only_release';
				}
				else {
					$img_src = 'only_test';
					$img_map = 'versionmap_only_test';
				}

				$version_list[$id][] = array( "title" => "<img src=\"/common/adm/img/version/{$img_src}.gif\" ".(($mode=='index')?"usemap=\"#{$img_map}\"":'')."/>" );
			}
			
			// Помещаем информацию о версиях в записи
			foreach($records as $k=>$record){
				$records[$k]["version_names"]=$version_list[$this->primary_key->get_id_from_record($record)];
			}
		}
		return $records;
	}
	
	/**
	 * Дополняем список групповых операций, операциями, специфичными для данного декоратора
	 * 
	 * @see table::get_group_operations
	 */
	public function get_group_operations()
	{
		$operations = array();
		
		$operations['group_publish'] = array( 'title' => metadata::$lang['lang_publish'], 'no_action' => 'no_change', 'confirm_message' => metadata::$lang['lang_confirm_mass_publish'] );
		$operations['group_unpublish'] = array( 'title' => metadata::$lang['lang_unpublish'], 'no_action' => 'no_change', 'confirm_message' => metadata::$lang['lang_confirm_mass_unpublish'] );
		
		return $operations + $this -> inner_object -> get_group_operations();
	}	

	/**
	 * Кляуза, которая позволяет выбирать тестовую версию всегда, когда она есть, в противном случае рабочую.
	 *
	 * Кроме случая, когда передан параметр $use_main_version. В этом случае приоритет будет у главной версии.
	 * Учитываем также наличие декоратора "lang"
	 *
	 * @param boolean $use_main_version		приоритет главной версии
	 * @param string $table_name			Названиет таблицы в запросе. Если не указано, то используется $this->obj
	 * @return string
	 */
	public function version_where_clause($use_main_version=false, $table_name=""){
		static $counter; // Нужен для случая, когда кляуза собирается несколько раз в одном запросе (соединение двух и более таблиц с тестовыми весиями)
		$counter++;
		$table_name=($table_name ? $table_name : $this->obj);
		if($this->parent_id!==""){
			$parent_field=metadata::$objects[$this->obj]["parent_field"];
			$force_hierarchy="AND {$this->obj}.{$parent_field}=L.{$parent_field}";
		}
		$clause=" {$table_name}.VERSION=:main_version_1_{$counter} ";
		if(!is_numeric($this->version_filter)){
			$clause.=" OR ({$table_name}.VERSION=:not_main_version_{$counter} AND NOT EXISTS
					(SELECT 1 FROM {$this->obj} L WHERE L.VERSION=:main_version_2_{$counter} AND L.{$this->autoinc_name}={$table_name}.{$this->autoinc_name} {$force_hierarchy} ".($this->decorators["lang"] ? " AND L.LANG_ID={$table_name}.LANG_ID" : "").")
				)";
		}
		$clause=" AND ({$clause}) {$level_clause} ";
		return $clause;
	}

	/**
	 * Переменные привязки для выборки тестовой версии всегда, когда она есть, в противном случае рабочей.
	 *
	 * Кроме случая, когда передан параметр $use_main_version. В этом случае приоритет будет у главной версии
	 *
	 * @param boolean $use_main_version		приоритет главной версии
	 * @return array
	 */
	public function version_where_clause_binds($use_main_version=false){
		static $counter; // Нужен для случая, когда кляуза собирается несколько раз в одном запросе (соединение двух и более таблиц с тестовыми весиями)
		$counter++;
		if($use_main_version){
			$main_version=$this->main_version;
			$not_main_version=($this->main_version ? 0 : 1);
		}else{
			$main_version=1;
			$not_main_version=0;
		}
		$binds["main_version_1_{$counter}"]=$main_version;
		if(!is_numeric($this->version_filter)){
			$binds["main_version_2_{$counter}"]=$main_version;
			$binds["not_main_version_{$counter}"]=$not_main_version;
		}
		return $binds;
	}

	/**
	 * Возвращает все версии записи с приоритетом тестовой версии (она первая)
	 *
	 * Все версии возвращаются ДАЖЕ в том случае, если они находятся на РАЗНЫХ уровнях иерархии
	 *
	 * @param array $pk		первичный ключ записи
	 * @return array
	 */
	public function get_versions($pk){
		return db::sql_select("SELECT {$this->obj}.* FROM {$this->obj} WHERE ".$this->primary_key->where_clause()." ORDER BY {$this->obj}.VERSION DESC", $this->primary_key->bind_array($pk));
	}

	/**
	 * Выставляет TIMESTAMP записи на текущее время (как признак того, что запись была изменена)
	 *
	 * Если версия не указана, то трогается более приоритетная версия (по обычному правилу), иначе трогается именно та версия, что указана.
	 * С явной версией будет работать быстрее, так как не будет выполняться запрос определения приоритетной версии
	 *
	 * @param array $pk		первичный ключ записи
	 * @param int $version	версия
	 * @return array
	 */
	public function touch($pk, $version=""){
		$where=$this->primary_key->where_clause();
		$binds=$this->primary_key->bind_array($pk);
		$where.=" AND VERSION=:version ";
		if($version!==""){
			$binds+=array("version"=>$version);
		}else{
			$versions=$this->full_object->get_versions($pk);
			$binds+=array("version"=>$versions[0]["VERSION"]);
		}
		db::sql_query("UPDATE {$this->obj} SET TIMESTAMP=:timestamp WHERE {$where}", $binds+array("timestamp"=>time()));
	}
	
	/**
	* Получает дополнительные данные для лога
	*/
	public function get_additional_info_for_log($fields) {
		if (!isset($fields['log_params']['version']))
			$fields['log_params']['version']=($fields['VERSION'])? $fields['VERSION'] : 1;
		return $this->inner_object->get_additional_info_for_log($fields);
	}
	
	/**
	 * Метод проверяет существование в присоединяемых таблицах с декоратором "Версия"
	 * опубликованных записей, на который ссылается текущая запись из подчиненной таблицы
	 *
	 * @param array $record		текущая запись
	 */
	public function publish_link_table_check( $record )
	{
		$pk = $this -> primary_key -> get_from_record( $record );
		
		// Проходимся по всем полям подчиненной таблицы
		foreach ( metadata::$objects[$this -> obj]['fields'] as $field_name => $field_item )
		{
			// Условие срабатывает только для заполненых или обязательных полей,
			// которые указывают на присоединяемую таблицу с декоратором "Версия"
			if ( ( $field_item['type'] == 'select2' ) && ( $record[$field_name] || ( $field_item['errors'] & _nonempty_ ) ) &&
				isset( metadata::$objects[$field_item['fk_table']]['decorators']['version'] ) )
			{
				// Получаем объект присоединяемой таблицы
				$fk_table = object::factory( $field_item['fk_table'] );
				
				// Проверяем, опубликована ли соответствующая запись в присоединяемой таблице
				if ( !lib::is_record_exists( $fk_table -> obj, array( $fk_table -> autoinc_name => $record[$field_name], 'VERSION' => 0 ) ) )
					throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_no_publush_link_table'] . ': "' .
						$this -> full_object -> get_record_title( $pk ) . '" (' . $this -> primary_key -> pk_to_string( $pk ) . ')' );
						
				$fk_table -> __destruct();
			}
		}
	}
	
	/**
	 * Метод проверяет существование в подчиненных таблицах с декоратором "Версия"
	 * опубликованных записей, ссылающихся на текущую запись в присоединяемой таблице
	 *
	 * @param array $record		текущая запись
	 */
	public function unpublish_link_table_check( $record )
	{
		$pk = $this -> primary_key -> get_from_record( $record );
		
		// Проходимся по всем подчиненных таблицам
		foreach ( metadata::$objects[$this -> obj]['links'] as $link_name => $link_item )
		{
			$link_table_name = $link_item['secondary_table'] ? $link_item['secondary_table'] : $link_name;
			$link_table_field = $link_item['secondary_field'] ? $link_item['secondary_field'] : $this -> autoinc_name;
			
			// Проверяем, нет ли у подчиненной таблицы декоратора "Версия" и нет ли в ней опубликованных записей, ссылающихся на текущую
			if ( isset( metadata::$objects[$link_table_name]['decorators']['version'] ) &&
					lib::is_record_exists( $link_table_name, array( $link_table_field => $record[$this -> autoinc_name], 'VERSION' => 0 ) ) )
				throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_no_unpublush_link_record'] . ': "' .
					$this -> full_object -> get_record_title( $pk ) . '" (' . $this -> primary_key -> pk_to_string( $pk ) . ')' );
		}
	}
	
	/**
	 * Расширяем шаблон таблицы map-ом
	 *
	 * @see table::ext_index_template()
	 */
	public function ext_index_template($tpl_output, &$smarty)
	{
		$tpl_output = $this -> inner_object -> ext_index_template ( $tpl_output, $smarty );
		$old_version=$_REQUEST["_f_VERSION"];
		$_REQUEST["_f_VERSION"]=1;
		$test_url=$this->url->get_url("", array("no_from"=>1));
		$_REQUEST["_f_VERSION"]=0;
		$release_url=$this->url->get_url("", array("no_from"=>1));
		$_REQUEST["_f_VERSION"]=$old_version;
		
		$lang_test_version = metadata::$lang['lang_test_version'];
		$lang_work_version = metadata::$lang['lang_work_version'];
		$lang_version_equal = metadata::$lang['lang_version_equal'];
		$lang_version_not_equal = metadata::$lang['lang_version_not_equal'];

		$html .= <<<HTM
		<map id="versionmap_equal" name="versionmap_equal">
			<area shape="rect" coords="0, 0, 11, 13" href="{$test_url}" alt="{$lang_version_equal}" title="{$lang_version_equal}"/>
			<area shape="rect" coords="17, 0, 33, 13" href="{$release_url}" alt="{$lang_version_equal}" title="{$lang_version_equal}"/>
		</map>
		
		<map id="versionmap_not_equal" name="versionmap_not_equal">
			<area shape="rect" coords="0, 0, 11, 13" href="{$test_url}" alt="{$lang_version_not_equal}" title="{$lang_version_not_equal}"/>
			<area shape="rect" coords="17, 0, 33, 13" href="{$release_url}" alt="{$lang_version_not_equal}" title="{$lang_version_not_equal}"/>
		</map>
		
		<map id="versionmap_onlytest" name="versionmap_only_test">
			<area shape="rect" coords="0, 0, 11, 13" href="{$test_url}" alt="{$lang_test_version}" title="{$lang_test_version}"/>
		</map>
		
		<map id="versionmap_onlywork" name="versionmap_only_release">
			<area shape="rect" coords="17, 0, 33, 13" href="{$work_url}" alt="{$lang_work_version}" title="{$lang_work_version}"/>
		</map>		
HTM;

		return $tpl_output . $html;
	}
	
	/**
	* Удаляем из информации для экспорта поле VERSION
	*/
	
	public function get_fields_for_export() {
		$fields = $this->inner_object->get_fields_for_export();
		unset($fields['VERSION']);
		return $fields;
	}	
}
?>