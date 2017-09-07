<?php
class m_basket {
	/**
	 * Корзина:
	 * @var shop_basket
	 */
	var $basket;
	/**
	 * Запустить корзину
	 * @return void
	 */
	function initBasket(){
		if (!$this->basket){
			if (isset($_SESSION['basket'])){
				$this->basket=&$_SESSION['basket'];
			}
			else {
				$this->basket=new shop_basket();
				$_SESSION['basket']=&$this->basket;
			}
		}
	}
	const invalidCount='lang_invalid_count';
	const invalidParams='lang_invalid_params';
	/**
	 * Добавить товар в корзину:
	 * @return void
	 */
	function doAdd(){
		if (!$_SESSION['basket']) $basket=$_SERVER['basket'];
		else $basket= new shop_basket();
		
		if ($add=$this->q_param['add']){
			if (!$good= shop_good::loadByID($add)){
				//Нет такого товара:
				application::throwError('404');
			}
			else {
				//Получаем покупку:
				$purchase=$good->getPurchase($this->q_param['properties']);
				$count=request::Int('count',1);
				//Проверяем покупку:
				if ($purchase->isValid()){
					
					if ($purchase->validateCount($count)){
						$basket->addPurchase($purchase, $count);
						$this->refreshBasket();
						$this->printBasket();
					}
					else {
						$this->printPurchaseForm($purchase, $count, self::invalidCount);
					}
				}
				else {
					$this->printPurchaseForm($purchase, $count, self::invalidParams);
				}
			}
		}
		else {
			application::throwError('404');
		}
	}
	
	var $body='';
	/**
	 * Вывести форму заказа:
	 * @param shop_purchase_interface $purchase
	 * @param $count
	 * @return int
	 */
	function printPurchaseForm($purchase, $count, $error){
		$this->body='<form method="post">'.$purchase['good']['title'].'<div class="p">';
		foreach ($purchase->getForm('$_'.$this->env['area_id']) as $field){
			$this->body.='<p><label for="'.$field['htmlFor'].'">'.$field['title'].'</label>'.$field['input'].'</p>';			
		}
		$this->body.='<p><label for="count_'.$this->env['area_id'].'">Количество:</label><input type="text" name="count_'.$this->env['areaID'].'" value="'.$count.'"/></label>';
		$this->body.='<input type="submit" name="update" value="OK"/>';
	}
	/**
	 * Обновить корзину
	 * 
	 */
	function refreshBasket(){		
		$this->basket->applyDiscounts(shop_discounts::loadByClient($this->getClient())->getDiscounts($this->basket->getTags()));
	}
	/**
	 * Вывести корзину:
	 * @return 
	 */
	function printBasket(){
		foreach ($basket as $element){
			/*@var shop_basket_element $element*/
			?>
<table>
{foreach from=$basket item="element" key="handler"}
<tr><td>{$item.title}</td><td>{$item.price}</td><td><input type="text" name="total_{$env.area_id}[{$handler}]" value="{$item.count}"/></td><td>{$item.total}</td>
<td>
<ul class="discounts">
{foreach from=$item.discounts item="discount"}
	<li>{$discount.message}</li>
{/foreach}
</ul>
</td>
</tr>
{/foreach}
{if count($basket.discounts)}
<tr><td colspan="3">Подитог:</td><td>{$basket.preTotal}</td></tr>
	{foreach from=$basket.discounts item="discount"}
		<tr><td></td><td colspan="2">{$discount.message}</td><td>-{$discount.summ}</td></tr>
	{/foreach}
{/if}
<tr class="total"><td colspan="3">Итого:</td><td class="summ">{$basket.total}</td></tr>
</table>

			<?
		}
	}
}
?>