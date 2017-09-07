<?php
/**
 * Позволяет работать с выборкой как с хранилищем данных.
 * Нужен для работы с тегом postload, в первую очередь
 * 
 * Хитрость в том, чтобы задать выборку в контроллере, и работать в postload как с данными.
 * 
 * controller
 *
 	$ItemsID=array();
	foreach($ItemsSelect as $id=>$Item){
		$this->Info['Items'][$id]=$Item;
		$ItemsID[]=$id;
	}
	$sel= new UserRateSelect();
	$sel->PrimaryKey='ItemID';
	//Оценки для выбранных статей:
	$sel->Where('ItemID',eq,$ItemsID);
	$this->Info['UsersRate']=new dbselect_store($sel);
	//Сохраняем результат выполнения шаблона как скрипт:
	$this->render_postload('items', $_SERVER['REQUEST_URI']);


	шаблон:

	<!--op:postload-->
	<?
		$Me->Info['Rate']={*$UsersRate*};
	?>
	<!--/op:postload-->
	<!--op:each="$Items"-->
		<H2>{$Title}</H2>
		Рейтинг: <!--op:postload--><?=$Me->getInfo('Rate',{*$id*},'Rate');?><!--/op:postload-->
	<!--/op:each-->
 * 
 */
class dbselect_store extends DataStore {
	/**
	 * Выборка
	 *
	 * @var dbSelect
	 */
	var $sel;
	/**
	 * Создать
	 *
	 * @param dbselect $sel
	 */
	function __construct($sel){
		 $this->sel=$sel;
		 parent::__construct(null);
	}
	/**
	 * Дополнительное условие
	 *
	 * @param string $Field	поле
	 * @param string $Type	тип
	 * @param mixed  $Value	значение
	 */
	function Where($Field, $Type, $Value){
		$this->sel->Where($Field, $Type, $Value);
	}
	function __getInfo($path){
		if (!$this->Info) $this->loadInfo();
		parent::_getInfo($path);
	}
	
	protected function loadInfo(){
		$this->Info=array();
		foreach ($this->sel as $id=>$val) {
			$this->Info[$id]=$val;
		}		
	}
}
?>