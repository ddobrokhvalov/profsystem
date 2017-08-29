<?php
/**
 * Класс работы с полями для таблицы "Администраторы".
 *
 * Отличается от стандартного тем, что вводит новый тип поля password_md5, который никому кроме администратров не нужен
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class field_auth_user extends field{

	/**
	 * В списке пароль не выводится
	 */
	public function index_password_md5($content){return "";}

	/**
	 * На карточке изменения/добавления пароль не выводится
	 */
	public function change_password_md5($content){return "";}

	/**
	 * Никаких спецпроверок не требуется
	 */
	protected function check_type_password_md5($content, $field_descr){}

	/**
	 * Формируем из исходного значения md5
	 */
	public function prepare_password_md5($content){
		return md5($content);
	}
}
?>