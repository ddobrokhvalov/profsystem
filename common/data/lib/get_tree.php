<?php
/**
 * Класс построения дерева из переданного списка записей
 *
 * Деревом является список пересортированных с учетом иерархии записей с добавленным полем TREE_DEEP, в котором содержится глубина уровня записи.
 * Для упрощения использования класса инстанцирование скрыто в статический метод get_tree::get(), который сразу и возвращает готовое дерево
 *
 * @package    RBC_Contents_5_0
 * @subpackage lib
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class get_tree extends lib_abstract{
	/**
	 * Вспомогательный список записей, перестроенный по родителям записей
	 * @var array
	 */
	private $rs_by_parent=array();
	/**
	 * Готовое дерево
	 * @var array
	 */
	private $tree=array();
	/**
	 * Название поля с первичным ключом (то есть автоикрементное поле записи, оно же - идентификатор)
	 * @var string
	 */
	private $primary_field;
	/**
	 * Название поля с идентификатором родителя
	 * @var string
	 */
	private $parent_field;
	/**
	 * Название поля по которому должна проводиться сортировка внутри уровня иерархии
	 * @var string
	 */
	private $order_field;
	/**
	 * Массив идентификаторов записей, которые (и их дети) быть исключены из результирующего дерева
	 * @var array
	 */
	private $exclude=array();

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Метод, который непосредственно используется для построения дерева. Возвращает готовое дерево
	 * 
	 * @param array $result_set		исходный список записей
	 * @param string $primary_field	название поля с первичным ключом (то есть автоикрементное поле записи, оно же - идентификатор)
	 * @param string $parent_field	название поля с идентификатором родителя
	 * @param string $order_field	название поля по которому должна проводиться сортировка внутри уровня иерархии (необязательное)
	 * @param int $include			идентификатор записи, с детей которой нужно начинать строить дерево; если 0, то все записи
	 * @param array $exclude		массив идентификаторов записей, которые (и их дети) должны быть исключены из результирующего дерева
	 * @return array
	 */
	public static function get($result_set, $primary_field, $parent_field="PARENT_ID", $order_field="", $include=0, $exclude=array()){
		$tree=new get_tree($result_set, $primary_field, $parent_field, $order_field, $include, $exclude);
		return $tree->tree;
	}

	/**
	 * Метод, который непосредственно используется для построения списка потомков. Возвращает список потомков
	 * 
	 * @param array $result_set		исходный список записей
	 * @param string $primary_field	название поля с первичным ключом (то есть автоикрементное поле записи, оно же - идентификатор)
	 * @param string $parent_field	название поля с идентификатором родителя
	 * @param string $order_field	название поля по которому должна проводиться сортировка внутри уровня иерархии (необязательное)
	 * @param array $include		массив идентификаторов записей, потомков которых требуется найти
	 * @param array $exclude		массив идентификаторов записей, которые (и их дети) должны быть исключены из результирующего дерева
	 * @param boolean $parent_first	родительские записи помещаются в массив раньше дочерних
	 * @return array
	 */
	public static function get_children($result_set, $primary_field, $parent_field="PARENT_ID", $order_field="", $include=array(), $exclude=array(), $parent_first=false){
		$tree=new get_tree($result_set, $primary_field, $parent_field, $order_field, $include, $exclude, $parent_first);
		return $tree->tree;
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Конструктор. Заполняет свойства и запускает построение дерева
	 *
	 * Параметры в точности такие же как у get_tree::get()
	 */
	private function __construct($result_set, $primary_field, $parent_field, $order_field, $include, $exclude, $parent_first=false){
		// Свойства
		$this->primary_field=$primary_field;
		$this->parent_field=$parent_field;
		$this->order_field=$order_field;
		$this->exclude=$exclude;
		$this->parent_first=$parent_first;
		
		// На всякий случай проверяем, чтобы $result_set был массивом
		if(!is_array($result_set)){
			$result_set=array();
		}
		// Если нужна сортировка, то пересортировываем
		if($this->order_field){
			usort($result_set, array($this, "tree_item_compare"));
		}
		// Строим список по родителям
		foreach($result_set as $rs){
			$this->rs_by_parent[$rs[$this->parent_field]][]=$rs;
		}
		
		if ( is_array( $include ) )				// Строим список потомков
			foreach ( $include as $parent_id )
				$this -> build_level_children( $parent_id );
		else 									// Строим само дерево
			$this -> build_level( $include, 0 );
	}

	/**
	 * Рекурсивный метод постройки уровня дерева
	 *
	 * @param int $start_from	идентификатор для которого строится уровень
	 * @param int $deep			глубина залегания этого уровня
	 */
	private function build_level($start_from, $deep){
		if(is_array($this->rs_by_parent[$start_from])){
			foreach($this->rs_by_parent[$start_from] as $child){
				if( !in_array( $child[$this->primary_field], $this->exclude ) ){
					$child["TREE_DEEP"]=$deep;
					$this->tree[]=$child;
					$this->build_level($child[$this->primary_field], $deep+1);
				}
			}
		}
	}

	/**
	 * Рекурсивный метод постройки списка потомков
	 *
	 * @param int $parent_id	идентификатор родительской записи
	 */
	private function build_level_children( $parent_id )
	{
		if ( in_array( $parent_id, $this -> exclude ) ) return false;
		
		if ( $this -> parent_first )
			$this -> tree[] = $parent_id;
		
		if ( is_array( $this -> rs_by_parent[$parent_id] ) )
			foreach ( $this -> rs_by_parent[$parent_id] as $page )
				$this -> build_level_children( $page[$this->primary_field] );
		
		if ( !$this -> parent_first )
			$this -> tree[] = $parent_id;
	}
	
	/**
	 * Сравнение записей. Callback метод для сортировки записей
	 * Сравнение идет строковой функцией, так как поля в записях все равно имеют строковый тип 
	 *
	 * @param string $a	первое сравниваемое значение
	 * @param string $b	второе сравниваемое значение
	 * @return int
	 * @todo Если понадобится сортировать по числу, то нужно будет предусмотреть учет типа поля
	 */
	private function tree_item_compare($a, $b){
		return strnatcmp($a[$this->order_field], $b[$this->order_field]);
	}
}
?>