<?php
/**
 * Абстрактный класс реализации внутренних таблиц
 *
 * Сейчас этот класс нужен только для того, чтобы служить заглушкой при ошибочном вызове внутренней таблицы.
 * Сами же внутренние таблицы нужны для того, чтобы набор их полей можно было описать в def-файле.
 *
 * @package    RBC_Contents_5_0
 * @subpackage core
 * @copyright  Copyright (c) 2007 RBC SOFT
 */
class internal_table extends object{

	/**
	 * Внутренние таблицы не имеют собственного интерфейса, о чем мы и сообщаем
	 */
	public function action_index(){
		throw new Exception(metadata::$lang["lang_object_without_interface"]);
	}
}
?>