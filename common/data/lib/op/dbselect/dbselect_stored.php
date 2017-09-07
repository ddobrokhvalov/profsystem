<?php
	/**
	 * "Кэширующая выборка"
	 * e.c. у нас есть некоторая функция getFieldValuе, 
	 * которая совершает какие-либо хитроумные (долгие) действия
	 * 
	 * Например нам надо выводить в превью галлереи последнюю загруженную картинку
	 * Делаем таблицу: gallery_pics
			CREATE TABLE `gallery_pics` (
			`id` INT UNSIGNED NOT NULL ,
			`Data` BLOB NOT NULL ,
			PRIMARY KEY ( `id` )
			);
	 *
	 * Картинки для галлерей у нас (предположим) храняться в Images:	 
	 		CREATE TABLE `images` (
			`id` INT( 20 ) UNSIGNED NOT NULL ,
			`Title` VARCHAR( 100 ) NOT NULL ,
			`Width` TINYINT UNSIGNED NOT NULL ,
			`Height` TINYINT UNSIGNED NOT NULL ,
			`GalleryID` INT UNSIGNED NOT NULL ,
			`ts` INT UNSIGNED NOT NULL
			);
	 *
	 * Таким образом, для вывода 10 посл. галлерей с превьюшками делаем так:

			$gal= new dbselect(array('id','Title','ts'));
			$gal->OrderBy('ts','DESC');
			$gal->Limit(0,10);
			//Картинки:
			$pics=new dbselect_stored('gallery_pics');
			//Одно самое последнее изображение:
			$img=new dbselect(array('id','Title','Width','Height','ts'));
			$img->OrderBy('ts','DESC');
			$img->Limit(0,1);
			//Добавляем:
			$pics->setSelectOnNotExists($img, 'GalleryID', dbselect_stored::SelectObject);
			//Прикрепляем:
			$gal->Join($pics, 'id','id','Pic','LEFT');

	 *
	 * Но это еще не все (c): вы можете перегрузить ф-ю getFieldValue и делать все что вашей душе угодно
	 * 
	 * It’s Not a Silver Bullet: не забывайте что проверять актуальность кэша и очищать его вам надо самостоятельно
	 * 
	 * Ahtung: 
	 * К этой таблице ничего нельзя приджойнить, это можно исправить, но я не буду, так как это неверно идеологически
	 *  
	 */
	class dbselect_stored extends dbselect {
		/**
		 * Конструктор
		 *
		 * @param string $Table			таблица для "кэша"
		 * @param string $DataField		поле, в котором лежит "кэш"
		 * @param string $PrimaryKey	первичный ключ
		 * @param boolean $Serialize	сериализованные данные (по умолчанию- да)
		 */
		function __construct($Table, $DataField='Data', $PrimaryKey='id', $Serialize=true){
			parent::__construct(array($PrimaryKey,$DataField),$Table,($Serialize)?array($DataField):null);
		}
		
		const SelectObject=1;
		const SelectEach=2;
		/**
		 * @var dbselect	выборка
		 */
		var $OnNotExistsSelect=null;
		/**
		 * @var string		выборка		 
		 */
		var $OnNotExistsKey=null;
		/**
		 * Метод:
		 *
		 * @var string
		 */
		var $OnNotExistsMethod=null;		
		/**
		 * Выставить селект на "не найдено"
		 *
		 * @param dbselect $sel		выборка
		 * @param string   $key		ключ по которому идет поиск
		 * @param string   $method	метод 
		 * 							dbselect_stored::SelectObject - выбрать одну запись
		 * 							dbselect_stored::SelectEach   - выбрать все
		 * 							* - определяете у себя
		 */
		function setSelectOnNotExists($sel, $key, $method=null){
			$this->OnNotExistsSelect=$sel;
			$this->OnNotExistsKey=$key;
			$this->OnNotExistsMethod=$method;
		}
		/**
		 * Разобрать результат
		 * Запись в таблице де факто является "прозрачным" кэшем, т.е. если запись по ключу $key
		 * отсутсвует -> 
		 * 1. вызываем метод getFieldValue получаем данные
		 * 2. сохраняем данные в кэширующей таблице по ключу $key
		 * 3. возвращаем запись		 		
		 *
		 * @param array $row
		 * @param int $Offset
		 * @param string $key
		 * @return array
		 */
		function AssocResult($row,&$Offset,$key=null){			
			if ($row[$Offset]) return parent::AssocResult($row,$Offset,$key);//Все пучком: поле есть
			//Ничего не найдено:
			$ret=array($this->primary_key=>$key, $this->Select[1]=>$this->getFieldValue($key));
			$this->Insert($ret);
			$Offset+=2;
			return $ret;
		}
		/**
		 * Получить значение по ключу:
		 * Ahtung! Эту функцию можно перегрузить, но не забывайте что объект сохраняется между вызовами
		 * 
		 * @var 	string $key
		 * @return  mixed	значение поля
		 */
		function getFieldValue($key){
			if ($this->OnNotExistsSelect){
				$sel=$this->OnNotExistsSelect;
				$sel->Where($this->OnNotExistsKey, eq, $key);
				$sel->SelectResult();
				switch($this->OnNotExistsMethod){
					case self::SelectObject:
						return $sel->SelectObject();
					break;
					case self::SelectEach:	
						$ret=array();			
						while($obj=$sel->Next()){
							$ret[]=$obj;
						}
						return $ret;
					break;
				}
			}
			else return null;
		}
	}
?>