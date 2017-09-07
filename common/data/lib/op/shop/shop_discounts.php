<?php
class shop_discounts {
	/**
	 * Загрузить скидки для клиента
	 * @param object $client
	 * @return shop_discounts
	 */
	static function loadByClient($client){		
		return new shop_discounts();//i am stub
	}
	/**
	 * Получить скидки
	 * @param array $tags
	 * @return array
	 */
	function getDiscounts($tags=array()){		
		return array(new shop_discount_special_price(), new shop_discount_by_total());//i am a stub
	}
		
	static $shop_discount_special_price=1;
}
?>