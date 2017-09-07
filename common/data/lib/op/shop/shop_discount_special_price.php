<?php
/**
 * Скидка "специальная цена"
 * @author atukmanov
 *
 */
class shop_discount_special_price implements shop_discount_interface{
	/**
	 * Выставить клиента
	 * @param $client
	 * @return void
	 */
	function setClient($client){
		/*i am stub*/
	}
	/**
	 * Выставить корзину
	 * @param shop_basket $basket
	 * @return void
	 */
	function setBasket($basket){
		/*i am stub*/
		return true;
	}
	/**
	 * Применить элемент:
	 * @param shop_basket_element $element
	 * @return shop_basket_element
	 */
	function applyElement($element, $handler){
		if (!$element->SPECIAL_PRICE){
			return $element;//no special price
		}		
		//применяем скидку:
		$element->setTotal($element->getCount()*$element->SPECIAL_PRICE,'Специальная цена', shop_discounts::$shop_discount_special_price,null,false);
		$element->stopDiscount();
		return $element;	
	}
}
?>