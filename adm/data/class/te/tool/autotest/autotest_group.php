<?PHP
/**
* Групповой класс итемов автотеста
* @package    RBC_Contents_5_0
* @subpackage te
* @author Alexandr Vladykin <avladykin@rbc.ru>
* @copyright  Copyright (c) 2007 RBC SOFT 
*/

class autotest_group extends autotest_item {
	
	const TEMPLATE = 'group.tpl';

	private $template_path;
	
	/**
	* Массив дочерних итемов группы
	* @var array()
	*/
	private $children = array();

	/**
	* Конструктор, вызывается при помощи фабрики классов autotest_item
	* @param $element - xml итема
	*/
	protected function __construct ($element) {
		parent::__construct($element);
		
		$this->template_path=params::$params["adm_data_server"]["value"]."tpl/te/autotest/".self::TEMPLATE;
		
		if ($this->element['children'])
			foreach ($this->element['children'] as $el)
				$this->add($el);
	}
	
	/**
	* Метод, позволяющий добавить новый элемент как дочерний для данной группы
	* @param $element - xml итема
	*/
	private function add($element) {
		$item=$this->get_autotest_item($element);
		if ($item)
			$this->children[]=$item;
	}
	
	/**
	* Вывод HTML-представления группы.
	*/
	public function get_HTML() {
		$items_html='';
		foreach ($this->children as $el) 
			$items_html.=$el->get_HTML();
			
		$this->get_smarty()->assign('group_title', $this->get_property('TITLE'));
		$this->get_smarty()->assign('group_desc', $this->get_property('DESC'));
		
		$this->get_smarty()->assign('items', $items_html);
		return $this->get_smarty()->fetch($this->template_path);
	}
}
?>