<?php
interface shop_discount_interface {	
	/**
	 * Выставить клиента
	 * @param $client
	 * @return void
	 */
	function setClient($client);
	/**
	 * Выставить корзину
	 * @param shop_basket $basket
	 * @return void
	 */
	function setBasket($basket);
	/**
	 * Применить элемент:
	 * @param shop_basket_element $element
	 * @return shop_basket_element
	 */
	function applyElement($element, $handler);
}
?>