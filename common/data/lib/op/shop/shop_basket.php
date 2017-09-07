<?php
/**
 * Корзина
 * @author atukmanov
 *
 */
class shop_basket extends DataStore implements Countable{
	/**
	 * Элементы:
	 * @var array
	 */	
	protected $elements=array();
	/***
	 * Sequence
	 * @var int
	 */
	protected $nextID=0;
	/**
	 * Добавить покупку
	 * @param shop_purchase_interface $purchase покупка
	 * @param int $count количество
	 * @param int $handler	указатель на замещаемый элемент
	 * @return int handler
	 */
	function addPurchase($purchase, $count, $handler=null){
		if (!$handler){
			$handler=$this->nextID;
			$this->nextID++;
		}		
		$this->elements[$handler]=new shop_basket_element($purchase, $count);
		$this->refresh();		
		return $handler;
	}
	/**
		Количество записей:
	 */
	public function count(){
		return count($this->elements);
	}
	/**
	 * Удалить элемент из корзины
	 * @param int $handler
	 * @return boolean
	 */
	function removePurchase($handler){
		if (!isset($this->elements[$handler])) return false;
		$ret=$this->elements[$handler];
		unset($this->elements[$handler]);
		$this->refresh();
		return $ret;
	}
	/**
	 * Обновить количество покупок в корзине
	 * @param array $count ассоциативный массив, где ключ- handler элемента, значение- количество
	 * @return boolean
	 */
	function updateCount($arr){
		foreach ($arr as $handler=>$count){
			if (!isset($this->elements[$handler])) throw new Exception('Invalid handler '.$handler);
			$obj=&$this->elements[$handler];
			/*@var shop_basket_element $obj*/
			$obj->setCount($count); 
		}
		$this->refresh();
	}
	/**
	 * Обновить:
	 * @return unknown_type
	 */
	protected function refresh(){
		$this->total=0;//сбрасываем цену
		$this->gifts=array();//сбрасываем скидку
		foreach ($this->elements as &$element){
			/*@var shop_basket_element $element*/
			$element->refresh();
		}		
	}
	/**
	 * Получить теги
	 * @return array
	 */
	function getTags(){
		$ret=array();
		foreach ($this->elements as &$element){
			/*@var shop_basket_element $element*/
			$ret=array_merge($ret,$element->getTags());
		}
		return array_unique($ret);
	}
	/**
	 * Применить скидки
	 * @param $discounts массив скидок
	 * @return void
	 */
	function applyDiscounts($discounts){
		$this->refresh();
		foreach ($discounts as $discount){
			
			/*@var shop_discount_interface*/
			if (!$discount->setBasket($this)) continue;//discount not supported
			foreach ($this->elements as $handler=>&$element){
				$this->elements[$handler]=$discount->applyElement($element, $handler);
			}
			$this->total=null;//пересчитываем цену на следующем шаге
		}
	}
	/**
	 * Получить стоимость до "глобальных скидок"
	 * @return int
	 */
	function getPreTotal(){
		$ret=0;
		foreach ($this->elements as $obj){
			/*@var shop_basket_element $obj*/
			$ret+=$obj->getTotal();
		}
		return $ret;
	}
	
	protected $total=null;
	/**
	 * Получить стоимость
	 * @return int
	 */
	function getTotal(){
		if ($this->total!==null)
		$this->total=0;
		$giftFreeTotal=0;
		/**
		 * Вычисляем суммарную стоимость:
		 */
		foreach ($this->elements as $handler=>$obj){
			/*@var shop_basket_element $obj*/
			$this->total+=$obj->getFinalTotal();
			if (count($this->giftHandlers)&&!in_array($handler, $this->giftHandlers)){
				//Если на объект не выставлен "подарок" инкрементируем сумму покрываемую "подарками":
				$giftFreeTotal+=$obj->getFinalTotal();
			}
		}
		/**
		 * Вычитаем подарки:
		 * Смысл в том, что каждый подарок представляет из себя некоторую бонусную сумму
		 */
		$gift=0;
		foreach ($this->gifts as $handler=>$gift){
			if ($giftFreeTotal>$gift['gift']){
				//Есть еще лимит на подарки:
				$ret-=$gift;
				$giftFreeTotal-= $gift['gift'];
				$this->gifts[$handler]['used']=$gift['gift'];
				$this->gifts[$handler]['rest']=0;//Нулевой остаток (подарок полностью использован)
			}
			else {
				$ret-=$giftFreeTotal;//Вычитаем частично подарок
				$this->gifts[$handler]['used']=$giftFreeTotal;		
				$this->gifts[$handler]['rest']=$this->gifts[$handler]['gift']-$giftFreeTotal;//Запоминаем остаток от подарка
				$giftFreeTotal=0;//Обнуляем остаток
			}
		}
		
		return $this->total;
	}
		
	/**
	 * Остаток по подаркам:
	 * @return int
	 */
	function getGiftsRest(){
		$this->getTotal();//Вычисляем остатки перед возвращением
		$ret=0;
		foreach ($this->gifts as $gift){
			$ret+=$gift['rest'];
		}
		return $ret;
	}
	/**
	 * Получить скидки
	 * @return array
	 */
	function getDiscounts(){
		$ret=array();
		foreach ($this->elements as $element){
			/*@var shop_basket_element $element*/
			foreach ($element->getGlobalDiscounts() as $discountID=>$discount){
				if (!isset($ret[$discountID])) $ret[$discountID]=$discount;
				else $ret[$discountID]['summ']+=$discount['summ'];
			}			
		}
		return $ret;
	}
	/**
	 * Подарки:
	 * @var array
	 */
	protected $gifts=array();
	/**
	 * Хэндлеры для подарков:
	 * @var array
	 */
	protected $giftHandlers=array();
	/**
	 * Выставить подарок:
	 * @param double  $gift сумма подарка
	 * @param int	  $elementHandler	хэндлер элемента корзины, по поводу которого выставлен подарок
	 * @param string  $message		сообщение скидки
	 * @param int	  $discountID	получить id скидки
	 * @param mixed	  $discountData	внутренняя информация о подарке	 	
	 * @return boolean
	 */
	function setGift($gift, $elementHandler, $message, $discountID, $discountData){
		$this->giftHandlers[]=$elementHandler;
		$this->gifts[]=array(
			'gift'=>$gift,
			'handler'=>$handler,		
			'message'=>$message,
			'discountID'=>$discountID,
			'discountData'=>$discountData,			
		);
			
	}
	/**
	 * 
	 * @return получить данные
	 */
	function _getInfo($path){
		switch (str::lower($path[0])){
			case 'elements':
				return $this->elements;
			break;
			case 'pretotal':
				return $this->getPreTotal();
			break;	
			case 'discounts':
				return $this->getDiscounts();
			break;		
			case 'total':
				return $this->getTotal();
			break;
			case 'numelements':
				return count($this->elements);
			break;
		}
	}
	/**
	 * Сохранить корзину с привязкой к заказу
	 * @param int $order id заказа 
	 * @return boolean
	 */
	function save($order){
		foreach ($this->elements as $element){
			/*@var shop_basket_element $element*/
			$element->purchase->reserve($element, $order);
		}
	}
	/**
	 * Загрузить по заказу
	 * @param int $order загрузить по заказу
	 * @return shop_basket
	 */
	static function loadByOrder($order){
		
	}
	/**
	 * Отменить заказ, проходиться по всем элементам и делает free у соотв. покупок
	 * @return boolean
	 */
	function cancel(){
		
	}
}
?>