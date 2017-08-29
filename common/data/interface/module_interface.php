<?php
/**
 * Интерфейс модулей.
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
interface module_interface{

	/**
	 * Создает экземпляр модуля
	 *
	 * $param string $obj	системное название модуля в верхнем регистре
	 */
	public static function factory($obj="");

	/**
	 * Запуск работы модуля
	 *
	 * Инициализирует модуль параметрами окружения, представления и запроса, а также информацией от предыдущих модулей.
	 * Исполняет модуль до состояния, когда можно использовать get_body(), get_title()
	 * 
	 * @param array $env			сведения об окружении - раздел, язык и т.д.
	 * @param array $view_params	параметры представления модуля
	 * @param array $module_info	информация, которую предыдущие модули хотят передать последующим. В системе существует один экземпляр этой переменной, модули получают его по ссылке и могут модифицировать его как им это нужно
	 */
	public function init($env, $view_params, &$module_info);

	/**
	 * Возвращает продукт работы модуля - контент в формате HTML
	 */
	public function get_body();

	/**
	 * Возвращает заголовок страницы
	 */
	public function get_title();

	/**
	 * Возвращает ключевые слова страницы
	 */
	public function get_keywords();

	/**
	 * Возвращает описание страницы
	 */
	public function get_description();
}
?>