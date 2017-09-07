<?php
/**
 * Интерфейс формы
 * @author atukmanov
 *
 */
interface form_interface {	
	/**
	 * Строка из запроса:
	 * @param mixed $key		путь до переменной запроса
	 * @param mixed $default	значение по умолчанию
	 * @param $preg				регулярное выражение
	 * @return mixed
	 */
	function requestValue($key, $default=null, $preg=null);
	/**
	 * Число из запроса:
	 * @param $key
	 * @param $default
	 * @return int
	 */
	function requestInt($key, $default=0);
	/**
	 * Получить имя поля
	 * @return string
	 */
	function getFieldName();
	/**
	 * Получить id поля:	 
	 * @return string
	 */
	function getFieldID();
	/**
	 * Собрать ссылку:
	 * @param $params
	 * @return string
	 */
	function buildLink($params=array());
	/**
	 * Получить id формы
	 * @return string
	 */
	function getFormID();
	/**
	 * Переменные окружения:
	 * @return array
	 * @return unknown_type
	 */
	function getEnv();
}
?>