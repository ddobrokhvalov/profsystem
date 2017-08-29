<?php
/**
 * Класс названия объекта
 *
 * Содержит название текущего объекта и его идентификатор
 * Наследуется всеми классами, которым нужна эта информация
 *
 * @package    RBC_Contents_5_0
 * @subpackage core
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
abstract class object_name{

	/**
	 * Название текущего объекта
	 * @var string
	 */
	public $obj;

	/**
	 * Идентификатор текущего объекта, применяется для регистрации операций над записями во всяческих журналах
	 * @var int
	 */
	public $te_object_id;

	/**
	 * Отображаемое название текущего объекта, применяется, например, для вывода названия объекта при ошибках
	 * @var int
	 */
	public $te_object_name;

	/**
	 * Флаг, отражающий участие объекта в таксономии	 
	 * @var int
	 */
	public $is_taxonomy;

	/**
	 * Список записей всех объектов с индексами равными системному имени объекта
	 *
	 * При первом конструировании объекта производится заполнение этой переменной для того, чтобы конструирование последующих объектов не вызывало лишних запросов в БД
	 *
	 * @var array
	 */
	static public $te_object_ids=array();

	/**
	 * Список записей всех объектов с индексами равными идентификатору объекта
	 *
	 * При первом конструировании объекта производится заполнение этой переменной для того, чтобы конструирование последующих объектов не вызывало лишних запросов в БД
	 *
	 * @var array
	 */
	static public $te_object_names=array();

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Конструктор
	 * 
	 * @param string $obj Системное название объекта
	 */
	public function __construct($obj){
		$this->obj=$obj;
		// Заполняем self::$te_object_ids, если это свойство еще не заполнено
		if(count(self::$te_object_ids)==0){
			$te_objects=db::replace_field(db::sql_select("
				SELECT TE1.*, TT.VALUE as \"_TITLE\"
				FROM TE_OBJECT TE1
					LEFT JOIN LANG ON
						LANG.ROOT_DIR = :root_dir
					LEFT JOIN TE_OBJECT TE2 ON
						TE2.SYSTEM_NAME = 'TE_OBJECT'
					LEFT JOIN TABLE_TRANSLATE TT ON
						TT.TE_OBJECT_ID=TE2.TE_OBJECT_ID AND
						TT.LANG_ID=LANG.LANG_ID AND
						TT.CONTENT_ID=TE1.TE_OBJECT_ID AND
						TT.FIELD_NAME='TITLE'",
				array( 'root_dir' => params::$params['default_interface_lang']['value'] ) ), 'TITLE', '_TITLE');
			self::$te_object_ids=lib::array_reindex($te_objects, "SYSTEM_NAME");
			self::$te_object_names=lib::array_reindex($te_objects, "TE_OBJECT_ID");
		}
		// Выставляем идентификатор
		$this->te_object_id=self::$te_object_ids[$obj]["TE_OBJECT_ID"];
		// Если название объекта описано языковой константой, то берем его из констант, иначе оставляем как есть
		$this->te_object_name=(strpos(self::$te_object_names[$this->te_object_id]["TITLE"], "lang_")===0 ? metadata::$lang[self::$te_object_names[$this->te_object_id]["TITLE"]] : self::$te_object_names[$this->te_object_id]["TITLE"]);
		
		// Вычисляем возможность подключения таксономии
		$this -> is_taxonomy = self::$te_object_ids[$obj]["TAXONOMY_ALLOWED"];
	}

	/**
	 * Возвращает название текущего объекта, чтобы объект-агрегатор мог сделать что-то специальное, например, меню подсветить
	 * 
	 * @return string
	 */
	public function get_object_name(){
		return $this->obj;
	}
}
?>
