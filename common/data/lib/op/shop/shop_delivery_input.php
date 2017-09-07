<?php
/**
 * Поле выбора формы доставки:
 * @author atukmanov
 *
 */
class shop_delivery_input extends form_input {
	/**
	 * 
	 * @var m_shop_basket
	 */
	var $form;
	/**
	 * Варианты:
	 * @var array
	 */
	protected $options=null;
	/**
	 * Получить формы доставки:
	 * @return array
	 */
	function getOptions(){
		$total=$this->form->getBasket()->getTotal();
		$sel=new rbcc5_select($this->getSetting('delivery_table','SHOP_DELIVERY'));
		$sel->OrderBy($sel->getOrderField(),'ASC');
		$this->options=array();		
		foreach ($sel as $deliveryType){
			
			$deliveryID=$deliveryType[$sel->primary_key];
			
			$prices= new rbcc5_select($this->fk_table);
			$prices->Where($sel->primary_key,eq,$deliveryID);
			//ищем минимальную цену:
			$prices->OrderBy('PRICE','ASC');
			//для нашей корзины:
			$prices->Where('TOTAL',smaller_or_eq,$total);
			//тт:			
			if ($price=rbcc5_object::fetchObject($prices)){				
				$price->Info['DELIVERY_TYPE']=$deliveryType;
				$this->options[$price->getID()]=$price;
			}
		}
		return $this->options;
	}
	/**
	 * Проверить:
	 */
	function validate(){
		if (!$id=$this->form->requestInt($this->name)) return $this->throwEmpryField();
		//Получаем элемент:
		$sel= new rbcc5_select($this->fk_table);
		$sel->Where($sel->primary_key, eq, $id);
		$deliveryType= new rbcc5_select($this->getSetting('delivery_table','SHOP_DELIVERY'));
		$sel->Join($deliveryType, $deliveryType->primary_key, $deliveryType->primary_key, 'DELIVERY_TYPE','LEFT');
		//Получаем объект:
		if (!$this->obj=rbcc5_object::fetchObject($sel)){
			//Не найден:
			return $this->throwError();
		}
		if ($this->obj->TOTAL>=$this->form->getBasket()->getTotal()){
			//Для данной стоимости корзины доставка запрещена:
			return $this->throwError();
		}
		return true;
	}
	/**
	 * Форма:
	 */
	function printForm(){
		$checkedID=($this->obj)?$this->obj->getID():0;
		echo '<ul id="',$this->form->getFieldID($this->name),'">';
		foreach ($this->getOptions() as $option){						
			echo '<li><input type="radio" name="',$this->form->getFieldName($this->name),'" id="',$this->form->getFieldID($this->name, $option->getID()),'" value="',$option->getID(),'"',($this->checkedID==$option->getID())?' checked':'','/><label for="',$this->form->getFieldID($this->name, $option->getID()),'">',$option->DELIVERY_TYPE['TITLE'];
			if ($option['MESSAGE']){
				echo ' <span class="price_message">',$option['MESSAGE'],'</span>';
			}
			else {
				echo ' <span class="',($option->PRICE)?'price':'taxfree','">',($option->PRICE)?$option->PRICE:$this->tax_free,'</span>';
			}
			echo '</label>';
		}
		echo '</ul>';
	}
}
?>