<?php
/**
 * Элемент корзины
 * @author atukmanov
 *
 */
class shop_basket_element extends DataStore{
	/**
	 * Покупка
	 * @var shop_purchase_interface
	 */
	var $purchase=null;
	/**
	 * Количество элементов в покупке:
	 * @var unknown_type
	 */
	var $count=1;
	/**
	 * 
	 * @param shop_purchase_interface $purchase
	 * @param int $count
	 * @return unknown_type
	 */
	function __construct($purchase, $count){
		$purchase->clean();
		$this->purchase=$purchase;
		$this->setCount($count);
	}
	/**
	 * Обновить количество элементов:
	 * @param $count
	 * @return boolean
	 */
	function setCount($count){
		$this->count=$count;		
		$this->finalTotal=$this->getCount()*$this->getPrice();
		$this->displayedTotal=$this->finalTotal;
	}
	/**
	 * Сбросить скидки и т.п.:
	 * @return unknown_type
	 */
	function refresh(){
		$this->setCount($this->getCount());
		$this->stopDiscount=false;
		$this->discounts=array();
		$this->globalDiscounts=array();
	}
	/**
	 * Количество элементов
	 * @return int
	 */
	function getCount(){
		return $this->count;
	}
	/**
	 * Стоимость единицы товара
	 * @return double
	 */
	function getPrice(){
		return $this->purchase->getPrice();
	}
	/**
	 * Итоговая сумма:
	 * @var double
	 */
	protected $finalTotal=0;
	/**
	 * Отображаемая сумма:
	 * @var double
	 */
	protected $displayedTotal=0;
	/**
	 * Итоговая цена (для рассчета)
	 * @return double
	 */
	function getFinalTotal(){
		return $this->finalTotal;
	}
	/**
	 * Стоимость всех элементов с учетом скидок для вывода
	 * @return double
	 */
	function getTotal(){
		return $this->displayedTotal;
	}	
	/**
	 * 
	 * @var array 
	 */
	protected $discounts=array();
	/**
	 * Глобальные скидки:
	 * @var unknown_type
	 */
	protected $globalDiscounts=array();
	/**
	 * Выставить новую итоговую цену:
	 * @param double  $total 		цена
	 * @param string  $message		сообщение скидки
	 * @param int	  $discountID	получить id скидки
	 * @param mixed	  $discountData	внутренняя информация о скидках
	 * @param boolean $global		глобальная скидка
	 * @return boolean
	 */
	function setTotal($total, $message, $discountID, $discountData, $global){
		if ($this->stopDiscount) return false;
		 
		if ($global){
			$this->globalDiscounts[$discountID]=array(
				'message'=>$message,
				'discountID'=>$discountID,
				'global'=>$global,
				'summ'=>$this->finalTotal-$total,//сумма скидки
			);
		}
		else {
			$this->discounts[$discountID]=array(
				'message'=>$message,
				'discountID'=>$discountID,
				'discountData'=>$discountData,
				'global'=>$global,		
			);			
			$this->displayedTotal=$total;
		}		
		$this->finalTotal=$total;		
	}
	
	protected $stopDiscount=false;
	/**
	 * Прекратить обработку скидками:
	 * @return boolean
	 */
	function stopDiscount(){		
		$this->stopDiscount=true;
	}	
	/**
	 * Получить примененные скидки
	 * @return array массив скидок структура элемента:
	 *					message- сообщение
	 */	
	function getDiscounts(){
		$ret=$this->discounts;
	}
	/**
	 * Глобальные скидки
	 * @return array
	 */
	function getGlobalDiscounts(){
		return $this->globalDiscounts;
	}
	
	function getTags(){
		return $this->purchase->getTags();
	}
	/**
	 * Вызвать:
	 * @param $method
	 * @return unknown_type
	 */
//	function __call($method){
//		return $this->purchase->$method();
//	}
	/**
	 * Получить данные:
	 * @param $path
	 * @return unknown_type
	 */
	function _getInfo($path){
		
		switch (str::lower($path[0])){		
			case 'total':
				return $this->getTotal();
			break;
			case 'finaltotal':
				return $this->getFinalTotal();
			break;
			case 'count':
				return $this->getCount();
			break;
			case 'discounts':				
				return $this->getDiscounts();
			break;			
			default:
				return $this->purchase->_getInfo($path);
			break;
		}
	}
}
?>