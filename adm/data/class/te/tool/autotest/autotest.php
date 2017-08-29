<?PHP

include_once(params::$params["adm_data_server"]["value"]."class/te/tool/autotest/autotest_item.php");

/**
 * Утилита-автотест проверки исходников системы на корректность
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @todo Сделать возможность запуска распределенной сессии автотеста
 * @todo Что-то сделать с iframe-ами (дизайн хотя бы)
 * @copyright  Copyright (c) 2006-2007 RBC SOFT
 */

class autotest extends tool {
	
	const PATH_TO_XML_FILE = 'autotest.xml';
	const TEMPLATE_PATH = 'te/autotest/';
	const MAIN_TEMPLATE = 'autotest.tpl';
	
	private $tests=array();
	
	/**
	* конструктор, помимо прочего парсит xml-Файл
	 * @param string $obj			Название конструируемого объекта
	 * @param string $full_object	Ссылка на полный объект, если ее нет, то в качестве такой ссылки используется сам конструируемый объект 
	*/
	function __construct($obj, $full_object=""){
		parent::__construct($obj, $full_object);
		$this->title = metadata::$objects[$this -> obj]['title'];
		$this->set_tests_from_xml();
	}
	
	/**
	* Функция парсинга xml-файла
	 * @param string $xml_file путь к xml-файлу. Если не задан, берется из константы PATH_TO_XML_FILE
	*/
	private function set_tests_from_xml($xml_file='') {
		if (!$xml_file) $xml_file=self::PATH_TO_XML_FILE;
		$xml_file = dirname(__FILE__).'/'.$xml_file;
		
		
		$dom = new ExpatXMLParser();
		$autotest = $dom->Parse($xml_file,_LOAD_FROM_FILE);
		$dom->Free();
		
		foreach ($autotest[0]['children'] as $root_els) {
			$item=autotest_item::get_autotest_item($root_els);
			if ($item) {
				$this->tests[] = $item;
			}
		}
	}


	/**
	* Страница по умолчанию
	*/
	protected function action_index() {
		$this->body = $this->main_HTML();
	}
	
	/**
	* Выводит список доступных автотестов
	*/
	private function main_HTML() {
		$items_html='';
		
		foreach ($this->tests as $item) 
			$items_html.=$item->get_HTML();
		
		$tpl=new smarty_ee(metadata::$lang);
		$tpl->assign("items", $items_html);
		
		return $tpl->fetch($this->get_main_template_path(self::MAIN_TEMPLATE));
	}


	/**
	* выдает путь к основному шаблону
	*/
	private function get_main_template_path($filename) {
		return $this->tpl_dir.self::TEMPLATE_PATH.$filename;
	}

	/**
	* запускает тест, передаются параметры SYSTEM_NAME's классов через $_REQUEST['tc']
	*/
	protected function action_run() {
		if (!sizeof($_REQUEST['tc'])) 
			$this->url->redirect();
		foreach ($_REQUEST['tc'] as $class_name) {
			if ($test_class=autotest_test::get_autotest_by_class($class_name)) {
				//$test_class->do_test();
				$this->body .= $test_class->show_report();
				$test_class->__destruct();
			}
		}
	}
	
	/**
	* запускает страницу выбора опций автоисправления
	*/
	protected function action_fix_index() {
		if (!$test_class = autotest_test::get_autotest_by_class($_REQUEST['SYSTEM_NAME'])) {
			$this->url->redirect();
		}
		$this->body = $test_class->show_fix_index();
	}
	
	/**
	* запускает автоисправление
	*/
	protected function action_fix_action() {
		if (!$test_class = autotest_test::get_autotest_by_class($_REQUEST['SYSTEM_NAME'])) {
			$this->url->redirect();
		}
		$tpl = new smarty_ee( metadata::$lang );
		$tpl->assign('msg', $test_class->show_fix_action());
		$tpl->assign('back_url', $this->url->get_url('', array('clear_prev_params'=>1, 'no_from'=>1)));
		$info_block = $tpl->fetch($this->tpl_dir."core/object/html_warning.tpl");
		$this->body=$info_block;
	}
}
?>
