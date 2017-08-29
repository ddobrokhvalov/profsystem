<?php
/**
 * В этом файле помимо собственно класса table описаны операции над данными и элементарные права, которые им соответствуют
 *
 * @package		RBC_Contents_5_0
 * @subpackage core
 * @copyright	Copyright (c) 2006 RBC SOFT
 * 
 * Стандартные операции над данными (дополнительно указаны элементарные типы прав, которые проверяются в is_permitted_to())
 * (константы не заведены, потому что 1. правам не нужно складываться, 2. могут появляться новые права - распыление констант произойдет):
 * 
 * add				add<br>
 * copy				add<br>
 * change			change<br>
 * delete			delete<br>
 * group_delete		delete<br>
 * m2m				change<br>
 * 
 * publish		- начиная с версии		publish<br>
 * unpublish	- начиная с версии		publish<br>
 * undo			- начиная с версии		change<br>
 * 
 * translate	- начиная с языка		add
 *
 * resolve		- начиная с воркфлоу	resolve
 * 
 * Также существует право view, позволяющее видеть записи. Для массовой проверки прав add и view неприменимы
 * 
 * Действия, которые не требуют специальной проверки прав:<br>
 * link			- начиная с блоков<br>
 * tree			- учитывать иерархию при операциях
 *
 * Группы методов<br>
 * exec_	- элементарные операции, образуют API объекта<br>
 * ext_		- заглушки, которые переопределяются с целью точечной модификации функционала<br>
 * action_	- группа методов, вызываемых диспетчером по запросу из командной строки. Метод выбирается на основе
 * переменной запроса "action". Диспетчер проверяет наличие метода класса, который должен называться
 * "action _".$_REQUEST["action"]. Если метод не указан явно, то диспетчер будет вызывать action_index().
 * Методы этой группы должны заполнять переменные $this->body (html-код с результатами своей работы), $this->title (название страницы).
 */

/**
 * Класс обработчиков таблиц
 *
 * @package		RBC_Contents_5_0
 * @copyright	Copyright (c) 2006 RBC SOFT
 */
class table extends object{

	/**
	 * Идентификаторы выбранных записей через запятую
	 * 
	 * Метод table::get_index_records() заполняет это свойство кляузой IN (без обрамляющих скобок) для
	 * того чтобы удобно было организовать довыборку каких-либо данных для модификации списка записей.
	 * Если ни одной записи не было выбрано, то свойство будет содержать ноль, что и соответствует невыбору записей
	 * @var string
	 */
	public $index_records_in=0;

	/**
	 * Массив идентификаторов выбранных записей
	 * @var array
	 */
	public $index_records_ids = array();

	/**
	 * Массив идентификаторов записей, над которыми пользователь не имеет права совершать определенные действия
	 * 
	 * Первоначально заполняется в table::4(), может быть расширен дополнительными операциями в соответствующих декораторах
	 * @var array
	 */
	public $not_permited_ids = array();

	/**
	 * Название автоинкрементного поля таблицы
	 * @var string
	 */
	public $autoinc_name;

	/**
	 * Количество записей возвращенное get_index_records(). Используется в get_index_counter() для возвращения числа записей в тех режимах, где выбираются все записи без страничной навигации
	 * @var int
	 */
	public $index_counter="";

	/**
	 * Разделитель полей в коротком списке
	 * @var int
	 * @todo Стоит подумать о более навороченном способе разделения полей в кратком списке, чтобы, например, можно было по данным из четырех полей получать строки типа "Петров, Петр Петрович (менеджер)"
	 */
	public $short_title_delimiter=", ";
	
	/**
	* Указывает методу html_card(), что данные для формы нужно брать из $_REQUEST
	* @var array
	*/
	public $from_request = false;	
	
	/**
	* Хранит параметры, которые необходимо передать в систему журналирования
	* @var array
	*/
	private $log_params = array();	
	

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Конструктор. Заполняем специальные свойства таблицы
	 *
	 * @param string $obj			Название конструируемого объекта
	 * @param object object $full_object	Ссылка на полный объект, если ее нет, то в качестве такой ссылки используется сам конструируемый объект
	 */
	function __construct($obj, $full_object=""){
		parent::__construct($obj, $full_object);
		// Автоинкрементное поле
		$this->autoinc_name=$this->primary_key->get_autoincrement_name();
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Добавить запись (после полной проверки). Возвращает идентификатор последней добавленной записи
	 * 
	 * Внимание: Если у добавляемой записи явно указано значение автоинкрементного поля, то функция возвратит неопределенное значение.
	 * В случае неуспеха вызвает исключение
	 * 
	 * @param array $raw_fields	Сырые данные, например, $_REQUEST
	 * @param string $prefix	Префикс, которым дополнены сырые данные, например, _form_ для формы
	 * @return int
	 * @todo Сделать в этом методе и его наследниках параметр $prefix необязательным
	 */
	public function exec_add($raw_fields, $prefix){
		$this->full_object->is_applied_to("add");
		$this->full_object->is_permitted_to("add", array(metadata::$objects[$this->obj]["parent_field"]=>$raw_fields[$prefix.metadata::$objects[$this->obj]["parent_field"]]), true);
		$fields=$this->full_object->get_prepared_fields($raw_fields, $prefix, "add");
		$this->full_object->field_group_check($fields, "add");
		db::insert_record($this->obj, $fields);
		$fields[$this->autoinc_name]=db::last_insert_id($this->obj."_SEQ");
		
		// Присоединяем теги к добавленной записи
		if ( $this -> is_taxonomy && isset( $raw_fields[$prefix . 'TAG'] ) )
			$this -> full_object -> set_tags( array( $this -> autoinc_name => $fields[$this -> autoinc_name] ), $raw_fields[$prefix . 'TAG'] );
		
		$this->full_object->log_register('add', $fields);
		return $fields[$this->autoinc_name];
	}
	

	/**
	 * Скопировать запись (после полной проверки). Возвращает идентификатор последней скопированной записи
	 * 
	 * Использует exec_add(). Введен, чтобы можно было переопределить его при каком-то сложном случае копирования.
	 * $pk здесь не используется - введен также для удобства переопределяющих методов.
	 * 
	 * @param array $raw_fields	Сырые данные, например, $_REQUEST
	 * @param string $prefix	Префикс, которым дополнены сырые данные, например, _form_ для формы
	 * @param array $pk			Первичный ключ копируемой записи
	 * @return int
	 * @todo Сделать в этом методе и его наследниках параметр $prefix необязательным
	 */
	public function exec_copy($raw_fields, $prefix, $pk){
		return $this->full_object->exec_add($raw_fields, $prefix);
	}

	/**
	 * Изменить запись (после полной проверки). В случае неуспеха вызвает исключение
	 *
	 * Если таблица иерархическая, то родитель изменяется у ВСЕХ версий записи, включая языковые. Этот функционал выполняется здесь, потому что он не зависит от декораторов и освобождает из от этого действия
	 *
	 * @param array $raw_fields	Сырые данные, например, $_REQUEST
	 * @param string $prefix	Префикс, которым дополнены сырые данные, например, _form_ для формы
	 * @param array $pk			Первичный ключ, который определяет изменяемую запись
	 */
	public function exec_change($raw_fields, $prefix, $pk){
		$this->full_object->is_applied_to("change");
		$this->primary_key->is_record_exists($pk, true);
		$this->full_object->is_permitted_to("change", $pk, true);
		$this->full_object->is_record_blocked($pk);
		
		$fields=$this->full_object->get_prepared_fields($raw_fields, $prefix, "change");
		$this->full_object->field_group_check($fields, "change", $pk);
		db::update_record($this->obj, $fields, "change", $pk);
		
		// Присоединяем теги к обновленной записи
		if ( $this -> is_taxonomy && isset( $raw_fields[$prefix . 'TAG'] ) )
			$this -> full_object -> set_tags( $pk, $raw_fields[$prefix . 'TAG'] );
		
		$this->full_object->log_register('change', array_merge($fields, array('pk'=>$pk)));
		
		// Добиваемся, чтобы во всех версиях был одинаковый родитель
		if($this->parent_id!==""){
			$parent_field=metadata::$objects[$this->obj]["parent_field"];
			db::update_record($this->obj, array($parent_field=>$raw_fields[$prefix.$parent_field]), "", array($this->autoinc_name=>$pk[$this->autoinc_name]));
		}
	}

	/**
	 * Удалить запись (после полной проверки). В случае неуспеха вызвает исключение
	 * 
	 * Вызов этого метода должен осуществляться ПОСЛЕ всех внешних переопределений, потому что именно он
	 * вызывает финализацию удаления (см. table::ext_finalize_delete()) и физически удаляет запись.
	 *
	 * Таким образом, наследники exec_delete() должны выполнять
	 * только проверки (в крайнем случае независимые действия, вроде пребилдов)
	 * Дополнительные действия модификации чего-либо ВСЕГДА должны описываться в ext_finalize_delete().
	 * 
	 * @param array $pk			Первичный ключ, который определяет удаляемую запись
	 * @todo Сделать автоматическое удаление зависимых записей для линков с cascade. Пока сделано только на один уровень, надо поисследовать - нельзя ли на произвольную глубину.
	 * @todo Надо ли запрещать удаление одной из языковых версий при наличии зависимых таблиц без языка, если остается еще одна языковая версия? Сейчас запрещается
	 * @todo Надо ли запрещать удаление последней языковой версии записи, если зависимая запись есть только на другом языке? Сейчас не запрещается
	 */
	public function exec_delete($pk, $partial=false){
		$this->full_object->is_applied_to("delete");
		$this->primary_key->is_record_exists($pk, true);
		$this->full_object->is_permitted_to("delete", $pk, true);
		$this->full_object->is_record_blocked($pk);

		// Проверяем наличие детей. Если дети есть, то не разрешаем удаление
		if($this->parent_id!==""){
			$children=db::sql_select("SELECT COUNT(*) AS COUNTER FROM {$this->obj} WHERE ".metadata::$objects[$this->obj]["parent_field"]."=:pk_value", array("pk_value"=>$pk[$this->autoinc_name]));
			if($children[0]["COUNTER"]){
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_delete_with_children"].": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
			}
		}
		// Не позволяем удаление, если это нельзя делать по причине ссылок
		if(is_array(metadata::$objects[$this->obj]["links"])){
			foreach(metadata::$objects[$this->obj]["links"] as $link_name=>$link){
				if(!$link["on_delete_cascade"] && !$link["on_delete_clear"] && !$link["on_delete_ignore"]){
					$secondary_table=($link["secondary_table"] ? $link["secondary_table"] : $link_name);
					$secondary_field=($link["secondary_field"] ? $link["secondary_field"] : $this->autoinc_name);
					list($ext_pk_clause, $ext_pk_binds)=$this->primary_key->ext_pk_for_children($secondary_table, $pk); // Сейчас только для языка работает
					$children=db::sql_select("SELECT COUNT(*) AS COUNTER FROM {$secondary_table} WHERE {$secondary_field}=:pk_value {$ext_pk_clause}", array("pk_value"=>$pk[$this->autoinc_name])+$ext_pk_binds);
					if($children[0]["COUNTER"]){
						throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_delete_with_children_table"]." ".$secondary_table.": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
					}
				}
			}
		}
		// Выполняем финализацию удаления, вычислив его полноту
		$exist_other_versions=db::sql_select("SELECT COUNT(*) AS COUNTER FROM {$this->obj} WHERE {$this->autoinc_name}=:ai AND NOT (".$this->primary_key->where_clause().")", $this->primary_key->bind_array($pk)+array("ai"=>$pk[$this->autoinc_name]));

		$this->full_object->log_register('delete', array('pk'=>$pk));
		
		$this->full_object->ext_finalize_delete($pk, $exist_other_versions[0]["COUNTER"] ? true : false);
		// Собственно удаление
		$delete_response=db::delete_record($this->obj, $pk);
		return $delete_response;
	}
	
	/**
	 * Изменить порядок записи
	 * 
	 * @param array $pk			Первичный ключ, определяющий перемещаемую запись
	 * @param string $dir		Направление перемещения записи ('up' или 'down')
	 */
	public function exec_order( $pk, $dir = 'up' )
	{
		$order_field = $this -> full_object -> get_order_field();
		if ( $order_field == false )
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_order_field"].": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
		
		// Получаем перемещаемую запись
		$record = $this -> full_object -> get_change_record( $pk, true );
		
		// Учитываем направление перемещения
		$order_oper = $dir == "up" ? "<" : ">"; $order_sort = $dir == "up" ? "desc" : "asc";
		$order_where = array( "{$order_field} {$order_oper} :{$order_field}" );
		$order_binds = array( $order_field => $record[$order_field] );
		
		// Если необходимо, подмешиваем в запись идентификатор ее главного блока
		if ( metadata::$objects[$this -> obj]['decorators']['block'] )
			$record['INF_BLOCK_ID'] = $this -> full_object -> get_main_block_id( $pk, true );
		
		// Учитываем группировку порядкового поля
		$group_by = metadata::$objects[$this -> obj]['fields'][$order_field]['group_by'];
		list( $group_where, $group_joins, $group_binds ) =
			$this -> full_object -> get_group_where( $group_by, $record, '' );
		$order_where[] = $group_where; $order_binds += $group_binds;
		
		// Собираем все условия воедино
		$order_where = join( ' and ', $order_where ) . $this -> full_object -> ext_index_query();
		$order_binds = $order_binds + $this -> full_object -> ext_index_query_binds();
		
		if( !$this -> auth -> is_main_admin )
		{
			// Исключаем из списка записей те, на которые нет прав
			$all_records = db::sql_select( "select {$this -> autoinc_name} from {$this -> obj} {$group_joins}
				where {$order_where}", $order_binds );
			$all_records_ids = array_keys( lib::array_reindex( $all_records, $this -> autoinc_name ) );
			$not_permitted_ids = $this -> full_object -> is_permitted_to_mass( 'change', $all_records_ids );
			$not_permitted_in = lib::array_make_in( $not_permitted_ids );
			
			$order_where .= " and {$this -> autoinc_name} not in ( {$not_permitted_in} )";
		}
		
		// Определяем ближайшую запись со стороны перемещения
		$near_records = db::sql_select( "select * from {$this -> obj} {$group_joins}
			where {$order_where} order by {$order_field} {$order_sort} limit 1", $order_binds );
		
		if ( count( $near_records ) )
		{
			// Получаем первичный ключ ближайшей записи
			$near_pk = $this -> primary_key -> get_from_record( $near_records[0] );
			
			// Получаем ближайшую запись
			$near_record = $this -> full_object -> get_change_record( $near_pk, true );
			
			// Меняем местами значения порядковых полей
			$record_order = $record[$order_field];
			$record[$order_field] = $near_record[$order_field];
			$near_record[$order_field] = $record_order;
			
			// Сохраняем записи, поменяв местами порядковое поле
			$this -> full_object -> exec_change( $record, '', $pk );
			$this -> full_object -> exec_change( $near_record, '', $near_pk );
		}
		else
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_order_record"].": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
	}
	
	/**
	 * Выставить или убрать связи многие-ко-многим (после полной проверки). В случае неуспеха вызвает исключение
	 * 
	 * $values, $p_ids, $s_ids, $t_ids на одинаковых ключах содержат значения, относящиеся к одной и той же записи м2м.
	 * Возвращает список ключей записей, для на которые не прав и для которых операция не была выполнена
	 * 
	 * @param string $m2m_name		Название связи
	 * @param array $values			Значения - 0/1
	 * @param array $p_ids			Идентификаторы первичной таблицы
	 * @param array $s_ids			Идентификаторы вторичной таблицы
	 * @param array $t_ids			Идентификаторы третичной таблицы
	 * @return array
	 * @todo Может быть придумать какое-нибудь решение, которое не будет делать много запросов при удалении и вставке большого количества записей, а как-нибудь за раз
	 * @todo При блокированных записях, помещенных в $na_records 
	 */
	public function exec_m2m($m2m_name, $values, $p_ids, $s_ids, $t_ids=array()){
		// Приводим данные к нормальному виду
		if(!is_array($values)){$values=array();}
		if(!is_array($p_ids)){$p_ids=array();}
		if(!is_array($s_ids)){$s_ids=array();}
		if(!is_array($t_ids)){$t_ids=array();}
		$na_records=array();
		$m2m=metadata::$objects[$this->obj]["m2m"][$m2m_name];
		$m2m_table=($m2m["m2m_table"] ? $m2m["m2m_table"] : $m2m_name);
		// Проверяем записи первичной таблицы
		$unique_p_ids=array_unique($p_ids);
		$ne_p_ids=$this->primary_key->are_ids_exist($unique_p_ids);
		$na_p_ids=$this->full_object->is_permitted_to_mass("change", $unique_p_ids);
		$blocked = $this->mass_check_on_block($this, $p_ids);
		$primary_m2m_field=($m2m["primary_m2m_field"] ? $m2m["primary_m2m_field"] : $this->autoinc_name);
		// Проверяем записи вторичной таблицы
		$secondary_instance=object::factory($m2m["secondary_table"]);
		$unique_s_ids=array_unique($s_ids);
		$ne_s_ids=$secondary_instance->primary_key->are_ids_exist($unique_s_ids);
		$na_s_ids=$secondary_instance->is_permitted_to_mass("change", $unique_s_ids);
		$blocked += $this->mass_check_on_block($secondary_instance, $s_ids);
		$secondary_m2m_field=($m2m["secondary_m2m_field"] ? $m2m["secondary_m2m_field"] : $secondary_instance->autoinc_name);
		$secondary_instance->__destruct();
		// Проверяем записи третичной таблицы
		if($m2m["tertiary_table"]){
			$unique_t_ids=array_unique($t_ids);
			$tertiary_instance=object::factory($m2m["tertiary_table"]);
			$ne_t_ids=$tertiary_instance->primary_key->are_ids_exist($unique_t_ids);
			$na_t_ids=$tertiary_instance->is_permitted_to_mass("change", $unique_t_ids);
			$blocked += $this->mass_check_on_block($tertiary_instance, $t_ids);
			$tertiary_m2m_field=($m2m["tertiary_m2m_field"] ? $m2m["tertiary_m2m_field"] : $tertiary_instance->autoinc_name);
			$tertiary_instance->__destruct();
		}
		
		
		$inserted=$deleted=array();
		// Выполняем операцию для каждого набора связей. 
		foreach($values as $key=>$value){
			if(in_array($p_ids[$key], array_merge($na_p_ids,$ne_p_ids)) || in_array($s_ids[$key], array_merge($na_s_ids,$ne_s_ids)) || ($m2m["tertiary_table"] && in_array($t_ids[$key], array_merge($na_t_ids,$ne_t_ids))) || in_array($key, $blocked)){
				$na_records[]=array("primary"=>$p_ids[$key], "secondary"=>$s_ids[$key], "tertiary"=>$t_ids[$key]);
			}else{
				if($m2m["tertiary_table"]){
					$m2m_pk=array($primary_m2m_field=>$p_ids[$key], $secondary_m2m_field=>$s_ids[$key], $tertiary_m2m_field=>$t_ids[$key]);
				}else{
					$m2m_pk=array($primary_m2m_field=>$p_ids[$key], $secondary_m2m_field=>$s_ids[$key]);
				}
				
				// Собственно изменение
				if (db::delete_record($m2m_table, $m2m_pk) ) {
						if (!$value) $m2m_changed[$p_ids[$key]]['deleted'][]=$m2m_pk;
				}
				else {
					if ($value) $m2m_changed[$p_ids[$key]]['inserted'][]=$m2m_pk;
				}

				
				if($value==1){
					db::insert_record($m2m_table, $m2m_pk);
				}
			}
		}

		if (sizeof($m2m_changed)) 
			$this->full_object->log_register('m2m', array('m2m'=>$m2m, 'm2m_changed'=>$m2m_changed, 'm2m_name'=>$m2m_name));
		
		return $na_records;
	}

	/**
	* Попытка блокирования записи. В случае удачи возвращает true, иначе false
	*
	* @param array $pk			Первичный ключ, который определяет блокируемую запись
	* @return boolean 			true - запись удачно заблокирована, false - запись заблокирована кем-то другим
	* @todo На данный момент запись блокируется только от других пользователей, но не от самого пользователя. А что делать если несколько пользователей
	* работают под одним логином? Или пользователь работает с одной и той же записью в нескольких формах. Возникнут проблемы с разблокировкой
	*/
	
	public function exec_block_record ($pk) {
		if (!$this->full_object->is_checkinout_table()) return true;

		// проверка прав
		$has_rights = false;

		$needed_rights = $this->full_object->get_rights_for_lock();
		foreach ($needed_rights as $r) {
			if ($this->full_object->is_permitted_to($r, $pk))
				$has_rights = true;
		}
		
		if (!$has_rights) return false;
		
		$this->exec_delete_lock_by_time();
		
		if ($this->is_record_blocked($pk, false, $locked_record)) return false;
		
		if (!sizeof($locked_record)) {
			$tim = strftime('%Y%m%d%H%M%S');
			db::insert_record(
				'LOCKED_RECORD', 
				array (
					'TE_OBJECT_ID'=>self::$te_object_ids[$this->obj]['TE_OBJECT_ID'], 
					'CONTENT_ID'=>$pk[$this->autoinc_name],
					'LANG_ID'=>$pk['LANG_ID']?$pk['LANG_ID']:0,
					'AUTH_USER_ID'=>$this->auth->user_info['AUTH_USER_ID'],
					'LOCK_STARTED'=> $tim,
					'LOCK_LAST_ACCESSED'=>$tim
				)
			);
		}
		else 
			$this->exec_prolong_block ($pk);
		return true;
	}
	
	/**
	* Удаление всех устаревших записей из лока
	*/

	public function exec_delete_lock_by_time() {
		if (!$this->full_object->is_checkinout_table()) return;
		db::sql_query('
			DELETE 
				FROM 
					LOCKED_RECORD 
			WHERE 
				LOCK_LAST_ACCESSED<:max_time_for_lock',
			array(
				'max_time_for_lock'=>strftime('%Y%m%d%H%M%S', time()-params::$params['lock_timeout']['value']), 
			)
		);
	}


	/**
	* Разблокирование записи
	*
	* @param array $pk			Первичный ключ, который определяет блокируемую запись
	* Если пользователь является чекинаут админом, то разблокируется запись в любом случае, 
	* иначе только если запись была заблокирована текущим пользователем
	* В случае если таблица с декоратором lang и не указан в первичном ключе LANG_ID, то разблокируются все версии записи
	* @return int Кол-во удаленных записей, если таблица не блокируемая, то всегда 1
	*/
	
	public function exec_unblock_record ($pk) {
		if (!$this->full_object->is_checkinout_table()) return 1;
		
		$this->primary_key->where_clause();
		
		$sql = '
			DELETE 
				FROM 
					LOCKED_RECORD
			WHERE 
				TE_OBJECT_ID=:te_object_id 
					AND 
						CONTENT_ID=:content_id
		';
		
		$bind = array (
				'te_object_id'=>self::$te_object_ids[$this->obj]['TE_OBJECT_ID'],
				'content_id'=>$pk[$this->autoinc_name],
		);

		if ($pk['LANG_ID']) {
			$sql .= ' AND LANG_ID=:lang_id';
			$bind['lang_id'] = $pk['LANG_ID'];
		}
		
		
		if (!$this->full_object->is_checkinout_admin()) {
			$sql .= ' AND AUTH_USER_ID=:auth_user_id';
			$bind['auth_user_id'] = $this->auth->user_info['AUTH_USER_ID'];
		}
		

	
		$res=db::sql_query($sql, $bind);
		return $res;
	}
	
	/**
	* Пытаемся продолжить блокирование
	*
	* @param array $pk			Первичный ключ, который определяет блокируемую запись
	* @return int Удалось или нет
	*/
	
	public function exec_prolong_block ($pk) {
		if (!$this->full_object->is_checkinout_table()) return -1;
		return db::update_record(
			'LOCKED_RECORD', 
			array (
				'LOCK_LAST_ACCESSED'=>strftime('%Y%m%d%H%M%S')
			),
			array(),
			array (
				'TE_OBJECT_ID'=>self::$te_object_ids[$this->obj]['TE_OBJECT_ID'], 
				'CONTENT_ID'=>$pk[$this->autoinc_name],
				'LANG_ID'=>$pk['LANG_ID']?$pk['LANG_ID']:0,			
				'AUTH_USER_ID'=>$this->auth->user_info['AUTH_USER_ID']
			)
		);
	}
	


///////////////////////////////////////////////////////////////////////////////////////////////////////////
		
	/**
	 * Общий метод для массовых операций над записям
	 */
	public function group_action($exec_action){
		$errors=array();
		$group_pks=$this->primary_key->get_group_from_request();
		foreach($group_pks as $pk){
			try{
				$this->full_object->$exec_action($pk["pk"]);
			}catch(Exception $e){
				$errors[]=array("id"=>$pk["pk"][$this->autoinc_name], "error"=>$e->getMessage());
			}
		}
		if(count($errors)>0){
			$this->body=$this->error_report($errors);
		}else{
			$this->url->redirect();
		}
	}

	public function group_action_form( $form_fields, $exec_action, $action_title, $form_name = '', $js = '' )
	{
		if( metadata::$objects[$this->obj]['no_change'] ) return;
		
		$group_pks = $this -> primary_key -> get_group_from_request();
		if ( !count( $group_pks ) ) $this -> url -> redirect();
		
		foreach( $group_pks as $group_pk )
		{
			$pk_array = array();
			foreach( $group_pk['pk'] as $pk_name => $pk_value )
 					$pk_array[] = $pk_value;
			$pk_for_hidden['pk']['group_id_' . join( '_', $pk_array )] = 1;
		}
		
		// Собираем форму редактирования
		if ( !$form_name )
			$form_name = html_element::get_next_form_name();
		
		$html_fields = html_element::html_fields( $form_fields, $this -> tpl_dir . 'core/html_element/html_fields.tpl', $this -> field );
		$form = html_element::html_form( $html_fields, $this -> url -> get_hidden( $exec_action, $pk_for_hidden ), $this -> tpl_dir . 'core/html_element/html_form.tpl', true );

		$operations = $this -> get_record_operations( $form_name );
		
		$this -> path_id_array[] = array( 'TITLE' => $action_title );
		
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'title', $action_title );
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'form', $form . $js );
		
		$this -> title = $action_title;
		$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' );
	}
	
	/**
	 * Переместить конкретную запись при груповом перемещении
	 */
	public function make_group_move($pk){
		$record = $this -> get_change_record( $pk );
		foreach ( $record as $field_name => $field_value )
			if ( $field_name != metadata::$objects[$this->obj]['parent_field'] )
				$_REQUEST[ '_form_' . $field_name ] = $field_value;
		$this -> full_object -> exec_change( $_REQUEST, '_form_', $pk );
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Позволяет дополнить заголовок списка записей новыми колонками
	 *
	 * @param string $mode	Режим списка записей. См. get_index_modifiers()
	 * @return array
	 */
	public function ext_index_header($mode){
		return array();
	}

	/**
	 * Позволяет дополнить кляузу WHERE запроса выборки данных в списке данных специальным ограничением
	 *
	 * @return string
	 */
	public function ext_index_query(){
		return "";
	}

	/**
	 * Позволяет дополнить набор переменных привязки для запроса выборки списка записей
	 *
	 * @return array
	 */
	public function ext_index_query_binds(){
		return array();
	}

	/**
	 * Дополнение присоединения таблицы специальными условиями
	 *
	 * @param string $head_table		Название головной таблицы (к которой присоединение должно выполняться)
	 * @param string $fk_table_name		Название присоединяемой таблицы в кляузе выбоки записей
	 * @return array
	 */
	public function ext_join($head_table, $fk_table_name){
		return array("", array());
	}

	/**
	 * Куски запроса и переменные привязки для непрямого получения поля, например, для переводимых таблиц
	 *
	 * Возвращаемый формат: array("таблица.поле", array("кляуза поиска"), array("кляуза присоединения таблицы), array("переменные привязки"))
	 *
	 * @param string $field_name	Название получаемого поля
	 * @param string $f_counter		Счетчик полей во внешнем запросе
	 * @return array
	 */
	public function ext_field_selection($field_name, $f_counter){
		return array("", array(), array(), array());
	}

	/**
	 * Позволяет дополнить кляузу WHERE запроса выборки данных в карточке редактирования специальным ограничением
	 *
	 * @return string
	 */
	public function ext_change_query(){
		return "";
	}

	/**
	 * Позволяет дополнить набор переменных привязки для запроса выборки данных в карточке редактирования
	 *
	 * @return array
	 */
	public function ext_change_query_binds(){
		return array();
	}

	/**
	 * Кляуза для ограничения списка записей по параметру list_mode
	 *
	 * @param string $mode		Режим списка записей. См. get_index_modifiers()
	 * @param string $list_mode	Параметр $list_mode для модификации списка записей (указывается в def-файле)
	 * @return array
	 */
	public function ext_index_by_list_mode($mode, $list_mode){
		$clause="";
		$binds=array();
		if(is_array($list_mode)) {
			if($list_mode["LANG_ID"]){
				$clause.=" AND {$this->obj}.LANG_ID=:list_mode_lang_id ";
				$binds+=array("list_mode_lang_id"=>$list_mode["LANG_ID"]);
			}
			if (isset($list_mode["direct"])) {
				$in=$list_mode["direct"];
        	
				if(is_array($list_mode["direct"]))
					$in=(count($list_mode["direct"])==0 ? "0" : join(", ", $list_mode["direct"]));
					
				$field = $this->autoinc_name;
				if (isset($list_mode["direct_field"])) 
					$field = $list_mode["direct_field"];
        	
				$clause.=" AND {$this->obj}.{$this->autoinc_name} IN (".$in.")";			
			}
		}
		return array($clause, $binds);
	}

	/**
	 * Позволяет достроить набор полей для формы (например, довывести поля наложения резолюции) и добавить java script к карточке
	 *
	 * @param string $form_name		Название формы
	 * @param array $record			Запись, для которой надо достроить поля
	 * @return array
	 */
	public function ext_html_card($form_name, $record){
		return array(array(), "");
	}

	/**
	 * Финализация удаления
	 *
	 * Метод, в котором должны быть описаны какие-либо действия, которые обязательно должны быть выполнены непосредственно
	 * перед уделением записи из БД, например какое-либо удаление с файловой системы или из БД.
	 *
	 * Вынесено в отдельный метод, потому что в декорированной матрешке exec_delete() проверка (и бросок исключения)
	 * могут осуществляться на любой стадии, в то время как ext_finalize_delete() вызывается именно тогда, когда все проверки
	 * уже выполнены.
	 *
	 * $partial - частичная финализация удаления. Выполняется в том случае, если остаются какие-либо версии записи (сейчас
	 * это касается только языковых версий записи). В таком случае нужно удалить то, что относится непосредственно к
	 * первичному ключу, например, дочерние записи с таким же языком, но нельзя удалять то, что относится к
	 * автоинкрементному идентификатору целиком, например, многие-ко-многим.
	 *
	 * @param array $pk			Первичный ключ, который определяет удаляемую запись
	 * @param boolean $partial	Частичная финализаця
	 * @return array
	 * @todo Попробовать убрать понимание декоратора "язык" из этого метода.
	 */
	public function ext_finalize_delete($pk, $partial=false){
		if(!$partial){
			// Удаляем записи из м2м таблиц
			if(is_array(metadata::$objects[$this->obj]["m2m"])){
				foreach(metadata::$objects[$this->obj]["m2m"] as $m2m_name=>$m2m){
					if(!$m2m["on_delete_ignore"]){
						$primary_m2m_field=($m2m["primary_m2m_field"] ? $m2m["primary_m2m_field"] : $this->autoinc_name);
						$m2m_table=($m2m["m2m_table"] ? $m2m["m2m_table"] : $m2m_name);
						db::delete_record($m2m_table, array($primary_m2m_field=>$pk[$this->autoinc_name]));
					}
				}
			}
		}
		// Работа со ссылками
		if(is_array(metadata::$objects[$this->obj]["links"])){
			foreach(metadata::$objects[$this->obj]["links"] as $link_name=>$link){
				$secondary_table=($link["secondary_table"] ? $link["secondary_table"] : $link_name);
				$secondary_field=($link["secondary_field"] ? $link["secondary_field"] : $this->autoinc_name);
				// Если установлен флаг "on_delete_ignore", то пропускаем эту ссылку
				if($link["on_delete_ignore"]){
					continue;
				}
				// Операции производятся, если удаление полное, либо если у зависимой таблицы есть декоратор язык (некрасиво, но пока так)
				if(!$partial || metadata::$objects[$secondary_table]["decorators"]["lang"]){
					list($ext_pk_clause, $ext_pk_binds)=$this->primary_key->ext_pk_for_children($secondary_table, $pk); // Сейчас только для языка работает
					// Если установлен флаг "on_delete_clear", очищаем подчиненные таблицы, заполняя соответствующие поля нулями
					if($link["on_delete_clear"]){
						db::update_record($secondary_table, array($secondary_field =>0), "", array($secondary_field=>$pk[$this->autoinc_name])+$ext_pk_binds);
					}
					// Если установлен флаг "on_delete_cascade", удаляем записи из подчиненных таблиц (пока только на один уровень)
					if($link["on_delete_cascade"]){
						db::delete_record($secondary_table, array($secondary_field=>$pk[$this->autoinc_name])+$ext_pk_binds);
					}
				}
			}
		}
		
		// Удаляем привязанные к записи теги
		if ( $this -> is_taxonomy )
			$this -> full_object -> clear_tags( $pk );
	}
	
	/**
	 * Метод расширения стандартного шаблона отображения таблицы
	 *
	 * @param string $tpl_output	скомпилированный шаблон
	 * @param array $record			объект шаблонизатора
	 * @return array
	 */
	public function ext_index_template($tpl_output, &$smarty)
	{
	    return $tpl_output;
	}

	/**
	 * Метод расширения запроса выборки записей из м2м. Возвращает кляузу и переменные привязки
	 *
	 * Внимание: вызывается и из первичной таблицы, и из вторичной
	 *
	 * @param string $m2m	описание связи из def-файла
	 * @return array
	 */
	public function ext_m2m($m2m){
	    return array("", array());
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Наследник диспетчера, обезпечивает блокирование/разблокирование записей
	* Добавляем действия, связанные с блокировкой
	* Если акция находится в списке блокирующих, то перед ее вызовом блокируется запись по первичному ключу из запроса
	* Если акция находится в списке изменяющих, то перед ее вызовом проверяется, была ли заблокирована запись ранее пользователем
	* Если не была - то выдается ошибка о превышении времени блокировки
	* @see object::dispatcher
	*/

	public function dispatcher () {
		$method_name=preg_replace("/[^a-z0-9_]+/i", "", $_REQUEST["action"]);
		
		if (in_array($method_name, $this->full_object->get_lock_actions())) {
			// блокируем запись, если акция в списке блокирующих
			$this->full_object->exec_block_record($this->primary_key->get_from_request(true));
		}
        
		if (in_array($method_name, $this->full_object->get_commit_lock_actions())) {
			// разблокируем запись, если не удачно, значит время блокировки истекло
			$this->full_object->unblock_user_record($this->primary_key->get_from_request(true));
		}
		parent::dispatcher();
	}


///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Действие - список записей текущей таблицы (страница по умолчанию)
	 * 
	 * Используется для отображения страницы по умолчанию, если в $_REQUEST["action"] ничего
	 * нет или есть что-то, что не опознается в классе. 
	 * Подходит для всех типов таблиц, а утилитами должен переопределяться
	 */
	public function action_index(){
		// Сбор заголовка таблицы
		$headers=$this->full_object->get_index_header("index");
		
		// Получение записей и их числа
		$records=$this->full_object->get_index_records($_REQUEST, "index", "");
global $bench;//
$bench->register(bench::bencher("all_parts"), "records extracted", 1);//
		$counter=$this->full_object->get_index_counter($_REQUEST, "index", "");
$bench->register(bench::bencher("all_parts"), "counter", 1);//
		
		// Сбор полного массива с записями
		$done_records=array();
		
		// Для иерархических таблиц дополняем заголовок страницы названием родительской записи
		if ( metadata::$objects[$this->obj]['parent_field'] && $this -> parent_id )
		{
			$parent_info = $this -> full_object -> get_parent_info( $this -> parent_id );
			
			$this -> title = $parent_info[0]['TITLE'];
			
			// Если не инициирован поиск по всем записям
			if ( !$_REQUEST["_f__ALL_LEVELS"] )
			{
				foreach(metadata::$objects[$this->obj]["fields"] as $field_name=>$field){
					if($field["show"] || $field["is_main"]){
						$first_show_field = $field_name; break;
					}
				}
				
				// Добавляем первой строкой ссылку на родительский уровень
				$done_records[] = array( $first_show_field => metadata::$lang['lang_go_up'],
					'_hier_url' => $this -> url -> get_url( '', array(
						'parent_id' => $parent_info[0][metadata::$objects[$this -> obj]['parent_field']],
							'from' => $_GET['prev_from'] ? $_GET['prev_from'] : 1 ) ), '_icon' => 'up' );
				
				// Дополняем статусную строку путем от текущего уровня до корня
				$this -> path_id_array += $this -> full_object -> get_parents_list( $parent_info );
			}
		}
		else
			$this -> title = metadata::$objects[$this->obj]['title'];
		
		$c=1;
		$order_field = $this -> full_object -> get_order_field();
		$invert_order = $this->sort_field == $order_field && $this->sort_ord == 'desc';
		foreach($records as $key=>$record){
			$number = $c+($this->from-1)*$this->rows_per_page; $c++;
			
			// Готовим данные для колонки порядкового поля
			if ( $order_field !== false && isset( $record[$order_field] ) ) {
				$pk=$this->primary_key->get_from_record($record);
				$record[$order_field] = array_merge( array( "order" => $record[$order_field] ),
					($number!=1 && !$_REQUEST["_f__ALL_LEVELS"] &&
						!metadata::$objects[$this -> obj]["no_change"] && !metadata::$objects[$this -> obj]["fields"][$order_field]["no_change"] &&
						$this->is_ops_permited("change", $pk[$this->autoinc_name])) ? array("up"=>$this->url->get_url("order",array("pk"=>$pk + array("dir"=>$invert_order?"down":"up")))) : array(),
					($number!=$counter && !$_REQUEST["_f__ALL_LEVELS"] &&
						!metadata::$objects[$this -> obj]["no_change"] && !metadata::$objects[$this -> obj]["fields"][$order_field]["no_change"] &&
						$this->is_ops_permited("change", $pk[$this->autoinc_name])) ? array("down"=>$this->url->get_url("order",array("pk"=>$pk + array("dir"=>$invert_order?"up":"down")))) : array() );
			}
			
			$done_records[]=array_merge(
				array("_number"=>$number, "_group"=>array("id"=>$this->primary_key->get_string_from_record($record))),
				$record,
				$this -> format_index_ops( $this->full_object->get_index_ops($record) ),
				(metadata::$objects[$this->obj]["parent_field"] ? array("_hier_url"=>$this->url->get_url("",array("parent_id"=>$this->primary_key->get_id_from_record($record),"prev_from"=>$_GET["from"]))) : array())
			);
		}
$bench->register(bench::bencher("all_parts"), "records done", 1);//
		
		$index_operations = $this -> full_object -> get_index_operations();
		
		// Если не определены групповые операции, то и не за чем выводить колонку чекбоксов
		if ( !count( array_intersect( array_keys( $index_operations ),
				array_keys( $this -> full_object -> get_group_operations() ) ) ) )
			unset( $headers['_group'] );
		
		
		if (!$this->is_ops_exists($done_records)) 
			unset ($headers['_ops']);
			
		
		$tpl = new smarty_ee( metadata::$lang, array( $this->full_object, 'ext_index_template' )  );
		
		$tpl -> assign( 'title', $this -> get_title() );
		$tpl -> assign( 'filter', html_element::html_filter( $this ) );
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $index_operations, 'label' => 'top' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $index_operations, 'label' => 'bottom' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		
		$tpl -> assign( 'table', html_element::html_table( array( 'header' => $headers, 'list' => $done_records, 'counter' => $counter, 'html_hidden' => $this -> url -> get_hidden( 'group_delete' ), "hier_table" => isset( metadata::$objects[$this->obj]["parent_field"] ) ), $this -> tpl_dir . 'core/html_element/html_table.tpl', $this -> field ) );
		$tpl -> assign( 'navigation', lib::page_navigation( $this -> rows_per_page, $counter, 'from', $this -> tpl_dir . 'core/html_element/html_navigation.tpl' ) );
		
		if ( !$this -> title )
			$this -> title = metadata::$objects[$this -> obj]['title'];
			
		$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_grid.tpl' );
		
$bench->register(bench::bencher("all_parts"), "table done", 1);//
	}

	/**
	 * Действие - карточка добавления
	 */
	public function action_add(){
		if($this->full_object->is_applied_to("add", false)){
			list($this->title, $this->body)=$this->full_object->html_card("add", $_REQUEST);
		}
	}

	/**
	 * Действие - карточка копирования
	 */
	public function action_copy(){
		if(metadata::$objects[$this->obj]["copy"]){
			list($this->title, $this->body)=$this->full_object->html_card("copy", $_REQUEST);
		}
	}

	/**
	 * Действие - карточка редактирования
	 */
	public function action_change(){
		if($this->full_object->is_applied_to("change", false)){
			$pk = $this->primary_key->get_from_request();
			$this->full_object->is_permitted_to("change", $pk, true);
			list($this->title, $this->body)=$this->full_object->html_card("change", $_REQUEST);
		}
	}

	/**
	 * Действие - карточка редактирования
	 */
	public function action_view(){
		if(metadata::$objects[$this->obj]["view"]){
			list($this->title, $this->body)=$this->full_object->html_card("view", $_REQUEST);
		}
	}

	/**
	 * Действие - добавляет новую запись
	 */
	public function action_added(){
		if($this->full_object->is_applied_to("add", false)){
			try{
				$metadata=metadata::$objects[$this->obj];
				$this->full_object->exec_add($_REQUEST, "_form_");
				$this->url->redirect();
			}catch(Exception $e){
				metadata::$objects[$this->obj]=$metadata;
				$this->from_request = true; $this->full_object->action_add();
				$this->body = object::html_error($e->getMessage(), $e->getFile(), $e->getLine(), '', $e->getTraceAsString(), false).$this->body;
			}
		}
	}

	/**
	 * Действие - копирование записи
	 */
	public function action_copied(){
		if(metadata::$objects[$this->obj]["copy"]){
			try{
				$metadata=metadata::$objects[$this->obj];
				$pk=$this->primary_key->get_from_request(true);
				$this->full_object->exec_copy($_REQUEST, "_form_", $pk);
				$this->url->redirect();
			}catch(Exception $e){
				metadata::$objects[$this->obj]=$metadata;
				$this->from_request = true; $this->full_object->action_copy();
				$this->body = object::html_error($e->getMessage(), $e->getFile(), $e->getLine(), '', $e->getTraceAsString(), false).$this->body;
			}
		}
	}
	
	/**
	 * Действие - изменяет существующую запись
	 */
	public function action_changed(){
		if($this->full_object->is_applied_to("change", false)){
			try{
				$metadata=metadata::$objects[$this->obj];
				$pk=$this->primary_key->get_from_request(true);
				$this->full_object->exec_change($_REQUEST, "_form_", $pk);
				$this->url->redirect( "", array( "restore_params" => 1 ) );
			}catch(Exception $e){
				$_REQUEST["action"]="change";
				metadata::$objects[$this->obj]=$metadata;
				$this->full_object->exec_block_record($this->primary_key->get_from_request(true));
				$this->from_request = true; $this->full_object->action_change();
				$this->body = object::html_error($e->getMessage(), $e->getFile(), $e->getLine(), '', $e->getTraceAsString(), false).$this->body;
			}
		}
	}

	/**
	 * Действие - изменяет существующую запись и снова возвращается на страницу редактрования
	 */
	public function action_changed_apply(){
		if($this->full_object->is_applied_to("change", false)){
			try{
				$metadata=metadata::$objects[$this->obj];
				$pk=$this->primary_key->get_from_request(true);
				$this->full_object->exec_change($_REQUEST, "_form_", $pk);
				$this->url->redirect("change", array("pk"=>$pk));
			}catch(Exception $e){
				$_REQUEST["action"]="change";
				metadata::$objects[$this->obj]=$metadata;
				$this->full_object->exec_block_record($this->primary_key->get_from_request(true));
				$this->from_request = true; $this->full_object->action_change();
				$this->body = object::html_error($e->getMessage(), $e->getFile(), $e->getLine(), '', $e->getTraceAsString(), false).$this->body;
			}
		}
	}

	/**
	 * Действие - удаляет запись
	 */
	public function action_delete(){
		if($this->full_object->is_applied_to("delete", false)){
			$pk=$this->primary_key->get_from_request();
			$this->full_object->exec_delete($pk);
			$this->url->redirect();
		}
	}

	/**
	 * Действие - удаляет записи массово
	 */
	public function action_group_delete(){
		if($this->full_object->is_applied_to("delete", false)){
			$this->full_object->group_action("exec_delete");
		}
	}

	/**
	 * Действие - карточка массового перемещения записей
	 */
	public function action_group_move()
	{
		$form_fields = $this -> full_object -> get_form_fields( 'group_move', '_form_',
			array( metadata::$objects[$this->obj]["parent_field"] => $this -> parent_id ), '',
			array( metadata::$objects[$this->obj]["parent_field"] =>
				metadata::$objects[$this->obj]['fields'][metadata::$objects[$this->obj]["parent_field"]] ) );
		
		$this -> full_object -> group_action_form( $form_fields, 'group_moved', metadata::$lang['lang_move_records'] );
	}

	/**
	 * Действие - перемещает записи массово
	 */
	public function action_group_moved()
	{
		if($this->full_object->is_applied_to("change", false)){
			$this->full_object->group_action("make_group_move");
		}
	}
	
	/**
	 * Действие - изменяет порядок записи
	 */
	public function action_order(){
		if($this->full_object->is_applied_to("change", false)){
			if($this->full_object->get_order_field()!== false){
				$pk=$this->primary_key->get_from_request();
				$this->full_object->exec_order($pk, $_REQUEST["dir"]);
			}
			$this->url->redirect();
		}
	}
	
	/**
	 * Действие - строит связь M:M
	 *
	 * Внимание: поскольку связь многие-ко-многим строится по вторичной таблице, но от лица первичной (чтобы не происходил
	 * уход из нее), то во многих местах этого метода применяется не $this->, а $secondary_instance->.
	 *
	 * @todo Переменные привязки в IN
	 * @todo попробовать упростить код на стадии сбора полного массива с записями
	 */
	public function action_m2m(){
		$main_blocked = $this->is_record_blocked($this->primary_key->get_from_request(true));
		
		$m2m=metadata::$objects[$this->obj]["m2m"][$_REQUEST["m2m"]];
		// Получаем информацию о записи из первичной таблицы, о названиях полей, об инстансе вторичной таблицы
		list($m2m_table, $primary_id, $primary_title, $primary_m2m_field, $secondary_m2m_field, $secondary_instance)=$this->get_m2m_data();
		// Сбор заголовка таблицы
		$headers=$secondary_instance->get_index_header("m2m");
		if($m2m["tertiary_table"]){
			list($tertiary_m2m_field, $tertiary_clause, $tertiary_records)=$this->full_object->get_m2m_tertiary_data();
			$tertiary_instance=object::factory($m2m["tertiary_table"]);
			foreach($tertiary_records as $tr)
				$headers["_m2m_{$tr["_VALUE"]}"]=array("title"=>$tr["_TITLE"], "type"=>"_group", "column"=>$tr["_VALUE"], 'blocked'=>$tertiary_instance->is_record_blocked($tertiary_instance->primary_key->get_from_record($tr), false));
		}else{
			$headers["_m2m"]=array("title"=>"", "type"=>"_group");
		}
		// Модификатор запроса
		list($m2m_ext_clause, $m2m_ext_binds)=$this->full_object->ext_m2m($m2m);		
		// Добавляем особый list_mode для фильтрации отмеченных записей
		if ( $_REQUEST["_f_RECORDS"] ){
			$m2m["list_mode"]["m2m_records"] = ( $_REQUEST["_f_RECORDS"] == "unchecked" ? "not " : "" ) .
				"exists ( select * from {$m2m_table} where {$m2m_table}.{$secondary_m2m_field} = {$secondary_instance->obj}.{$secondary_instance->autoinc_name} and {$m2m_table}.{$primary_m2m_field} = {$primary_id} {$tertiary_clause} {$m2m_ext_clause})";
			$m2m["list_mode"]["m2m_records_binds"] = $m2m_ext_binds;
		}
		// Записи вторичной таблицы
		$secondary_records=$secondary_instance->get_index_records($_REQUEST, "m2m", $m2m["list_mode"]);
		
		$secondary_counter=$secondary_instance->get_index_counter($_REQUEST, "m2m", $m2m["list_mode"]);
		$secondary_clause=" AND {$m2m_table}.{$secondary_m2m_field} IN ({$secondary_instance->index_records_in}) ";
		// Сбор информации о существующих связях
		$exist_links=db::sql_select("SELECT * FROM {$m2m_table} WHERE {$primary_m2m_field}=:primary_id {$secondary_clause} {$tertiary_clause} {$m2m_ext_clause}", array("primary_id"=>$primary_id)+$m2m_ext_binds);
		foreach($exist_links as $el){
			$r_exist_links[$el[$secondary_m2m_field]."_".$el[$tertiary_m2m_field]]=$el; // Если связываются две таблицы, то $tertiary_m2m_field не будет ничего содержать, равно как и $el[$tertiary_m2m_field]
		}
		// Получение потушенных чекбоксов
		$disabled_method="ext_disabled_".$_REQUEST["m2m"];
		if(method_exists($secondary_instance, $disabled_method)){
			$r_disabled=$secondary_instance->$disabled_method($primary_id);
			$p_disabled=$secondary_instance->is_permitted_to_mass('change',$secondary_instance->index_records_ids);
			foreach ( $p_disabled as $p_id )
				$r_disabled[$p_id] = 1;
		}
		
		// Сбор полного массива с записями
		$done_records=array();
		$c=1;
		foreach($secondary_records as $key=>$record){
			$id=$record["_VALUE"];
			$checkboxes=array();
			$blocked=$main_blocked || $secondary_instance->is_record_blocked($secondary_instance->primary_key->get_from_record($record), false);
			
			if($m2m["tertiary_table"]){
				foreach($tertiary_records as $tr){
					if(isset($r_disabled[$id][$tr["_VALUE"]])){
						$disabled=1;
						$checked=$r_disabled[$id][$tr["_VALUE"]];
					}else{
						$disabled=0;
						$checked=($r_exist_links[$id."_".$tr["_VALUE"]] ? 1 : 0);
					}
					$blocked_line = $blocked || $headers["_m2m_{$tr['_VALUE']}"]['blocked'];
					$checkboxes["_m2m_{$tr["_VALUE"]}"]=array("id"=>"{$id}_{$tr["_VALUE"]}", "checked"=>$checked, "disabled"=>$blocked_line?$blocked_line:$disabled, "hidden"=>1);
				}
			}else{
				if(isset($r_disabled[$id])){
					$disabled=1;
					$checked=$r_disabled[$id];
				}else{
					$disabled=0;
					$checked=($r_exist_links[$id."_"] ? 1 : 0);
				}
				$checkboxes=array("_m2m"=>array("id"=>$id, "checked"=>$checked, "disabled"=>$blocked?$blocked:$disabled, "hidden"=>1));
			}
			
			$done_records[] = array_merge(
								array("_number"=>$c+($this->from-1)*$this->rows_per_page), 
								$record, 
								$checkboxes,
								( metadata::$objects[$secondary_instance->obj]["parent_field"] 
									? 
										array(
											"_hier_url"=>$secondary_instance->url->get_url(
												"m2m",
												array(
													"obj"=>$this->obj, 
													"m2m_name" => $_REQUEST["m2m"], 
													"pk" => $this->primary_key->get_from_request(), 
													"parent_id"=>$secondary_instance->primary_key->get_id_from_record($record),
													"prev_from"=>$_GET["from"]
												)
											)
										) 
									: 
									array()
								)
							  );
			$c++;
		}
		
		$operations = array();
		
		if( metadata::$objects[$secondary_instance -> obj]['parent_field'] && $secondary_instance -> parent_id )
		{
			$parent_info = $secondary_instance -> get_parent_info();
			
			$this -> title = $parent_info[0]['TITLE'];
			
			// Если не инициирован поиск по всем записям
			if ( !$_REQUEST["_f__ALL_LEVELS"] )
			{
				$done_records = array_merge( array( array( '_TITLE' => metadata::$lang['lang_go_up'],
					'_hier_url' => $secondary_instance -> url -> get_url( '', array( 'm2m' => 1, 'short' => 1,
						'parent_id' => $parent_info[0][metadata::$objects[$secondary_instance -> obj]['parent_field']],
							'from' => $_GET['prev_from'] ? $_GET['prev_from'] : 1 ) ), '_icon' => 'up' ) ), $done_records );
			}
		}
		else
			$this -> title = metadata::$lang['lang_change_record'];
		
		$operations +=  $this->get_operations(array('apply', 'cancel'), 'checkbox_form');
		
		// Добавляем виртуальное поле для фильтрации отмеченных записей
		metadata::$objects[$secondary_instance->obj]["fields"]["RECORDS"] = array(
			"title" => metadata::$lang["lang_records"], "type" => "select1", "virtual" => 1, "filter_short" => 1, "value_list" => array(
				array( "title" => metadata::$lang["lang_checked_records"], "value" => "checked" ), array( "title" => metadata::$lang["lang_unchecked_records"], "value" => "unchecked" ) ) );
		
		$this -> path_id_array[] = array( 'TITLE' => $primary_title );
		
		$tpl = new smarty_ee( metadata::$lang, array( $this->full_object, 'ext_index_template' ) );
		
		$tpl -> assign( 'title', $primary_title );
		$tpl -> assign( 'form_name', 'checkbox_form' );
		$tpl -> assign( 'tabs', $this -> full_object -> get_header_tabs( $this -> primary_key -> get_from_request(), $_REQUEST['m2m'] ) );
		$tpl -> assign( 'filter', html_element::html_filter( $secondary_instance, true ) );
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'form', html_element::html_table( array( 'header' => $headers, 'list' => $done_records, 'counter' => $secondary_counter, 'html_hidden' => $secondary_instance -> url -> get_hidden( '', array( 'm2m' => 1, 'short' => 1 ) ), "hier_table" => isset( metadata::$objects[$secondary_instance->obj]["parent_field"] ) ), $this -> tpl_dir . 'core/html_element/html_table.tpl', $secondary_instance -> field ) );
		$tpl -> assign( 'navigation', ( lib::page_navigation( $this -> rows_per_page, $secondary_counter, 'from', $this -> tpl_dir . 'core/html_element/html_navigation.tpl', 7, true ) ) );
		$this->set_blocked_tpl_params($tpl, $this->primary_key->get_from_request());
		
		$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' );
		
		$secondary_instance -> __destruct();
		if ($tertiary_instance)
			$tertiary_instance->__destruct();
	}

	/**
	 * Действие - укладывает изменения связи M:M в БД
	 *
	 * Внимание: поскольку связь многие-ко-многим строится по вторичной таблице, но от лица первичной (чтобы не происходил
	 * уход из нее), то во многих местах этого метода применяется не $this->, а $secondary_instance->.
	 *
	 * @todo Придумать как правильно выводить информацию о записях, которые не удалось поменять, так как на них нет прав
	 */
	public function action_m2med_apply(){
		$m2m=metadata::$objects[$this->obj]["m2m"][$_REQUEST["m2m"]];
		// Получаем информацию о записи из первичной таблицы, о названиях полей, об инстансе вторичной таблицы
		list($m2m_table, $primary_id, $primary_title, $primary_m2m_field, $secondary_m2m_field, $secondary_instance)=$this->get_m2m_data();
		if($m2m["tertiary_table"]){
			list($tertiary_m2m_field, $tertiary_clause, $tertiary_records)=$this->full_object->get_m2m_tertiary_data();
		}
		// Чекбоксы
		$group_pks=$secondary_instance->primary_key->get_group_from_request(true, true);
		// Формирование $this->index_records_in для использования ее в $this->full_object->$disabled_method
		foreach($group_pks as $pk){
			if($m2m["tertiary_table"]){
				if(preg_match("/(\d+)_(\d+)/", $pk["pk"][$secondary_instance->autoinc_name], $matches)){
					$in[]=$matches[1];
				}
			}else{
				$in[]=$pk["pk"][$secondary_instance->autoinc_name];
			}
		}
		// "Обманываем" вторичную таблицу - как будто она вынимала записи
		if(is_array($in)){
			$secondary_instance->index_records_in=join(", ", $in);
		}
		// Получение потушенных чекбоксов
		$disabled_method="ext_disabled_".$_REQUEST["m2m"];
		if(method_exists($secondary_instance, $disabled_method)){
			$r_disabled=$secondary_instance->$disabled_method($primary_id);
		}
		// Готовимся изменять информацию в БД
		foreach($group_pks as $pk){
			// Собираем идентификаторы и потушенность
			if($m2m["tertiary_table"]){
				if(preg_match("/(\d+)_(\d+)/", $pk["pk"][$secondary_instance->autoinc_name], $matches)){
					$s_id=$matches[1];
					$t_id=$matches[2];
					$disabled=$r_disabled[$pk["pk"][$secondary_instance->autoinc_name]][$matches[2]];
				}else{
					continue;
				}
			}else{
				$s_id=$pk["pk"][$secondary_instance->autoinc_name];
				$disabled=$r_disabled[$pk["pk"][$secondary_instance->autoinc_name]];
			}
			// Помещаем в массивы
			if(!$disabled){
				$values[]=$pk["value"];
				$p_ids[]=$primary_id;
				$s_ids[]=$s_id;
				if($m2m["tertiary_table"]){
					$t_ids[]=$t_id;
				}
			}
		}
		
		// Собственно изменение
		try{
			$metadata=metadata::$objects[$this->obj];
			$na_records=$this->full_object->exec_m2m($_REQUEST["m2m"], $values, $p_ids, $s_ids, $t_ids);
			if(count($na_records)>0){
				foreach($na_records as $na_record){
					$errors[]=array("id"=>$na_record["primary"].", ".$na_record["secondary"].($m2m["tertiary_table"] ? ", ".$na_record["tertiary"] : ""), "error"=>metadata::$lang["lang_operation_not_permitted"]);
				}
				$this->body=$this->error_report($errors);
			}else{
				metadata::$objects[$secondary_instance->obj]["fields"]["RECORDS"] = array( "filter_short" => 1 );
				$secondary_instance->url->redirect("", array("m2m"=>1,"short"=>1));
			}
			$secondary_instance -> __destruct();
		}catch(Exception $e){
			$_REQUEST["action"]="m2m";
			metadata::$objects[$this->obj]=$metadata;
			$this->full_object->exec_block_record($this->primary_key->get_from_request(true));
			$this->from_request = true; $this->full_object->action_m2m();
			$this->body = object::html_error($e->getMessage(), $e->getFile(), $e->getLine(), '', $e->getTraceAsString(), false).$this->body;
		}
	}

	/**
	* Операция разблокирования записи
	*/
	
	public function action_unblock_record () {
		if (!$this->full_object->is_checkinout_table()) return;

		if (!$this->full_object->is_checkinout_admin()) 
			throw new Exception(metadata::$lang['lang_access_denied']);
		
		$pk=$this->primary_key->get_from_request();
		$this->full_object->exec_unblock_record($pk);
		$this->url->redirect("change", array("pk"=>$pk, 'restore_params'=>1));
	}

	/**
	 * Действие - строит дерево записей. Применимо только для иерархических таблиц.
	 * @todo может быть надо написать красивое сообщение о невозможности работы этого метода на неиерархической таблице?
	 * @todo попробовать унифицировать подсчет номера записи - "_number"
	 */
	public function action_tree(){
		if($this->parent_id!==""){
			// Сбор заголовка таблицы
			$headers=$this->full_object->get_index_header("tree");
			
			// Устанавливаем ограничение по поддереву
			if(isset($_REQUEST[$this->autoinc_name])){
				$include=$_REQUEST[$this->autoinc_name];
			}else{
				$include=0;
			}

			$done_records = $this->full_object->get_tree_records ($include);

			// Изготовление заголовка
			$this->title=metadata::$lang["lang_records_tree"];
			
			// Заполение свойств отображения информации
			$operations = $this->get_operations(array('back'));
			
			$title = $this -> get_title();
			$this -> path_id_array[] = array( 'TITLE' => $title );
			
			// Дополняем статусную строку путем от текущего уровня до корня
			if ( metadata::$objects[$this->obj]['parent_field'] && $this -> parent_id )
				$this -> path_id_array = array_merge( $this -> path_id_array,
					$this -> full_object -> get_parents_list( $this -> full_object -> get_parent_info( $this -> parent_id ) ) );
			
			$tpl = new smarty_ee( metadata::$lang, array( $this->full_object, 'ext_index_template' )  );
			
			$tpl -> assign( 'title', $title );
			$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
			$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
			$tpl -> assign( 'table', html_element::html_table( array( 'header' => $headers, 'list' => $done_records, 'counter' => $counter, 'html_hidden' => $this -> url -> get_hidden() ), $this -> tpl_dir . 'core/html_element/html_table.tpl', $this -> field ) );
			
			$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_grid.tpl' );
		}
	}
	
	
	/**
	* Возвращает данные для дерева записей.
	* @param int $include Идентификатор родителя, с которого нужно начининать строить дерево
	* @return array
	*/
	
	public function get_tree_records ($include=0, $list_mode="") {
		// Получение записей и их числа
		$records=$this->full_object->get_index_records($_REQUEST, "tree", $list_mode, $include);
		$counter=$this->full_object->get_index_counter($_REQUEST, "tree", $list_mode, $include);
		// Сбор полного массива с записями
		$done_records=array();
		$c=1;
		foreach($records as $key=>$record){
			$id=$this->primary_key->get_id_from_record($record);
			$done_records[]=array_merge(
				array("_number"=>$c+($this->from-1)*$this->rows_per_page),
				$record
			);
			$c++;
		}
		
		return $done_records;		
	}
	

	/**
	* Переписываем метод command_ping для cheking/checkout
	* Продолжаем блокировку записи
	* @see object::command_ping
	* @todo В случае проблем - вернуть какое-то сообщение клиенту
	*/
	
	public function command_ping ( $mark = '') {
		parse_str($_REQUEST['params'], $params);
		if (sizeof($params) && in_array($params['action'], $this->full_object->get_lock_actions()) && $params['blocking']) {
			$pk=$this->primary_key->get_from_record($params);
			$res = $this->full_object->exec_prolong_block($pk);
			if ($res===0)
				return html_element::xml_response( '<alertmsg><![CDATA['.metadata::$lang['lang_can_not_prolong_block'].']]></alertmsg>', $mark );
		}
		
		return html_element::xml_response( '', $mark );
	}
	
	/**
	* Разблокируем запись, вызывается при уходе со страницы
	*/
	
	public function command_unblock_record ( $mark = '') {
		if ($this->full_object->is_checkinout_table()) {
			parse_str($_REQUEST['params'], $params);
			if (sizeof($params) && in_array($params['action'], $this->full_object->get_lock_actions())) {
				$pk=$this->primary_key->get_from_record($params);
				$this->full_object->unblock_user_record($pk, false);
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
			if (sizeof($params) && in_array($params['action'], $this->full_object->get_lock_actions())) {
				$pk=$this->primary_key->get_from_record($params);
				if ($this->full_object->is_record_blocked($pk, false)) 
					return html_element::xml_response( '<![CDATA[blocked]]>', $mark );
				$this->full_object->exec_block_record($pk);
			}
		}
		
		return html_element::xml_response( '<![CDATA[ok]]>', $mark );
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Формирует карточку записи для просмотра/добавления/изменения/копирования записи. Возвращает массив из заголовка и тела
	 *
	 * @param string $mode		Режим работы html_card() - "add", "change", "copy", "view". Не путать с $mode в методах сбора списка записей
	 * @param array &$request	Ссылка на $_REQUEST или его эмуляцию
	 * @todo рефакторинг
	 * @return array
	 */
	public function html_card($mode, &$request){
		if($mode=="copy" || $mode=="change" || $mode=="view"){
			$pk=$this->primary_key->get_from_record($request);
			$record=$this->full_object->get_change_record($pk, true);
			$pk=$this->primary_key->get_from_record($record); // Это уточнение сделано для поддержки декораторов, которые самостоятельно переуточняют вариант записи
		}else{
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
		}
		
		if ( $this->from_request )
			foreach(metadata::$objects[$this->obj]["fields"] as $field_name=>$field_descr)
				if(isset($request["_form_".$field_name]))
					$record[$record_prefix.$field_name]=$request["_form_".$field_name];
		
		$form_name = html_element::get_next_form_name();
		$operations = array();
		
		if($mode=="copy"){
			$this->full_object->is_permitted_to("add", array(metadata::$objects[$this->obj]["parent_field"]=>$this->parent_id), true);
			$title=metadata::$lang["lang_copy_record"];
			$card_title=$this->full_object->get_record_title($pk);
			$pk_for_hidden=array("pk"=>$pk);
			$action="copied";
		}
		elseif ($mode=="view") {
			$this->full_object->is_permitted_to("view", $pk, true);
			$title=$card_title=metadata::$lang["lang_view_record"];
		}
		elseif($mode=="change"){
			$this->full_object->is_permitted_to("change", $pk, true);
			$pk_for_hidden=array("pk"=>$pk);
			$title=metadata::$lang["lang_change_record"];
			$card_title=$this->full_object->get_record_title($pk);
			$action="changed";
			$apply_action="changed_apply";
		}else{
			$this->full_object->is_permitted_to("add", array(metadata::$objects[$this->obj]["parent_field"]=>$this->parent_id), true);
			$title=$card_title=metadata::$lang["lang_add_record"];
			$action="added";
		}
		
		list($additional_fields, $js)=$this->full_object->ext_html_card($form_name, $record);
		
		// Выводим в карточку записи дополнительное поле "Теги"
		if ( $this -> is_taxonomy )
			$additional_fields += $this -> full_object -> get_form_fields( $mode, '_form_', $record, $record_prefix, array( 'TAG' => array( 'title' => metadata::$lang['lang_uncheckable_tag_through_a_comma'], 'type' => 'text', 'vars' => array( 'maxlength' => params::$params["taxonomy_length"]["value"] ) ) ) );
		
		if ($mode=="view") {
			$form = html_element::html_value_list( $this -> full_object -> get_form_fields( $mode, '', $record, '', '', false ) + $additional_fields, $this -> tpl_dir . 'core/html_element/html_table.tpl', $this -> field );
			$operations =$this->full_object->get_operations(array('back'), $form_name);
		} else {
			$html_fields = html_element::html_fields( $this -> full_object -> get_form_fields( $mode, '_form_', $record, $record_prefix ) + $additional_fields, $this -> tpl_dir . 'core/html_element/html_fields.tpl', $this -> field, !$this->from_request );
			$form = html_element::html_form( $html_fields, $this -> url -> get_hidden( $action, $pk_for_hidden ), $this -> tpl_dir . 'core/html_element/html_form.tpl', true) . $js;
			$operations =$this -> get_record_operations( $form_name, $apply_action);
		}
		
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
		$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		$tpl -> assign( 'form', $form ); 
		if ($mode=='change') $this->set_blocked_tpl_params ($tpl, $pk);
		
		return array( $title, $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' ) );
	}
	
	
	/**
	 * Подготовка списка операций над записями
	 * 
	 * @return array
	 */
	public function get_index_operations()
	{
		$operations = array();
		
		if( $this -> full_object -> is_applied_to( 'add', false ) &&
				( !isset( metadata::$objects[$this->obj]['parent_field'] ) || $this -> full_object -> is_permitted_to( 'add', array( $prefix.metadata::$objects[$this->obj]['parent_field'] => $this -> parent_id ) ) ) )
			$operations['add'] = array( 'name' => 'add', 'alt' => metadata::$lang['lang_add'], 'url' => $this -> url -> get_url( 'add' ) );
		
		$is_allowed_operations = count( array_diff( $this -> index_records_ids, $this -> full_object -> is_permitted_to_mass( 'change', $this -> index_records_ids ) ) ) > 0;
		foreach( $this -> full_object -> get_group_operations() as $group_operation_name => $group_operation_item )
			if ( ( !isset( $group_operation_item['no_action'] ) || !metadata::$objects[$this -> obj][$group_operation_item['no_action']] ) && $is_allowed_operations )
				$operations[$group_operation_name] = array( 'name' => $group_operation_name, 'alt' => $group_operation_item['title'],
					'url' => "javascript:if ( CheckFillConfirm( '" . $group_operation_item['confirm_message'] . "' ) ) { document.forms['checkbox_form'].action.value = '{$group_operation_name}'; document.forms['checkbox_form'].submit() }" );
		
		return $operations;
	}

	/**
	 * Подготовка списка групповых операций над записями
	 * 
	 * @return array
	 */
	public function get_group_operations()
	{
		$operations = array();
	
		if ( $this -> full_object -> is_applied_to( 'change', false ) && metadata::$objects[$this -> obj]['parent_field'] )
			$operations['group_move'] = array( 'title' => metadata::$lang['lang_mass_move'], 'no_action' => 'no_change', 'confirm_message' => metadata::$lang['lang_confirm_mass_move'] );
		if ( $this -> full_object -> is_applied_to( 'delete', false ) )
			$operations['group_delete'] = array( 'title' => metadata::$lang['lang_delete'], 'no_action' => 'no_delete', 'confirm_message' => metadata::$lang['lang_confirm_mass_delete'] );
		
		return $operations;
	}

	/**
	 * Подготовка списка операций над записью
	 * 
	 * @return array
	 */
	public function get_record_operations( $form_name = '', $apply_action = '' )
	{
		$opers = array();
		if ($form_name) {
			if ($apply_action)
				$opers[] = "apply";
				
			$opers[] = "save";
		}
		
		$opers[] = "cancel";
		
		return $this->full_object->get_operations($opers, $form_name);
	}
	
	/**
	* Возвращает массив кнопок операций для вставки в html_element::html_operations
	* @param array $oper_array Массив операций, в формате названия операции. Возможные значения:
	* apply, save, cancel, back
	* В случае если запись заблокирована, и пользователь является админом, автоматически подставляется кнопка unblock
	* Для кнопки apply акция должна соответствовать текущей + (e)d_apply, если текущая акция оканчивается на y, то ied_apply, если на e, то d_apply
	* @param string $form_name Название формы
	* @return array
	*/
	
	public function get_operations ($oper_array, $form_name='')  {

		$apply_action = $this->get_apply_action($_REQUEST['action']);
		
		if ($this->full_object->is_current_record_blocked()) {
			if ($this->full_object->is_checkinout_admin())	array_unshift($oper_array, 'unblock');
			$oper_array = array_diff($oper_array, array('apply', 'save'));
		}
		
		
		$operations['unblock'] = array("name"=>"unblock", "alt"=>metadata::$lang["lang_unblock"], "url"=> $this->url->get_url('unblock_record', array('pk'=>$this->primary_key->get_from_request(), 'save_params'=>1)) );
		$operations['apply'] = array("name"=>"apply", "alt"=>metadata::$lang["lang_action_apply"], "onClick"=>"if ( CheckForm.validate( document.forms['{$form_name}'] ) ) { remove_unblock_record (); document.forms['{$form_name}'].action.value='{$apply_action}'; document.forms['{$form_name}'].submit() }; return false");
		$operations['save'] = array("name"=>"save", "alt"=>metadata::$lang["lang_action_save"], "onClick"=>"if ( CheckForm.validate( document.forms['{$form_name}'])) {remove_unblock_record (); document.forms['{$form_name}'].submit()}; return false;"); 
		$operations['cancel'] = array("name"=>"cancel", "alt"=>metadata::$lang["lang_cancel"], "url"=>$this->url->get_url( "", array( "restore_params" => 1 ) ));
		$operations['back'] = array("name"=>"back", "alt"=>metadata::$lang["lang_back"], "url"=>$this->url->get_url(""));
		
		return array_intersect_key($operations, array_flip($oper_array));
	}
	
	/**
	* Возвращает назваение apply_action для $action
	* @param string $action текущее действие
	* @return string apply действие для текущего
	*/

	private function get_apply_action ($action) {
		// для apply если оканчивается action на e, то добавляем только d, иначе ed

		$apply_action = $action;
		
		if ($apply_action[strlen($apply_action)-1]=='y') 
			$apply_action[strlen($apply_action)-1]='i';
		
		if ($apply_action[strlen($apply_action)-1]!='e') {
			$apply_action .= 'e';
		}
		
		$apply_action .= 'd_apply';
		
		return $apply_action;
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Собирает для текущей таблицы массив заголовков колонок списка записей для использования в html_table()
	 *
	 * @param string $mode	Режим списка записей. См. get_index_modifiers()
	 * @return array
	 */
	public function get_index_header($mode){
		list($show_fields, $filter_fields, $is_limited, $is_tree, $is_short, $is_filter)=$this->full_object->get_index_modifiers($mode);
		// Номер и групповой чекбокс
		$header["_number"]=array("title"=>"N");
		if($mode=="index"){
			$header["_group"]=array("title"=>"", "type"=>"_group");
		}
		// Готовим колонки полей
		if($mode=="index"){ // Полный список
			foreach(metadata::$objects[$this->obj]["fields"] as $field_name=>$field){
				if($show_fields[$field_name]){
					$header[$field_name]=array(
						"title"=>$field["title"],
						"type"=>$field["type"],
						"view_type"=>$field["view_type"],
						"is_main"=>$field["is_main"],
						"width"=>$field["width"],
						"length"=>$field["length"],
						"sort_url"=>$this->url->get_url("",array("sort_field"=>$field_name)),
						"sort_ord"=>($field_name==$this->sort_field ? $this->sort_ord : ""),
						"escape"=>$field["no_escape"] ? 0 : 1,
					);
				}
			}
			// Присоединяем дополнительные колонки
			$header=array_merge($header, $this->full_object->ext_index_header($mode));

			// Готовим колонки линков
			if(is_array(metadata::$objects[$this->obj]["links"])){
				foreach(metadata::$objects[$this->obj]["links"] as $key=>$link){
					$secondary_table=($link["secondary_table"] ? $link["secondary_table"] : $key);
					if($link["show"]){
						$header["_link_".$key]=array(
							"title"=>($link["title"] ? $link["title"] : metadata::$objects[$secondary_table]["title"]),
							"width"=>$link["width"],
							"type"=>"_link",
						);
					}
				}
			}
			// И добавляем операции
			$header["_ops"]=array("title"=>metadata::$lang["lang_operations"], "type"=>"_ops");
		}else{ // Сокращенный список
			$header["_TITLE"]=array(
				"title"=>metadata::$lang["lang_name"],
				"is_main"=>1,
				"escape"=>1,
			);
			
			foreach( metadata::$objects[$this->obj]['fields'] as $field_name => $field_desc )
				if ( $field_desc['type'] == 'checkbox' && $field_desc['show_short'] )
					$header[$field_name] = array( 'title' => $field_desc['title'], 'type' => 'checkbox' );
			
			$ext_index_headers = $this -> full_object -> ext_index_header( $mode );
			foreach ( $ext_index_headers as $ext_index_header_name => $ext_index_header_item )
				if ( !in_array( $ext_index_header_name, array( 'lang_names', 'version_names' ) ) )
					unset( $ext_index_headers[$ext_index_header_name] );
			$header = array_merge( $header, $ext_index_headers );
		}
		return $header;
	}

	/**
	 * Собирает для записи ссылки операций для использования в html_table()
	 *
	 * @param array $record	Запись
	 * @return array
	 */
	public function get_index_ops($record){
		$pk=$this->primary_key->get_from_record($record);
		$links=array();
		// Готовим линки
		if(is_array(metadata::$objects[$this->obj]["links"])){
			foreach(metadata::$objects[$this->obj]["links"] as $key=>$link){
				// Отработка show_if
				if(is_array($link["show_if"])){
					unset($fields_allowed);
					foreach($link["show_if"] as $field=>$values){
						$fields_allowed[$field]=false;
						foreach($values as $value){
							if($record["_".$field]==$value){
								$fields_allowed[$field]=true;
								break;
							}
						}
					}
					$verdict=(in_array(false, $fields_allowed) ? false : true);
				}
				// Сбор ссылки
				if($link["show"]){
					if(!is_array($link["show_if"]) || $verdict){
						$secondary_table=($link["secondary_table"] ? $link["secondary_table"] : $key);
						$links["_link_".$key]=array("url"=>$this->url->get_url("link" ,array("autoinc_name"=>$this->autoinc_name, "id"=>$pk[$this->autoinc_name], "link"=>$link+array("secondary_table"=>$secondary_table))));
					}else{
						$links["_link_".$key]="";
					}
				}
			}
		}
		
		// Готовим стандартные операции
		$ops = array();
		
		if($this->full_object->is_applied_to("change", false) && $this -> is_ops_permited( 'change', $pk[$this->autoinc_name] ) ) {
			$ops[]=array("name"=>"change", "alt"=>metadata::$lang["lang_change"], "url"=>$this->url->get_url("change", array("pk"=>$pk, "save_params" => 1)));
		}
		if ((metadata::$objects[$this->obj]["copy"])  && $this -> is_ops_permited( 'copy', $pk[$this->autoinc_name] ) ) {
			$ops[]=array("name"=>"copy", "alt"=>metadata::$lang["lang_copy"], "url"=>$this->url->get_url("copy" ,array("pk"=>$pk)));
		}
		if($this->full_object->is_applied_to("delete", false) && $this -> is_ops_permited( 'delete', $pk[$this->autoinc_name] ) ) {
			$ops[]=array("name"=>"delete", "alt"=>metadata::$lang["lang_delete"], "url"=>$this->url->get_url("delete" ,array("pk"=>$pk)), "confirm" => true );
		}
		if($this->parent_id!==""){
			$ops[]=array("name"=>"tree", "alt"=>metadata::$lang["lang_tree"], "url"=>$this->url->get_url("tree" ,array("pk"=>$pk)));
		}
		
		// Добавляем в один список с операциями ссылки на таблицы М:М
		if($this->full_object->is_applied_to("change", false) && $this -> is_ops_permited( 'change', $pk[$this->autoinc_name] ) ) {
			if(is_array(metadata::$objects[$this->obj]["m2m"])){
				foreach(metadata::$objects[$this->obj]["m2m"] as $key=>$link){
					$ops[]=array("name"=>"m2m", "m2m"=>$key, "alt"=>($link["title"] ? $link["title"] : metadata::$objects[$link["secondary_table"]]["title"]),
						"url"=>$this->url->get_url("m2m" ,array("pk"=>$pk, "m2m_name"=>$key, "m2m"=>$link, "obj"=>$this->obj, "no_parent" => 1, "save_params" => 1)));
				}
			}
		}
		return array_merge($links, array("_ops"=>$ops));
	}

	/**
	 * Вспомогательный метод, конвертирующий массив операций в строку формата выпадающего меню
	 *
	 * @param array $ops		Массив операций
	 * @return array
	 */
	public function format_index_ops( $ops )
	{
		$ops_array = array();
		if ( $ops['_ops'] )
			foreach( $ops['_ops'] as $ops_index => $ops_value )
				$ops_array[] = self::format_index_op($ops_value);
		$ops['_ops'] = join( ',', $ops_array );
		return $ops;
	}
	
	public static function format_index_op ( $ops_value )
	{
		return "{ 'title': '{$ops_value['alt']}', 'image': '/common/adm/img/menu/{$ops_value['name']}.gif', 'object': window, 'method': 'redirect', 'param': { 'url': '{$ops_value['url']}'".( $ops_value['confirm'] ? ", 'confirm': '".($ops_value['confirm_question']?$ops_value['confirm_question']:metadata::$lang["lang_confirm_delete"])."'" : "" )."} }";
	}

	/**
	 * Вспомогательный метод, возвращающий истину, если текущему пользователю разрешена заданная операция над заданной записью
	 *
	 * @param string $record	Операция
	 * @param int $id			Идентификатор записи
	 * @return boolean
	 */
	public function is_ops_permited( $operation = '', $id = '' ) {
		if ($operation=='copy') {
			static $copy_permitted;
			if (!isset($copy_permitted)) 
				$copy_permitted = $this->full_object->is_applied_to("add") && 	$this->full_object->is_permitted_to("add", array(metadata::$objects[$this->obj]["parent_field"]=>$this->parent_id));
				
			return $copy_permitted;
		}
				
		return !( is_array( $this -> not_permited_ids[$operation] ) && in_array( $id, $this -> not_permited_ids[$operation] ) );
	}

	/**
	 * Метод возвращает массив навигационных вкладок для карточки записи
	 * 
	 * Первой всегда идет вкладка "Описание" (редактирование записи).
	 * Если эта вкладка является единственной, то она не показывается.
	 * 
	 * @param array $pk				Первичный ключ записи
	 * @param array $mark_select	Метка-указатель текущей вкладки
	 * @return array
	 */
	public function get_header_tabs( $pk, $mark_select = 'change' )
	{
		$header_tabs = array();
		
		// Вкладка "Описание"
		if ( $this -> full_object -> is_permitted_to( 'change', $pk ) )
			$header_tabs[] = array( 'title' => metadata::$lang['lang_description'], 'url' => $this -> url -> get_url( 'change', array( 'pk' => $pk ) ), 'active' => $mark_select == 'change' );
		
		// Вкладки "многие-ко-многим"
		if ( is_array( metadata::$objects[$this -> obj]['m2m'] ) )
			foreach ( metadata::$objects[$this -> obj]['m2m'] as $key => $link )
				$header_tabs[] = array(	'm2m' => $key, 'title' => ( $link['title'] ? $link['title'] : metadata::$objects[$link['secondary_table']]['title'] ), 'url' => $this -> url -> get_url( 'm2m', array( 'pk' => $pk, 'm2m_name' => $key, 'm2m' => $link, 'obj' => $this -> obj, 'no_parent' => 1 ) ), 'active' => $mark_select == $key );
		
		return $header_tabs;
	}

	/**
	 * Возвращает кляузу WHERE (без самого этого слова) и связанные переменные для выделения текущей группы записей
	 *
	 * @param array $group_by		Набор полей группировки
	 * @param array $group_values	Значения полей группировки
	 * @param string $prefix		Префикс для названий полей (например, если поля берутся из фильтра)
	 * @return array
	 */
	public function get_group_where($group_by, &$group_values, $prefix){
		$binds=array();
		if(is_array($group_by)){
			foreach($group_by as $group_field){
				$binds["g_".$group_field]=($group_values[$prefix.$group_field] ? $group_values[$prefix.$group_field] : 0);
				$where[]="{$group_field}=:g_{$group_field}";
			}
		}
		if(is_array($where)){
			$where=join(" AND ", $where);
		}else{
			$where="1=1";
		}
		return array($where, "", $binds);
	}

	/**
	 * Делает проверки записей на соответствие группе. В некоторых случаях изменяет записи!
	 *
	 * Для полей с типом "text" проверяет уникальность значения поля в группе, а для полей с типом "checkbox" проверяет, чтобы поле было заполнено только один раз в группе (выбор главной записи).
	 *
	 * Для чекбоксов поведение следующее:<br>
	 * 1. если добавляется/выставляется главная запись, но уже есть главные записи, то у старых записей главность снимается<br>
	 * 2. если у главной записи пытаются снять главность, то будет брошено исключение<br>
	 * 3. если добавляется неглавная запись, а главных еще нет (например, добавление в пустой список), то будет брошено исключение
	 *
	 * @param array $prepared_fields	хэш подготовленых к укладке в базу полей
	 * @param string $mode				"add", "change"
	 * @param array $pk					первичный ключ записи (только для режима изменения)
	 * @todo прикрутить выбор версии или окончательно убедиться, что работает и без него (кажется, что достаточно привязки по одному идентификатору - без учета версии и языка - для проведения такой проверки, потому что проверка становится более жесткой, а это неплохо)
	 * @todo попробовать избавиться от изменения данных в этом методе
	 * @todo Чекбокс (Главная запись) может некорректно работать при операциях публикации и отмены изменений. Нужно потестировать и доработать, когда будет пример таблицы с версиями и главной записью
	 */
	public function field_group_check($prepared_fields, $mode, $pk=array()){
		foreach(metadata::$objects[$this->obj]["fields"] as $field_name=>$field){
			if(!$field["no_{$mode}"] && is_array($field["group_by"])){
				// Если мы группируем значения внутри неизменямого поля, то нужно дополнить им набор полей, взяв его прямо из БД
				if($mode=="change"){
					foreach($field["group_by"] as $group_field){
						if(metadata::$objects[$this->obj]["fields"][$group_field]["no_{$mode}"]){
							$record=$this->get_change_record($pk); // Не очень хорошо делать это всякий раз, но уж очень маловероятно, что неизменяемыми будут сразу несколько группирующих полей
							$prepared_fields[$group_field]=$record[$group_field];
						}
					}
				}
				// Кляузы
				list($group_where, $group_joins, $group_binds)=$this->full_object->get_group_where($field["group_by"], $prepared_fields, "");
				if($mode=="change"){
					$pk_where="AND ".str_replace("=", "<>", $this->primary_key->where_clause());
					$pk_binds=$this->primary_key->bind_array($pk);
				}else{
					$pk_binds=array();
				}
				// Общие компоненты сообщений об ошибках
				$pk_message=($mode!="add" ? ": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")" : "");
				$add_message=($mode=="add" ? " (".metadata::$lang["lang_adding"].")" : "");
				// Проверка поля Текст
				if($field["type"]=="text"){
					$check=db::sql_select("SELECT COUNT(*) AS COUNTER FROM {$this->obj} {$group_joins} WHERE {$group_where} AND lower( {$this->obj}.{$field_name} ) = lower( :field_value ) ".$pk_where, $group_binds+array("field_value"=>$prepared_fields[$field_name])+$pk_binds);
					if($check[0]["COUNTER"]>0){
						throw new Exception($this->te_object_name.$add_message.": ".metadata::$lang["lang_non_unique_value"]." ".$field_name." - \"{$prepared_fields[$field_name]}\"".$pk_message);
					}
				// Проверка поля Чекбокс
				}elseif($field["type"]=="checkbox"){
					$check=db::sql_select("SELECT COUNT(*) AS COUNTER FROM {$this->obj} {$group_joins} WHERE {$group_where} AND {$this->obj}.{$field_name}=1 ".$pk_where, $group_binds+$pk_binds);
					if($check[0]["COUNTER"]>0 && $prepared_fields[$field_name]==1){
						db::sql_query("UPDATE {$this->obj} SET {$this->obj}.{$field_name}=0 WHERE {$group_where} ", $group_binds);
					}elseif($check[0]["COUNTER"]==0 && $prepared_fields[$field_name]==0){
						throw new Exception($this->te_object_name.$add_message.": ".metadata::$lang["lang_main_record_must_be_set"]." ".$field_name.$pk_message);
					}
				}
			}
		}
	}

	/**
	 * Возвращает список записей текущей таблицы
	 *
	 * @param array &$request		Ссылка на $_REQUEST или его эмуляцию
	 * @param string $mode			Режим списка записей. См. get_index_modifiers()
	 * @param string $list_mode		Модификация выборки данных. Используется не в этом базовом методе, а в его расширителях
	 * @param int $include			Только для дерева. Идентификатор родителя, с которого нужно начининать строить дерево
	 * @param array $exclude		Только для дерева. Массив идентификаторов записей, которые (и их дети) не должны попасть в дерево
	 * @return array
	 */
	public function get_index_records(&$request, $mode, $list_mode, $include=0, $exclude=array()) {
		
		// Свойства списка и компоненты запроса
		list($show_fields, $filter_fields, $is_limited, $is_tree, $is_short, $is_filter)=$this->full_object->get_index_modifiers($mode);
		list($fields, $joins, $where, $binds, $field_binds)=$this->full_object->get_index_query_components($request, $mode, $list_mode);
		
		// Готовим параметры для лимита
		if($is_limited){
			$from=($this->from-1)*$this->rows_per_page;
			$limit=" LIMIT {$from},{$this->rows_per_page}";
		}
		// Готовим сортировку для index - обычным образом, для прочих типов (сокращенных) - умолчальную
		if(!$is_short && $this->sort_field){
			$order="ORDER BY {$this->obj}.{$this->sort_field} {$this->sort_ord}";
		}elseif($is_short){
			list($sort_field, $sort_ord)=$this->get_sort_field_and_ord(true);
			if($sort_field){
				$order="ORDER BY {$this->obj}.{$sort_field} {$sort_ord}";
			}
		}
		
		//echo '<PRE>';
		//print_r($fields);
		//exit;
		
		// Собственно запрос и получение записей
		$query="SELECT ".$this->primary_key->select_clause().", {$fields} FROM {$this->obj} {$joins} WHERE {$where} ".$this->full_object->ext_index_query().$order.$limit;
static $iii=0;		
//echo"<pre>|";echo ++$iii.htmlspecialchars(print_r($query,1));echo"|</pre>";
//echo"<pre>|";echo htmlspecialchars(print_r(array_merge($binds, $field_binds, $this->full_object->ext_index_query_binds()),1));echo"|</pre>";
global $bench;//
		$bench->register(bench::bencher("all_parts"), "before query ".$iii, 1);//
bench::bencher("query_itself");//
		$records=db::sql_select($query, array_merge($binds, $field_binds, $this->full_object->ext_index_query_binds()));
		$bench->register(bench::bencher("all_parts"), "after query".$iii, 1);//		

//$bench->register(bench::bencher("query_itself"), "records query ({$this->obj})");//
		// Делаем такую полезную вещь, как кляузу IN (без обрамляющих скобок) с перечнем идентификаторов всех выбранных записей, чтобы не нужно было ее всякий раз руками собирать
		if(count($records)>0){
			foreach($records as $k=>$record){
				$ids[]=$record[$this->autoinc_name];
			}
			$this->index_records_ids=$ids;
			$this->index_records_in=join(", ", $ids);
		}
		// Делаем иерархию
		if($is_tree){
			$records=get_tree::get($records, $this->autoinc_name, metadata::$objects[$this->obj]["parent_field"], $sort_field, $include, $exclude);
			$this->index_counter=count($records);
			if($mode=="select2" && !isset( $list_mode['without_root'] )){
				array_unshift($records, array("_TITLE"=>"- - ".metadata::$lang["lang_root"]." - -", "_VALUE"=>"0"));
			}
		}
		// Вычисление прав на операции для всех записей скопом (только для обычного списка)
		if ( $mode == 'index' )
		{
			$this -> not_permited_ids['change'] = $this -> full_object -> is_permitted_to_mass( 'change', $this -> index_records_ids );
			$this -> not_permited_ids['delete'] = $this -> full_object -> is_permitted_to_mass( 'delete', $this -> index_records_ids );
		}
		// Для иерархических таблиц собираем информацию о детях
		if ( $parent_field = metadata::$objects[$this->obj]['parent_field'] )
		{
			$page_children = db::sql_select( "select {$parent_field}, count(*) as COUNTER from {$this->obj} where {$parent_field} in ({$this->index_records_in}) group by {$parent_field}" );
			$page_children = lib::array_reindex( $page_children, $parent_field );
			
			// Помещение иконок в записи
			foreach ( $records as $record_index => $record )
				$records[$record_index]['_icon'] = $page_children[$record[$this->autoinc_name]]['COUNTER'] ? 'page' : 'leaf_page';
			
			// В случае поиска по всем уровням дополняем записи путями
			if ( $request['_f__ALL_LEVELS'] && ( $mode == 'index' || $mode == 'm2m' ) )
			{
				// Получаем пути к выбранным записям
				list( $paths, $ids ) = lib::get_path( $records, $this -> obj, $this -> autoinc_name, metadata::$objects[$this->obj]['parent_field'], $this -> primary_key -> ext_pk_fields() );
				
				// Добавляем в начало каждого пути идентификатор корня
				foreach ( $paths as $path_index => $path )
					if ( is_array( $path ) ) array_unshift( $paths[$path_index], 0 );
				
				// Получаем информацию о страницах, попавшихся на пути
				$parents_obj = object::factory( $this -> obj );
				$parents_list = $parents_obj -> get_index_records( $none, 'select2', array( 'direct' => $ids ) );
				$parents_obj -> __destruct();
				
				$parents_list = lib::array_reindex( $parents_list, $this -> autoinc_name );
				
				// Добавляем в список родительских страниц название корневую запись
				$parents_list[0]['_TITLE'] = metadata::$lang['lang_root'];
				
				if ( $mode == 'm2m' ) $primary_instance = object::factory( $_REQUEST['obj'] );
				
				foreach ( $parents_list as $parent_id => $parent_item )
				{
					if ( mb_strlen( $parent_item['_TITLE'], params::$params["encoding"]["value"] ) > 20 )
						$parents_list[$parent_id]['_TITLE'] = mb_substr( $parent_item['_TITLE'], 0, 20, params::$params["encoding"]["value"] ) . '...';
					
					$parents_list[$parent_id]['_URL'] = ( $mode == 'm2m' ) ?
						$this -> url -> get_url( 'm2m', array( 'obj' => $primary_instance -> obj, 'm2m_name' => $_REQUEST['m2m'],
							'pk' => $primary_instance -> primary_key -> get_from_request(), 'parent_id' => $parent_id, 'no_from' => 1 ) ) :
						$this -> url -> get_url( '', array( 'parent_id' => $parent_id, 'no_from' => 1 ) );
				}
				
				// В путях подменяем идентификаторы записей на их описания
				foreach ( $records as $record_index => $record )
				{
					if ( is_array( $paths[$record_index] ) )
						foreach ( $paths[$record_index] as $path_index => $record_id )
							$paths[$record_index][$path_index] = $parents_list[$record_id];
					
					// Выводим информацию о пути в шаблон
					$records[$record_index]['_path'] = $paths[$record_index];
				}
			}
		}
		
		return $records;
	}

	/**
	 * Возвращает количество записей текущей таблицы
	 *
	 * @param array &$request		Ссылка на $_REQUEST или его эмуляцию
	 * @param string $mode			Режим списка записей. См. get_index_modifiers()
	 * @param string $list_mode		Модификация выборки данных. Используется не в этом базовом методе, а в его расширителях
	 * @param int $include			Только для дерева. Идентификатор родителя, с которого нужно начининать строить дерево
	 * @param array $exclude		Только для дерева. Массив идентификаторов записей, которые (и их дети) не должны попасть в дерево
	 * @return int
	 */
	public function get_index_counter(&$request, $mode, $list_mode, $include=0, $exclude=array()){
		if($this->index_counter===""){
			list($fields, $joins, $where, $binds)=$this->full_object->get_index_query_components($request, $mode, $list_mode);
			$query="SELECT COUNT(*) AS COUNTER FROM {$this->obj} {$joins} WHERE {$where} ".$this->full_object->ext_index_query();
			$counter=db::sql_select($query, array_merge($binds, $this->full_object->ext_index_query_binds()));
			$counter=$counter[0]["COUNTER"];
		}else{
			$counter=$this->index_counter;
		}
		return $counter;
	}

	/**
	 * Возвращает компоненты запроса для выборки списка записей или числа записей
	 *
	 * @param array &$request		Ссылка на $_REQUEST или его эмуляцию
	 * @param string $mode			Режим списка записей. См. get_index_modifiers()
	 * @param string $list_mode		Модификация выборки данных. Используется не в этом базовом методе, а в его расширителях
	 * @return array
	 * @todo Правильно ли, что для select2 выводятся все записи? Или надо более тонко настроить выборку для этого случая?
	 */
	public function get_index_query_components(&$request, $mode, $list_mode){
		list($show_fields, $filter_fields, $is_limited, $is_tree, $is_short, $is_filter)=$this->full_object->get_index_modifiers($mode);

		if ( isset( $list_mode['without_filter'] ) )
			$is_filter = false;
		
		// Сбор списков полей, джойнов, лайков
		$fields=array();
		$joins=array();
		$where_search=array();
		$where_filter=array();
		$binds=array();
		$field_binds=array();// список биндов слева от оператора FROM
		$f_counter=0;
		foreach(metadata::$objects[$this->obj]["fields"] as $field_name=>$field){
			if($show_fields[$field_name]){
				// Собираем наборы полей для выборки и поиска
				if($field["type"]=="select2"){ // Для select2 вместо идентификатора выдаем присоединенное название заголовочных полей из внешней таблицы, а также делаем лайки по названию поля внешней таблицам
					
					$fk_instance=object::factory($field["fk_table"]);
					list($ext_join_clause, $ext_join_binds)=$fk_instance->ext_join($this->obj, "{$field["fk_table"]}_{$f_counter}");
					$binds+=$ext_join_binds;
					$joins[]="LEFT JOIN {$field["fk_table"]} {$field["fk_table"]}_{$f_counter} ON {$field["fk_table"]}_{$f_counter}.".$fk_instance->autoinc_name."={$this->obj}.{$field_name} ".$ext_join_clause;
					
					$fields[]="{$this->obj}.{$field_name} AS \"_{$field_name}\"";
					
					list($fk_field, $fk_joins, $fk_binds)=$fk_instance->get_short_title_clause("_".$f_counter);
					$fields[]=$fk_field." AS {$field_name}";
					$joins=array_merge($joins, $fk_joins);
					$binds+=$fk_binds;
					
					if($is_filter){
						list($select2_search, $select2_binds)=$fk_instance->get_short_title_like($request["search"], "_".$f_counter);
						$where_search=array_merge($where_search, $select2_search);
						$binds+=($request["search"] ? $select2_binds : array());
					}
					
					$fk_instance -> __destruct();
				}elseif($field["type"]=="select1"){ // Для select формируем CASE для замены значений их названиями
					$case="";
					$c_counter=1;
					$values = array();
					foreach($field["value_list"] as $item){
						$case.=" WHEN :case_title_{$f_counter}_{$c_counter} THEN :case_value_{$f_counter}_{$c_counter}";
						$field_binds['case_value_'.$f_counter.'_'.$c_counter] = $item['title'];
						$field_binds['case_title_'.$f_counter.'_'.$c_counter] = $item["value"];						
						$values[$item['value']]['search'] = $request['search'];
						$values[$item['value']]['title'] = $item['title'];
						$c_counter++;
					}
					$fields[]="CASE {$this->obj}.{$field_name} {$case} ELSE {$this->obj}.{$field_name} END AS {$field_name}";
					//$fields[]="CASE {$this->obj}.{$field_name} {$case} ELSE {$this->obj}.{$field_name} END AS {$field_name}";
					$fields[]="{$this->obj}.{$field_name} AS \"_{$field_name}\"";

					// для фильтра
					$where_search[]="{$this->obj}.{$field_name} LIKE :like_{$f_counter}";
					
					// если вручную был задан поиск
					if ($request['search']) {
						$matched = array_keys(array_filter($values, array($this, 'callback_filter_select1_by_search')));

						if (!sizeof($matched)) 
							$where_search[]="1=0";
						else {
							$i_counter=0;
							$in = array();
							
							for ($i=0, $n=sizeof($matched); $i<$n; $i++) {
								$in[]=':in_'.$f_counter.'_'.$i;
								$binds['in_'.$f_counter.'_'.$i] = $matched[$i];
							}
							
							$where_search[]="{$this->obj}.{$field_name} IN (".implode(',', $in).")";
						}
					}

				}else{
					// Если какому-нибудь из классов наследников есть что сказать о выборке поля, то принимаем эти данные
					list($dec_field, $dec_where_search, $dec_join, $dec_binds)=$this->full_object->ext_field_selection($field_name, $f_counter);
					if($dec_field){
						$fields[]=$dec_field." AS {$field_name}";
						$where_search=array_merge($where_search, $dec_where_search);
						$joins=array_merge($joins, $dec_join);
						$binds+=$dec_binds;
					}else{
						// проверка на то что поле не входит в первичный ключ, чтобы не было дублирования, Oracle этого не любит
						if (!$this->primary_key->is_field_in_select_clause($field_name))
							$fields[]="{$this->obj}.{$field_name}";
						$where_search[]="{$this->obj}.{$field_name} LIKE :like_{$f_counter}";
					}
				}
				if($is_filter && $field["type"]!="select2" && $request["search"]){ // Для select2 бинды собираются особо, с учетом их специфики
					$binds["like_{$f_counter}"]="%{$request["search"]}%";
				}
			}
			
			// Собираем наборы для фильтрации для полей типа checkbox, select1 и select2
			if(
				$filter_fields[$field_name] && (
					($field["type"]=="checkbox" && ($request["_f_".$field_name]==="1" || $request["_f_".$field_name]==="0"))
					|| ($field["type"]=="select1" && ($request["_f_".$field_name] || $request["_f_".$field_name]==="0"))
					|| ($field["type"]=="select2" && $request["_f_".$field_name])
				)
			){
				$where_filter[]="{$this->obj}.{$field_name}=:filter_{$f_counter}";
				$binds["filter_{$f_counter}"]=$request["_f_".$field_name];
			}
			// Собираем наборы для фильтрации для полей типа text и textarea
			if(
				$filter_fields[$field_name] && ( $field["type"]=="text" || $field["type"]=="textarea" ) && $request["_f_".$field_name] != ""
			){
				$where_filter[]="{$this->obj}.{$field_name} LIKE :filter_{$f_counter}";
				$binds["filter_{$f_counter}"]="%{$request["_f_".$field_name]}%";
			}
			// Собираем наборы для фильтрации для полей типа int (к сожалению с float такая фишка не прокатила)
			if(
				$filter_fields[$field_name] && $field["type"]=="int" && $request["_f_".$field_name] != ""
			){
				$where_filter[]="{$this->obj}.{$field_name} = :filter_{$f_counter}";
				$binds["filter_{$f_counter}"]="{$request["_f_".$field_name]}";
			}
			// Собираем наборы для фильтрации для полей типа date и datetime
			if(
				$filter_fields[$field_name] && ( $field["type"]=="date" || $field["type"]=="datetime" )
			){
				if ( $request["_f_".$field_name."_from"] != "" &&
					$pack_date = lib::pack_date( $request["_f_".$field_name."_from"], $field["type"] == "datetime" ? "long" : "short" ) )
				{
					$where_filter[]="{$this->obj}.{$field_name} >= :filter_{$f_counter}_from";
					$binds["filter_{$f_counter}_from"] = $pack_date;
				}
				if ( $request["_f_".$field_name."_to"] != "" &&
					$pack_date = lib::pack_date( $request["_f_".$field_name."_to"], $field["type"] == "datetime" ? "long" : "short" ) )
				{
					$where_filter[]="{$this->obj}.{$field_name} <= :filter_{$f_counter}_to";
					$where_filter[]="{$this->obj}.{$field_name} <> ''";
					$binds["filter_{$f_counter}_to"] = $pack_date;
				}
			}
			$f_counter++;
		}
		// Для короткой формы сразу формируем общие название и идентификатор
		if($is_short){
			list($fk_field, $fk_joins, $fk_binds)=$this->full_object->get_short_title_clause("", false, $mode);
			$fields[]=$fk_field." AS \"_TITLE\"";
			$fields[]="{$this->obj}.{$this->autoinc_name} AS \"_VALUE\"";
			
			list($sort_field, $sort_ord)=$this->get_sort_field_and_ord(true);
			if($sort_field && !$this->primary_key->is_field_in_select_clause($sort_field) && !metadata::$objects[$this->obj]["fields"][$sort_field]['show_short'] && !metadata::$objects[$this->obj]["fields"][$sort_field]['is_main']) {
				$fields[]="{$this->obj}.{$sort_field}";
			}
			
			$joins=array_merge($joins, $fk_joins);
			$binds+=$fk_binds;
		}
		// Ограничение по отмеченности
		if ( is_array( $list_mode ) && $list_mode['m2m_records'] ) {
			$where_filter[] = $list_mode['m2m_records'];
			$binds+=$list_mode['m2m_records_binds'];
		}
		// Собираем компоненты
		$fields=join(", ", array_unique($fields));
		$joins=join(" ", $joins);
		if(count($where_search)>0 && $is_filter && $request["search"]){
			$where="(".join(" OR ", $where_search).")";
		}else{
			$where="1=1";
		}
		if(count($where_filter)>0 && $is_filter){
			$where.=" AND ".join(" AND ", $where_filter);
		}
		// Приделываем ограничение, если оно нужно
		list($lm_where, $lm_binds)=$this->full_object->ext_index_by_list_mode($mode, $list_mode);
		$where.=$lm_where;
		$binds=array_merge($binds, $lm_binds);

		// Приделываем ограничение на просмотр
		if($mode!="select2" || $list_mode["use_rights"]){ // В select2 должен выводиться полный список
			list($auth_where, $auth_binds)=$this->full_object->get_auth_clause_and_binds();
			$where.=$auth_where;
			$binds=array_merge($binds, $auth_binds);
		}
		// Определяем нужно ли здесь фильтровать по родителю
		if($this->parent_id!=="" && !$is_tree && !$request['_f__ALL_LEVELS']){
			$parent_field=metadata::$objects[$this->obj]["parent_field"];
			$where.=" AND {$this->obj}.{$parent_field}=:p_{$parent_field}";
			$binds["p_".$parent_field]=$this->parent_id;
		}
		
		if(!$where){
			$where="1=1";
		}
		
		return array($fields, $joins, $where, $binds, $field_binds);
	}

	/**
	 * Возвращает набор полей для выбора в списке, набор полей для фильтрации, а также признаки модификации запроса
	 *
	 * @param string $mode	Режим выборки записей. Может быть "index" - для обычной выборки; "m2m", "tree", "select2" - для соответствующих режимов
	 * @return array
	 */
	public function get_index_modifiers($mode){
		// Для обычного списка с лимитом и фильтрацией
		if($mode=="index" || $mode=="m2m"){
			if($mode=="m2m"){
				$postfix="_short";
			}
			foreach(metadata::$objects[$this->obj]["fields"] as $field_name=>$field){
				if($field["show{$postfix}"] || $field["is_main"]){
					$show_fields[$field_name]=1;
				}
				if($field["filter{$postfix}"] && !$field["virtual"]){
					$filter_fields[$field_name]=1;
				}
			}
			$is_limited=true;
			$is_tree=false;
			$is_filter=true;
		// Для полного списка, обычного и иерархического
		}elseif($mode=="tree" || $mode=="select2"){
			foreach(metadata::$objects[$this->obj]["fields"] as $field_name=>$field){
				if($field["show_short"] || $field["is_main"] || ( $mode=="tree" && $field["show"] ) ){
					$show_fields[$field_name]=1;
				}
				if(((($mode=="m2m" && $this->parent_id!=="") || ($mode=='tree')) && $field["filter_short"] && !$field["virtual"])){
					$filter_fields[$field_name]=1;
				}
			}
			$is_limited=false;
			if($this->parent_id!==""){
				$is_tree=true;
			}else{
				$is_tree=false;
			}
			$is_filter=$mode=='tree' && is_array($filter_fields);
		}
		if($mode=="m2m" || $mode=="tree" || $mode=="select2"){
			$is_short=true;
		}else{
			$is_short=false;
		}
		
		return array($show_fields, $filter_fields, $is_limited, $is_tree, $is_short, $is_filter);
	}

	/**
	 * Возвращает кляузу для выбора короткого названия записи для использования в сокращенных списках (например, select2), а также при присоединении таблицы к другой
	 *
	 * @param string $table_postfix		постфикс к названию таблицы - нужен для корректной отработки случая, когда таблицу требуется присоединить больше чем один раз
	 * @param boolean $use_foreigns		присоединять колонки с внешнимм записями или нет - если таблица сама присоединяется, то нужно выставлять этот параметр в false (по умолчанию), так как двойное присоединение работать не будет
	 * @return array
	 * @todo если нам понадобится отображать присоединенное поле в коротком списке, то нужно будет доработать этот метод
	 */
	public function get_short_title_clause($table_postfix="", $use_foreigns=false, $mode="index" ){
		$show_fields=array(); $joins=array(); $binds=array();
		$f_counter=0;
		foreach(metadata::$objects[$this->obj]["fields"] as $field_name=>$field){
			// Если какому-нибудь из классов наследников есть что сказать о выборке поля, то принимаем эти данные
			list($dec_field, $dec_where_search, $dec_join, $dec_binds)=$this->full_object->ext_field_selection($field_name, $table_postfix."_".$f_counter, $table_postfix);
			if(($field["show_short"] || $field["is_main"]) && ($use_foreigns || $field["type"]!="select2")){
				if($field["type"]=="select2"){
					//$show_fields[]=object::factory($field["fk_table"])->get_short_title_clause("_".$f_counter);
				}else{
					if ( $field["show_short"] && $field["type"]=="checkbox" && $mode!="select2" )
						continue;
					
					if($dec_field){
						$show_fields[]=$dec_field;
						$joins=array_merge($joins, $dec_join);
						$binds+=$dec_binds;
					}else{
						$show_fields[]=$this->obj.$table_postfix.".".$field_name;
					}
				}
			}
			$f_counter++;
		}
		return array(db::concat_clause($show_fields, $this->short_title_delimiter), $joins, $binds);
	}

	/**
	 * Возвращает название указанной записи, причем название строится по правилам сокращенного списка
	 *
	 * @param array $pk					Массив с первичным ключом записи
	 * @return string
	 */
	public function get_record_title($pk){
		list($field, $joins, $binds)=$this->full_object->get_short_title_clause();
		if ( $field )
			$record_title=db::sql_select("SELECT {$field} AS TITLE FROM {$this->obj}
				".join(" ", $joins)."
				WHERE ".$this->primary_key->where_clause()." ".$this->full_object->ext_index_query()
			, $this->primary_key->bind_array($pk)+$binds+$this->full_object->ext_index_query_binds());
		if ( mb_strlen( $record_title[0]["TITLE"], params::$params["encoding"]["value"] ) > 100 )
		{
			$space_index = mb_strpos( $record_title[0]["TITLE"], " ", 100, params::$params["encoding"]["value"] );
			if ( $space_index === false || $space_index > 120 )
				$record_title[0]["TITLE"] = mb_substr( $record_title[0]["TITLE"], 0, 100, params::$params["encoding"]["value"] );
			else
				$record_title[0]["TITLE"] = mb_substr( $record_title[0]["TITLE"], 0, $space_index, params::$params["encoding"]["value"] );
			$record_title[0]["TITLE"] .= '...';
		}
		return $record_title[0]["TITLE"];
	}

	/**
	 * Возвращает кляузу для поиска в сокращенном наборе полей для присоединения к другой таблице, а также связанные переменные для этой цели
	 *
	 * @param string $search			поисковая строка
	 * @param string $table_postfix		постфикс к названию таблицы - нужен для корректной отработки случая, когда таблицу требуется присоединить больше чем один раз
	 * @return array
	 */
	public function get_short_title_like($search, $table_postfix=""){
		static $counter; // нужен для придания уникальности переменным привязки
		$binds=array();
		$where_search=array();
		foreach(metadata::$objects[$this->obj]["fields"] as $field_name=>$field){
			if(($field["show_short"] || $field["is_main"])){
				$f_counter++;
				// Если какому-нибудь из классов наследников есть что сказать о выборке поля, то принимаем эти данные
				list($dec_field, $dec_where_search, $dec_join, $dec_binds)=$this->full_object->ext_field_selection($field_name, $table_postfix."_".$f_counter, $table_postfix);
				$where_search[]=($dec_field ? $dec_field : $this->obj.$table_postfix.".".$field_name)." LIKE :like{$table_postfix}_{$f_counter}";
				$binds["like{$table_postfix}_{$f_counter}"]="%{$search}%";
			}
		}
		return array($where_search, $binds);
	}

	/**
	 * Возвращает запись для формы ее редактирования
	 *
	 * Сейчас всегда выдается запись с приоритетом тестовой версии - даже в том случае, если тестовая и рабочая версия разнесены
	 * по разным уровням иерархии. Сделано так потому что принято решение о том, чтобы все версии записи (включая языковые)
	 * переносились по иерархии синхронно, то есть вышеописанный случай нештатный, хотя и должен корректно отработаться.
	 *
	 * @param array $pk					Массив с первичным ключом записи
	 * @param boolean $throw_exception	Проверить запись на существование и бросить исключение, если ее нет
	 * @return array
	 * @todo сделать параметр $pk необязательным, чтобы по умолчанию первичный ключ формировался через $this->primary_key->get_from_request()
	 */
	public function get_change_record($pk, $throw_exception=false){
		if($throw_exception){
			$this->primary_key->is_record_exists($pk, true);
		}
		$pk_where=$this->primary_key->where_clause();
		$pk_bind=$this->primary_key->bind_array($pk);
		$record=db::sql_select("SELECT {$this->obj}.* FROM {$this->obj} WHERE {$pk_where} ".$this->full_object->ext_change_query(), array_merge($pk_bind, $this->full_object->ext_change_query_binds()));
		
		// Добавляем к записи поле "Теги"
		if ( $record[0] && $this -> is_taxonomy )
			$record[0]['TAG'] = $this -> full_object -> get_tags( $pk );
		
		return $record[0];
	}

	/**
	 * Возвращает подготовленные к помещению в БД данные
	 * 
	 * Производит проверку данных. В случае неуспеха вызвает исключение
	 * 
	 * @param array $raw_fields		Сырые данные, например, $_REQUEST
	 * @param string $prefix		Префикс, которым дополнены сырые данные, например, _form_ для формы
	 * @param string $mode			Режим работы метода - "add" или "change"
	 * @return array
	 * @todo убедиться, что при копировании режим отрабатывается корректно
	 */
	public function get_prepared_fields($raw_fields, $prefix, $mode){
		$fields = array();
		foreach(metadata::$objects[$this->obj]["fields"] as $field_name=>$field){
			if(!$field["no_{$mode}"] && !$field["virtual"]){
				$fields[$field_name]=$this->field->get_prepared($raw_fields[$prefix.$field_name], $field, $prefix.$field_name);
			}
		}
		return $fields;
	}
	
	/**
	 * Контролирует правильность связи м2м, а также возвращает нужные данные о первичной и вторичной таблицах и о связи
	 * Для $secondary_instance желательно делать __destruct() после использования
	 * Возвращает $secondary_instance, сделан private final чтобы не было соблазна использовать его в других классах, и не вызывать __destruct
	 *
	 * @return array
	 */
	private final function get_m2m_data(){
		$m2m=metadata::$objects[$this->obj]["m2m"][$_REQUEST["m2m"]];
		if(!$m2m){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_such_link"].": \"".$_REQUEST["m2m"]."\"");
		}

		// Правильное название таблицы-связки
		$m2m_table=($m2m["m2m_table"] ? $m2m["m2m_table"] : $_REQUEST["m2m"]);

		// Данные первичной таблицы
		$primary_pk=$this->primary_key->get_from_request(true);
		$primary_id=$this->primary_key->get_id_from_record($primary_pk);
		$primary_title=$this->full_object->get_record_title($primary_pk);
		$primary_m2m_field=($m2m["primary_m2m_field"] ? $m2m["primary_m2m_field"] : $this->autoinc_name);

		// Данные вторичной таблицы
		$secondary_instance=object::factory($m2m["secondary_table"]);
		$secondary_instance->apply_object_parameters( $_REQUEST );
		$secondary_m2m_field=($m2m["secondary_m2m_field"] ? $m2m["secondary_m2m_field"] : $secondary_instance->autoinc_name);

		return array($m2m_table, $primary_id, $primary_title, $primary_m2m_field, $secondary_m2m_field, $secondary_instance);
	}

	/**
	 * Возвращает нужные данные о третичной таблице
	 *
	 * @return array
	 * @todo Переменные привязки в IN
	 * @todo Не передавать реквест в построение списка записей третичной таблицы?
	 */
	public function get_m2m_tertiary_data(){
		$m2m=metadata::$objects[$this->obj]["m2m"][$_REQUEST["m2m"]];
		$m2m_table=($m2m["m2m_table"] ? $m2m["m2m_table"] : $_REQUEST["m2m"]);
		$tertiary_instance=object::factory($m2m["tertiary_table"]);
		$tertiary_instance->from=1; // Для того, чтобы листалка не учитывалась третичной таблицей
		$tertiary_instance->rows_per_page=params::$params["rows_per_page"]["value"];
		$tertiary_m2m_field=($m2m["tertiary_m2m_field"] ? $m2m["tertiary_m2m_field"] : $tertiary_instance->autoinc_name);
		$m2m["list_mode"]["without_filter"]=1;
		$tertiary_records=$tertiary_instance->get_index_records($_REQUEST, "m2m", $m2m["list_mode"]);
		$tertiary_clause=" AND {$m2m_table}.{$tertiary_m2m_field} IN ({$tertiary_instance->index_records_in})";
		$tertiary_instance -> __destruct();
		return array($tertiary_m2m_field, $tertiary_clause, $tertiary_records);
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Возвращает кляузу для выборки только тех записей, которые можно смотреть данному пользователю
	 *
	 * @return array
	 */
	public function get_auth_clause_and_binds(){
		return array(" AND 1=".(int)$this->full_object->is_permitted_to("view"), array()); // Если пользователь допущен к обычному системному разделу, то он может смотреть все
	}
	
	/**
	* Записывает данные в лог
	* @param string $type	Тип записи
	* @param array $fields	Информация
	*/
	public function log_register($type, $fields) {
		if (!log::is_enabled('log_records_change')) return;
		
		if ($type=='m2m') {
			if (sizeof($fields['m2m_changed'])) {
				foreach ($fields['m2m_changed'] as $pid=>$data) {
					$fields['log_params']['extended_info']=array('m2m'=>$fields['m2m'], 'm2m_changed'=>$this->sort_m2m($data));
					$fields=array_merge_recursive($fields, $this->get_info_for_log($this->get_default_record_by_id($pid)));
					log::register('log_records_change', $type, $fields['log_params']['log_info'], $this->te_object_id, $fields['log_params']['object_id'], $fields['log_params']['lang_id'], $fields['log_params']['version'], $fields['log_params']['extended_info']);
				}
			}
		}
		else {
			$fields=$this->get_info_for_log($fields);
			log::register('log_records_change', $type, $fields['log_params']['log_info'], $this->te_object_id, $fields['log_params']['object_id'], $fields['log_params']['lang_id'], $fields['log_params']['version'], $fields['log_params']['extended_info']);
		}
	}
	
	/**
	* Получает запись в таблице по ID в случае отсутствия декорирующих под-id
	* Из декораторов получает значения по умолчанию
	*
	* @param int $pid	id записи
	* @return array Запись
	*/
	
	private function get_default_record_by_id($pid) {
		$sql='SELECT * FROM '.$this->obj.' WHERE '.$this->autoinc_name.'=:pk'.$this->full_object->ext_index_query();
		$res=db::sql_select($sql, array('pk'=>$pid)+$this->full_object->ext_index_query_binds());
		return $res[0];
	}
	
	/**
	* Получает данные для лога
	*/
	public function get_info_for_log($fields) {
		$old_fields=array();
		if ($fields['pk']) 
			$old_fields = $this->full_object->get_change_record($fields['pk']);
		
		$fields=array_merge($old_fields, $fields);
		$fields=array_merge($fields, $this->full_object->get_additional_info_for_log($fields));
		return $fields;
	}
	
	/**
	* Получает дополнительные данные для лога
	*/
	public function get_additional_info_for_log($fields) {
		$fields['log_params']['object_id']=$fields['log_params']['log_info']['object_id']=$fields[$this->autoinc_name];
		if (!$fields['log_params']['log_info']['object_name'])
			$fields['log_params']['log_info']['object_name']=$this->full_object->get_record_title($this->full_object->primary_key->get_from_record($fields));

		return $fields;
	}
	
	/**
	* Сортировка данных для получения из исходного массива arr другого массива в необходимом формате
	* @param array $arr Массив m2m, сохраненный в БД
	* @return $res Результат сортировки
	*/
	
	public function sort_m2m ($arr) {
		$res=array();
		foreach ($arr as $key=>$data) {
			for ($i=0, $n=sizeof($data); $i<$n; $i++) {
				$ids=array_keys($data[$i]);
				$tetr_id=$ids[2]?$data[$i][$ids[2]]:0;
				$res[$tetr_id][$key][]=$data[$i];
			}
		}
		
		return $res;
	}
	
	/**
	* Проверка возможна ли данная операция
	* @param string $operation_name Название операции
	* @param boolean $throw_exception	Бросать ли исключение
	*
	* @return boolean
	*/
	
	public function is_applied_to($operation_name, $throw_exception=true) {
		if (metadata::$objects[$this->obj]["no_".$operation_name]) {
			if ($throw_exception)
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_operation_not_applied"].': "'.$operation_name.'"');
			return false;
		}
		return true;
	}
	
	
	/**
	* Может ли текущий пользователь разблокировывать записи
	* @return boolean
	*/
	
	public function is_checkinout_admin() {
		return $this->auth->is_main_admin;
	}
	
	/**
	* Необходимо ли блокировать записи текущей таблицы
	* @return boolean
	*/
	
	public function is_checkinout_table() {
		return (bool) params::$params['lock_records']['value'] && !(bool) object_name::$te_object_ids[$this->obj]['IS_UNBLOCKABLE'];
	}


	/**
	* Проверяет, заблокирована ли запись
	* @param array $pk Первичный ключ
	* @param boolean $throw_exception Вызывать ли исключение в случае если запись заблокирована
	* @param $locked_record Возвращает запись из таблицы заблокированных записей
	* @return boolean
	*/
	
	public function is_record_blocked($pk, $throw_exception=true, &$locked_record=array()) {
		if (!$this->full_object->is_checkinout_table()) return false;
		
		$locked_record=db::sql_select('
			SELECT 
				* 
			FROM 
				LOCKED_RECORD 
			WHERE 
				TE_OBJECT_ID=:te_object_id 
					AND 
						CONTENT_ID=:content_id
					AND
						LANG_ID=:lang_id',
			array(
				'te_object_id'=>object_name::$te_object_ids[$this->obj]['TE_OBJECT_ID'],
				'content_id'=>$pk[$this->autoinc_name],
				'lang_id'=>$pk['LANG_ID']?$pk['LANG_ID']:0
			)
		);
		
		if (sizeof($locked_record)) {
			if ($locked_record[0]['AUTH_USER_ID']!=$this->auth->user_info['AUTH_USER_ID']) {
				if ($throw_exception)
					throw new Exception($this->te_object_name.": ".metadata::$lang['lang_record_blocked'].": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
				return true;
			}
		}
		
		return false;
	}
	

	/**
	* Проверяет заблокированы ли записи с уникальными ключами из массива $ids 
	* Возвращает массив ключей заблокированных записей
	* @param object $obj Объект
	* @param array $ids Массив id записей для проверки
	* @return array
	*/
	
	public function mass_check_on_block($obj, $ids) {
		$res = array();
		foreach ($ids as $key=>$id) 
			if ($obj->is_record_blocked(array($obj->autoinc_name=>$id), false))
				$res[]=$key;
		return $res;
	}
	
	/**
	* Возвращает сообщение о том, кто заблокировал запись, в случае если запись заблокирована, иначе возвращает пустую строку
	* @param object $obj Объект
	* @param array $pk Первичный ключ
	* @return string
	*/
	
	public function get_blocked_info($obj, $pk) {
		if (!$obj->is_record_blocked($pk, false, $lock_record)) return;
		$auth_user_obj = object::factory('AUTH_USER');
		$res=metadata::$lang['lang_record_blocked_by'].' '.$auth_user_obj->get_record_title(array('AUTH_USER_ID'=>$lock_record[0]['AUTH_USER_ID'])).' '.metadata::$lang['lang_with'].' '.lib::unpack_date($lock_record[0]['LOCK_STARTED'], 'full');
		$auth_user_obj->__destruct();
		return $res;
	}
	
	
	/**
	* Список блокирующих акций
	* в данном списке находятся действия, при вызове которых запись необходимо заблокировать
	* @see table::get_commit_lock_actions
	* @return array
	*/ 
	
	public function get_lock_actions () {
		return array ('change', 'm2m');
	}
	
	/**
	* Права, которые должны быть у пользователя для блокировки записи
	* @return array
	*/
	
	public function get_rights_for_lock () {
		return array('change');
	}
	
	/**
	* Список изменяющих акций
	* В данном списке находятся действия, при вызове которых запись необходимо изменить, 
	* при этом она должна была быть заблокирована, @see table::get_lock_actions
	* @return array
	*/
	
	public function get_commit_lock_actions() {
		return array ('changed', 'changed_apply', 'm2med_apply');
	}
	
	
	/**
	* Разблокирует запись только в случае если данный пользователь ее блокировал
	* Иначе возникает Exception
	* @param array $pk Первичный ключ
	*/
	
	public function unblock_user_record ($pk, $throw_exception=true) {
		try {
			try {
				$this->is_record_blocked($pk);
				if (!$this->full_object->exec_unblock_record($pk)) 
					throw new Exception();
			}
			catch (Exception $e) {
				throw new Exception(metadata::$lang['lang_block_time_expire']);
			}
		}
		catch (Exception $e) {
			if ($throw_exception)
				throw $e;
		}
		return true;
	}
	

	/**
	* Устанавливает параметры шаблона, связанные с блокировкой, необходимо вызывать при работе с шаблоном html_card
	* @param object $tpl Шаблон
	* @param array $pk Первичный ключ
	*/
	
	public function set_blocked_tpl_params ($tpl, $pk) {
		if (!$this->full_object->is_checkinout_table()) return;
		if ($this->full_object->is_record_blocked($pk, false)) {
			$tpl->assign("blocked", true);
			$tpl->assign("blocked_info", $this->full_object->get_blocked_info($this, $pk));
		}
		else {
			$tpl->assign("blocking", true);
			$tpl->assign("ping_time", round(params::$params['lock_timeout']['value']*1000/4));
		}
	}

	/**
	* Заблокирована ли текущая запись на странице
	* @return boolean
	*/
	
	public function is_current_record_blocked () {
		return in_array($_REQUEST['action'], $this->full_object->get_lock_actions()) 
					&& 
						$this->full_object->is_record_blocked($this->primary_key->get_from_request(), false);

	}

	/**
	* Возваращает полный объект 
	* @return class
	*/
	public function get_full_object () {
		return $this->full_object;
	}
	
	
	/**
	* Функция обратного вызова для фильтрации данных для поля select1
	* @param array $var Строка массива
	* @return boolean
	*/
	
	public function callback_filter_select1_by_search ($var) {
		if (!$var['search']) return true;
		return stripos($var['title'], $var['search'])!==FALSE;
	}

	/**
	* Метод обработки строки тегов
	* 	
	* @param string $tags_string Строка тегов, разделенных запятыми
	* @return array
	*/
	public function prepare_tags( $tag_string )
	{
		$tag_array = array();
		
		$tag_items = array_unique( array_map( 'trim', explode( ',', $tag_string ) ) );
		
		foreach ( $tag_items as $tag )
		{
			if ( !$tag ) continue;
			
			$system_name = mb_strtolower( $tag, params::$params["encoding"]["value"] );
			
			$tag_item = db::sql_select( 'select * from TAG where SYSTEM_NAME = :system_name',
				array( 'system_name' => $system_name ) );
			
			if ( count( $tag_item ) == 0 )
			{
				db::insert_record( 'TAG', array( 'TITLE' => $tag, 'SYSTEM_NAME' => $system_name, 'NUM_LINKS' => 0 ) );
				$tag_array[] = db::last_insert_id( 'TAG_SEQ' );
			}
			else
			{
				$tag_array[] = $tag_item[0]['TAG_ID'];
			}
		}
		
		return $tag_array;
	}
	
	/**
	* Метод извлечения из базы тегов текущей записи
	* 	
	* @param array $pk			Первичный ключ записи
	* @return string
	*/
	public function get_tags( $pk )
	{
		$tag_array = db::sql_select( '
			select TAG.TITLE from TAG, TAG_OBJECT where TAG.TAG_ID = TAG_OBJECT.TAG_ID and
				TAG_OBJECT.TE_OBJECT_ID = :te_object_id and TAG_OBJECT.OBJECT_ID = :object_id
			order by TAG.SYSTEM_NAME',
			array( 'te_object_id' => $this -> te_object_id, 'object_id' => $pk[$this -> autoinc_name] ) );
		
		return join( ', ', array_keys( lib::array_reindex( $tag_array, 'TITLE' ) ) );
	}
	
	/**
	* Метод сохранения в базе тегов текущей записи
	* 	
	* @param array $pk			Первичный ключ записи
	* @param string $tag_string Строка тегов, разделенных запятыми
	*/
	public function set_tags( $pk, $tag_string )
	{
		if ( mb_strlen( $tag_string, params::$params["encoding"]["value"] ) > params::$params["taxonomy_length"]["value"] )
			throw new Exception( $this -> te_object_name . ": " . metadata::$lang["lang_taxonomy_length_exceed"] . ": \"" . $this->full_object->get_record_title($pk) . "\" (" . $this->primary_key->pk_to_string($pk) . ")" );
		
		$old_tag_array = db::sql_select( 'select TAG_ID from TAG_OBJECT where TE_OBJECT_ID = :TE_OBJECT_ID and OBJECT_ID = :OBJECT_ID',
			array( 'TE_OBJECT_ID' => $this -> te_object_id, 'OBJECT_ID' => $pk[$this -> autoinc_name] ) );
		
		$tag_array = $this -> full_object -> prepare_tags( $tag_string );
		
		db::delete_record( 'TAG_OBJECT', array( 'TE_OBJECT_ID' => $this -> te_object_id, 'OBJECT_ID' => $pk[$this -> autoinc_name] ) );
		foreach ( $tag_array as $tag_id )
			db::insert_record( 'TAG_OBJECT', array( 'TE_OBJECT_ID' => $this -> te_object_id, 'OBJECT_ID' => $pk[$this -> autoinc_name], 'TAG_ID' => $tag_id ) );
		
		$this -> full_object -> update_tags( array_merge( $tag_array, array_keys( lib::array_reindex( $old_tag_array, 'TAG_ID' ) ) ) );
	}
	
	/**
	* Метод удаления из базы тегов текущей записи
	* 	
	* @param array $pk			Первичный ключ записи
	*/
	public function clear_tags( $pk )
	{
		$tag_array = db::sql_select( 'select TAG_ID from TAG_OBJECT where TE_OBJECT_ID = :TE_OBJECT_ID and OBJECT_ID = :OBJECT_ID',
			array( 'TE_OBJECT_ID' => $this -> te_object_id, 'OBJECT_ID' => $pk[$this -> autoinc_name] ) );
		
		db::delete_record( 'TAG_OBJECT', array( 'TE_OBJECT_ID' => $this -> te_object_id, 'OBJECT_ID' => $pk[$this -> autoinc_name] ) );
		
		$this -> full_object -> update_tags( array_keys( lib::array_reindex( $tag_array, 'TAG_ID' ) ) );
	}
	
	/**
	* Обновление числа ссылок на теги
	* 	
	* @param array $tag_list	Список тегов для обновления
	*/
	public function update_tags( $tag_list )
	{
		$tags_count = db::sql_select( '
			select TAG.TAG_ID, count( TAG_OBJECT.TAG_ID ) as NUM_LINKS
			from TAG left join TAG_OBJECT on TAG_OBJECT.TAG_ID = TAG.TAG_ID
			where TAG.TAG_ID in ( ' . lib::array_make_in( $tag_list ) . ' )
			group by TAG.TAG_ID' );
		
		foreach ( $tags_count as $tags_item )
			db::update_record( 'TAG', array( 'NUM_LINKS' => $tags_item['NUM_LINKS'] ), '', array( 'TAG_ID' => $tags_item['TAG_ID'] ) );
	}
	
	/**
	* Главный метод экспорта, возвращает запись в формате XML
	*/
	
	public function get_export_xml ($pk) {
		if (!$this->full_object->get_change_record($pk)) return '';
		$ret = '<RECORD TABLE_NAME="'.$this->obj.'" RECORD_ID="'.array_shift(array_values($pk)).'">'."\n";
		$ret .= preg_replace("/^/m", "  ", trim($this->get_export_fields_xml($pk)));
		$ret .= preg_replace("/^/m", "  ", trim($this->full_object->get_export_add_data_xml($pk)));
		$ret .= "\n</RECORD>\n";
		return $ret;
	}
	
	/**
	* Метод, строящий XML по данным записи
	* @param array $pk	Первичный ключ записи
	* @return string
	*/
	
	private function get_export_fields_xml($pk) {
		$fields = $this->full_object->get_export_field_values($pk);
		$ret_xml = '';
		foreach ($fields as $field_name=>$field_value) {
			$ret_xml .= "<FIELD FIELD_NAME=\"$field_name\">\n";

			for ($i=0, $n=sizeof($field_value); $i<$n; $i++) 
				$ret_xml .= "  <FIELD_VALUE".$this->get_export_attributes($field_value[$i])."><![CDATA[{$field_value[$i]['value']}]]></FIELD_VALUE>\n";

			$ret_xml .= "</FIELD>\n";
		}
		return $ret_xml;
	}
	
	/*
	* Метод для экспорта данных из таблицы. Возвращает список полей и их значений, 
	* которые необходимо проэкспортировать для данной запмсм. Public для декораторов.
	* Можно дополнить в дочерних классах и декораторах
	* @param array $pk	Первичный ключ записи
	* @return array
	*/
	
	public function get_export_field_values($pk) {
		$fields = array();
		$record = $this->full_object->get_change_record($pk);
		if (!$record) return $fields;
		
		$i=0;
		
		$field_names = $this->full_object->get_fields_for_export();
		
		foreach ($field_names as $field_name) {
			$value=$record[$field_name];
			$fields[$field_name][0] = array("value"=>$value);
			
			$type = metadata::$objects[$this->obj]['fields'][$field_name]['type'];
			if (method_exists($this->field, 'index_'.$type) && !is_array($value)) {
				$method='index_'.$type;
				$fields[$field_name][0]=array('value'=>$this->field->$method($value));
			}

			if (($type=='textarea') && is_string($value)) 
				$fields[$field_name][0]=array("value" => base64_encode($value));
		}
		return $fields;
	}
	
	/**
	* Возвращает список полей для экспорта
	* @return array
	*/
	
	public function get_fields_for_export () {
		$fields = array_keys(metadata::$objects[$this->obj]['fields']);
		$fields = array_combine($fields, $fields);
		unset ($fields[$this->autoinc_name]);
		
		foreach ($fields as $field)
			if (metadata::$objects[$this->obj]['fields'][$field]['virtual'])
				unset($fields[$field]);
		
		return $fields;
	}

	
	/**
	* Возвращает строку с атрибутами и значениями
	* @param array $val_arr	Массив атрибутов
	* @return string
	*/
	
	private function get_export_attributes($val_arr) {
		$ret = '';
		foreach ($val_arr as $attr_name=>$attr_val) 
			if ($attr_name!='value')
				$ret .= " $attr_name=\"$attr_val\"";
		
		return $ret;
	}
	
	/**
	* Данный метод позволяет дополнить значения экспортируемой записи любыми данными
	* Используется в наследниках. public для декораторов
	* @param array $pk	Первичный ключ записи
	* @return string
	*/
	
	public function get_export_add_data_xml($pk) {
		return '';
	}
	
	/**
	* Метод импорта данных из XML
	* @param array $xml_arr массив данных одной записи, возвращаемый ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return array карта соответствия между id записей из файла импорта и реальными
	*/
	
	public function import_from_xml ($xml_arr, &$import_data) {
		if ($this->obj != $xml_arr['attributes']['TABLE_NAME'])
			throw new Exception(metadata::$data['lang_import_bad_import_xml_data']);
		
		$field_values=$this->full_object->get_import_field_values($xml_arr['children'], $import_data);

		$new_id=$this->full_object->exec_add($field_values, '');
		// добавляем в журнал для отката
		db::insert_record(
			'IMPORT_LOG', 
			array (
				"CONTENT_ID" => $new_id,
				"TE_OBJECT_ID" => object_name::$te_object_ids[$this->obj]['TE_OBJECT_ID']
			)
		);

		
		$this->full_object->import_links($new_id, $xml_arr['children'], $import_data);

		return array($xml_arr['attributes']['RECORD_ID']=>$new_id);
	}
	
	/**
	* Возвращает поля для вставки в таблицу в процессе импорта
	* @param array $main_children данные обо всех потомках массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return array Подготовленные данные для вставки в таблицу
	*/

	public function get_import_field_values($main_children, &$import_data) {
		$fields = array();
		for ($i=0, $n=sizeof($main_children); $i<$n; $i++) 
			if ($main_children[$i]['tag']=='FIELD') {
				$field_name = $main_children[$i]['attributes']['FIELD_NAME'];
				$fields[$field_name]=$this->full_object->get_import_field_value($field_name, $main_children[$i]['children'], $import_data);
			}
		return $fields;
	}
	
	/**
	* Возвращает значение для конкретного поля для вставки в таблицу
	* @param string $field_name Название поля
	* @param array $field_children Данные обо всех потомках данного поля массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return mixed Значение, которое вставляется в БД (еще возможно подменить, @see get_import_field_values)
	*/

	public function get_import_field_value($field_name, $field_children, &$import_data) {
		$value = $field_children[0]['value'];
		
		if ((metadata::$objects[$this->obj]['fields'][$field_name]['type']=='textarea')) {
			$value = base64_decode($value);
			if ($import_data['info_data']['BASE64_CONTENT_ENCODING']!=params::$params['encoding']['value'])
				$value=iconv($import_data['info_data']['BASE64_CONTENT_ENCODING'], params::$params['encoding']['value'], $value);
			return $value;
		}
		
		return $value;
	}
	
	/**
	* Возвращает из таблицы соответствий id новый id записи, если данные не найдены - вызывается исключение
	* @param int $old_id старый id по данным экспортных файлов
	* @param string $table название таблицы
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return int
	*/
	
	public final function get_import_new_id($old_id, $table, &$import_data) {
		if (!$new_id=$import_data['id_maps'][$table][$old_id])
			throw new Exception (metadata::$lang['lang_import_not_found_info_about_record_in_table'].' '.$table.', RECORD_ID='.$old_id);		
		return $new_id;
	}
	
	/**
	* Производит импорт связанных данных
	* @param $new_id ID записи
	* @param array $main_children данные обо всех потомках массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	*/
	
	public function import_links($new_id, $main_children, &$import_data) {
		for ($i=0, $n=sizeof($main_children); $i<$n; $i++) {
			if (($main_children[$i]['tag']=='LINKS')&&($records=$main_children[$i]['children'])) {
				if (sizeof($records)) {
					$table_name=$records[0]['attributes']['TABLE_NAME'];
					$obj = object::factory($table_name);
					foreach ($records as $record) {
						$record['children'][]=array('tag'=>'FIELD', 'attributes'=>array('FIELD_NAME'=>$this->autoinc_name), 'children'=>array(array('value'=>$new_id)));
						$obj->import_from_xml($record, $import_data);
					}
					$obj->__destruct();
				}
			}
		}
	}
	
	/**
	* Удаляет ошибочно импортированные данные
	* @param int $content_id ID записи
	*/
	
	public function import_undo ($content_id) {
		if ($this->primary_key->is_record_exists(array($this->autoinc_name=>$content_id), false))
			$this->full_object->exec_delete(array($this->autoinc_name=>$content_id));
	}
	
	/**
	 * Возвращает имя поля типа "Порядок" или false, если такого поля нет
	 */
	public function get_order_field()
	{
		foreach ( metadata::$objects[$this->obj]['fields'] as $field_name => $field )
			if ( $field['type'] == 'order' ) return $field_name;
		return false;
	}
	
	/**
	 * Возвращает информацию о родительской записи
	 */
	public function get_parent_info()
	{
		list( $parent_field, $parent_joins, $parent_binds ) = $this -> full_object -> get_short_title_clause();
		
		$res = db::sql_select( '
				SELECT ' . $this -> obj . '.*
				FROM ' . $this -> obj . ' ' . join( ' ', $parent_joins ) . '
				WHERE ' . $this -> autoinc_name . ' = :pk LIMIT 1',
			array( 'pk' => $this -> parent_id ) + $parent_binds );
		
		// Оракл не поддерживает несколько одинаковых полей в возвращаемой выборке, 
		// в случае включения ее в подзапрос, поэтому заменяем вручную
		$res[0]['TITLE'] = $res[0][$parent_field];
		
		return $res;
	}
	
	/**
	 * Возвращает путь от текущего уровня до корня
	 */
	public function get_parents_list( $parent_info )
	{
		// Создаем новый экземпляр объекта
		$this_object = object::factory( $this -> obj );
		
		// Если необходимо, принудительно устанавливаем тестовую версию в качестве основной
		if ( metadata::$objects[$this -> obj]['decorators']['version'] ) $this_object -> main_version = 1;
		
		// Собираем дополнительные ограничения для запроса
		$ext_index_query = $this_object -> ext_index_query();
		$ext_index_query_binds = $this_object -> ext_index_query_binds();
		
		$parent_list = array();
		
		// Поднимаемся вверх по дереву, собираем информацию о родителях
		while ( $parent_info[0] )
		{
			$parent_list[] = $parent_info[0];
			$parent_id = $parent_info[0][metadata::$objects[$this_object -> obj]['parent_field']];
			
			$parent_where = $this_object -> autoinc_name . ' = :autoinc_name ' . $ext_index_query;
			$parent_binds = array( 'autoinc_name' => $parent_id ) + $ext_index_query_binds;
			
			$parent_info = db::sql_select( 'select * from ' . $this -> obj . ' where ' . $parent_where, $parent_binds );
		}
		
		$this_object -> __destruct();
		
		$path_array = array();
		foreach ( $parent_list as $parent_item )
			$path_array[] = array( 'TITLE' => isset( $parent_item['TITLE'] ) ? $parent_item['TITLE'] : $parent_item['_TITLE'],
				'URL' => $this -> url -> get_url( '', array( 'parent_id' => $parent_item[$this -> autoinc_name], 'no_from' => 1 ) ) );
		
		return $path_array;
	}
	
	/**
	* Проверяет, есть ли опции у какой-нить записи
	* @var array $done_records
	*/
	
	private function is_ops_exists($done_records) {
		foreach ($done_records as $rec) 
			if ($rec['_ops']) return true;
		return false;
	}
}
?>
