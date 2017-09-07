<?php
/**
 * Скидка "специальная цена"
 * @author atukmanov
 *
 */
class shop_discount_by_total implements shop_discount_interface{
	/**
	 * Выставить клиента
	 * @param $client
	 * @return void
	 */
	function setClient($client){
		/*i am stub*/
	}
	
	var $discountSize=0;
	var $discount=null;
	/**
	 * Выставить корзину
	 * @param shop_basket $basket
	 * @return void
	 */
	function setBasket($basket){
		$total=$basket->getTotal();
		$sel= new rbcc5_select('SHOP_DISCOUNTS_BY_SUMM');
		$sel->Where('TOTAL',smaller_or_eq,$basket->getTotal());
		$sel->OrderBy('DISCOUNT','DESC');
		if ($this->discount=rbcc5_object::fetchObject($sel)){					
			$this->discountSize=(100-$this->discount->DISCOUNT)/100;		
			return true;
		}
		else {
			return false;
		}
	}
	/**
	 * Применить элемент:
	 * @param shop_basket_element $element
	 * @return shop_basket_element
	 */
	function applyElement($element, $handler){				
		//применяем скидку:
		//str::print_r('setTotal', $element->getFinalTotal()*$this->discountSize, $element->getCount(), $element->getFinalTotal(), $this->discountSize);
		$element->setTotal($element->getFinalTotal()*$this->discountSize,$this->discount->TITLE, $this->discount->table.'.'.$this->discount->getID(),null,true);
		return $element;	
	}
}
?>