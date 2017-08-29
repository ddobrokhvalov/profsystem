<?PHP
include_once(params::$params["adm_data_server"]["value"]."class/te/tool/autotest/autotest_group.php");
include_once(params::$params["adm_data_server"]["value"]."class/te/tool/autotest/autotest_test.php");

/**
* Корневой класс итемов автотеста
* @package    RBC_Contents_5_0
* @subpackage te
* @author Alexandr Vladykin <avladykin@rbc.ru>
* @copyright  Copyright (c) 2007 RBC SOFT 
*/

abstract class autotest_item {
	/**
	* Элемент XML итема
	* @var array()
	*/
	protected $element;
	
	/**
	* Объект smarty
	* @var object
	*/
	private $smarty=null;
	
	/**
	* Фабрика классов. Возвращает объект необходимого класса по xml-элементу $element
	* @param $element Элемент XML итема
	*/
	public static function get_autotest_item($element) {
		switch ($element['tag']) {
			case 'GROUP' :
				return new autotest_group($element);
			case 'TEST' :
				return autotest_test::get_autotest_test($element);
		}
		return false;
	}
	
	/**
	* Конструктор, напрямую не вызывается, только из дочерних классов
	*/
	protected function __construct($element) {
		$this->element=$element;
	}

	/**
	* Создает новый объект smarty (или очищает старый и возвращает его)
	* @returns object новый объект smarty
	*/
	protected function new_smarty() {
		if (!$this->smarty) {
			$this->smarty = new smarty_ee(metadata::$lang);
		}
		else {
			$this->smarty->clear_all_assign();
		}
		return $this->smarty;		
	}
	
	/**
	* Возвращает текущий объект smarty. Если такового нет, создает новый
	* @returns object текущий объект smarty
	*/
	protected function get_smarty() {
		return ($this->smarty)?$this->smarty:$this->new_smarty();
	}
	
	protected function unset_smarty() {
		unset ($this->smarty);
	}
	
	
	public function __destruct() {
		$this->unset_smarty();
		unset($this->element);
		unset($this);
	}
	
	/**
	* Возвращает свойство объекта 
	* @param string property_name название свойства объекта
	* @returns string значение свойства объекта
	*/
	public function get_property($property_name) {
		$ret=$this->element['attributes'][$property_name];
		if (preg_match('/^lang_(.*)$/', $ret, $m)) {
			if (metadata::$lang[$m[0]])
				return metadata::$lang[$m[0]];
			return $m[1];
		}
		return $this->element['attributes'][$property_name];
	}

	/**
	* Возвращает HTML-преставление для главной страницы объекта
	* Прописывается в дочерних классах
	*/
	abstract function get_HTML();	
}
?>