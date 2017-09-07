<?php
interface shop_purchase_interface {
	/**
	 * Получить товар
	 * @return op_good
	 */
	function getGood();
	/**
	 * Получить заголовок
	 * @return string
	 */
	function getTitle();
	/**
	 * Получить цену
	 * @return int
	 */	
	function getPrice();
	/**
	 * Выставить свойства:
	 * @param form_interface $form
	 * @return boolean
	 */
	function setProperties($form);	
	/**
	 * Получить список полей для выбора свойств
	 * @param form_interface $form
	 * @return array
	 */
	function getForm($form);
	/**
	 * Очистить данные:
	 * @return boolean
	 */
	function clean();
	/**
	 * Получить участок запроса для добавления в корзину
	 * @return array
	 */
	function getQuery();
	/**
	 * Получить UID
	 * @return array
	 */
	function getUID();
	/**
	 * Получить список ошибок
	 * @return array
	 */
	function getErrors();
	/**
	 * Проверить доступность добавления в корзину
	 * @return array
	 */
	function isValid();
	/**
	 * Проверить доступность данного количества покупок
	 * @return boolean
	 */
	function validateCount($count);
	/**
	 * Зарезервировать $count элементов
	 * @param shop_basket_element $element	элемент
	 * @param int $basketID	id корзины
	 * @return int
	 */
	function reserve($element, $basketID);
	/**
	 * Разрезервировать $count элементов
	 * @param $count
	 * @return boolean
	 */
	function free($count);
	/**
	 * Получить список тегов, для поиска скидок
	 * @return array
	 */
	function getTags();
}
?>