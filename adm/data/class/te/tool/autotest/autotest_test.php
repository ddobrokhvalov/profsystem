<?PHP
/**
* Абстрактный класс тестов автотеста
* @package    RBC_Contents_5_0
* @subpackage te
* @author Alexandr Vladykin <avladykin@rbc.ru>
* @copyright  Copyright (c) 2007 RBC SOFT 
*/

abstract class autotest_test  extends autotest_item {

	/**
	* @var array $report - в данный массив хешей модули автотеста записывают информацию о результатах
	*      тестирования, а также вывод вариантов исправления. Выводится в виде таблицы.
	*				Возможные ключи:
	*					is_new_table - начинать ли новую таблицу с данной строки
	*					caption - название таблицы
	*					text_before - текст, который необходимо вывести перед таблицей
	*					text_after - текст, который необходимо вывести после таблицы
	*					add_print_titles - массив названий полей, которые необходимо также напечатать
  *
	*					descr - описание строки
	*					status - статус строки (если 1, то выводится красным)
	*					fix_link - ссылка на исправление данной строки
	*					link_descr - описание исправления
	*					add_print_fields - массив полей, которые необходимо также напечатать
	*												
	*/
	protected $report=array();
	
	/**
	* Выводить ли общую ссылку на исправление, переопределяется в дочерних модулях
	* @var boolean
	*/
	protected $is_for_global_fix=false;
	
	/**
	* Нужно ли подтверждение для общего исправления, сюда же записывается сообщение
	* @var mixed
	*/
	
	protected $global_fix_confirm = null;
	
	/**
	* Объект URL
	*/
	protected $url;
	
	/**
	* Хэш всех тестовых итемов с ключом по SYSTEM_NAME
	* @array
	*/
	private static $test_by_class=array();

	
	/**
	* Фабричный метод. По элементу возвращает необходимый дочерний объект теста
	* @param $element xml-элемент итема
	* @returns объект теста или false в случае отсутствия
	*/
	public static function get_autotest_test($element) {
		if (!($module_name=$element['attributes']['SYSTEM_NAME'])) 
			return false;
			
		$module_file =	params::$params["adm_data_server"]["value"]."class/te/tool/autotest/module/{$module_name}/{$module_name}.php";

		if (!file_exists($module_file)) 
			return false;
		
		include_once $module_file;
		$ret = new $module_name ($element);
		if (is_subclass_of($ret, 'autotest_test')) {
			self::$test_by_class[$ret->get_property('SYSTEM_NAME')]=$ret;
			return $ret;
		}
		return false;
	}
	
	/**
	* Возвращает необходимый объект автотеста по SYSTEM_NAME.
	* Если такого объекта пока что нет, возвращается false
	* @param string system_name - название модуля класса
	* @returns объект теста или false в случае отсутствия
	*/
	public static function get_autotest_by_class ($system_name) {
		if ($system_name && array_key_exists($system_name, self::$test_by_class)) 
			return self::$test_by_class[$system_name];
		return false;
	}
	
	/**
	* Конструктор, вызывается только при помощи фабрик классов
	* @param $element xml-элемент итема
	*/
	protected function __construct ($element) {
		parent::__construct($element);
		$this->template_path=params::$params["adm_data_server"]["value"]."tpl/te/autotest/";
		$this->url=new url('AUTOTEST', '', '', '', '', '');
	}
	

	/**
	* Вывод HTML-представления теста в изначальной таблице.
	*/
	public function get_HTML() {
		$template=$this->template_path.'item.tpl';
		$this->new_smarty();
		$this->get_smarty()->assign('test_title', $this->get_property('TITLE'));
		$this->get_smarty()->assign('test_desc', $this->get_property('DESC'));
		$this->get_smarty()->assign('test_class', $this->get_property('SYSTEM_NAME'));
		return $this->get_smarty()->fetch($template);
	}

	/**
	* возвращает информацию есть ли глобальное исправление для данного модуля
	*/
	protected function is_for_global_fix() {
		return $this->is_for_global_fix;
	}	


	/**
	* Осуществляет проверку, переопределяется
	* необходимо сформировать массив report
	* @see report
	* @returns Выводим на экран
	*/
	abstract function do_test();								
	
	/**
	* варианты исправления, переопределяется
	* необходимо сформировать массив report
	* @see report
	* @returns string выводим на экран
	*/
	protected function fix_index() {}
	
	/**
	* исправление, переопределяется, 
	* @returns string результат исправления
	*/
	protected function fix_action() {}

	/**
	* Возвращает human readable отчет о выполнении теста на основе хэша $report
	*/
	public function show_report() {
		global $bench;
		$this->report=array();
		$this->new_smarty();
		$this->get_smarty()->assign('test_name', $this->get_property('TITLE'));
		$template=$this->template_path.'report.tpl';

		$res=$this->do_test();
		$bench->register(bench::bencher('all_parts'), "test done");		
		if (sizeof($this->report)) {
			$prepared_reports=$this->prepare_report('fix_index');
			$bench->register(bench::bencher('all_parts'), "report prepared");					
			$report_html=$this->get_report_html($prepared_reports);
			$bench->register(bench::bencher('all_parts'), "report html done");

			if ($this->is_for_global_fix()) {
				if ($this->global_fix_confirm) {
					$this->get_smarty()->assign('global_fix_confirm', $this->global_fix_confirm);
					$this->get_smarty()->assign('global_fix_link', $this->url->get_url('fix_action', array('save_params'=>1)).'&SYSTEM_NAME='.$this->get_property('SYSTEM_NAME'));
				}
				else {
					$this->get_smarty()->assign('global_fix_link', $this->url->get_url('fix_index', array('save_params'=>1)).'&SYSTEM_NAME='.$this->get_property('SYSTEM_NAME'));
				}
			}
			
			$this->get_smarty()->assign('report_html', $report_html);
		}
		$res.=$this->get_smarty()->fetch($template);
		
		return $res;
	}
	
	/**
	* Выводит на экран human readable отчет на основе хэша $report о возможностях исправления
	*/

	public function show_fix_index () {
		$this->report=array();
		return $this->fix_index();
	}
	
	/**
	* Выводит на экран отчет об исправлении
	*/
	public function show_fix_action () {
		return $this->fix_action();
	}


	/**
	* Готовит массив report к выводу в табличной форме
	* формирует правильные ссылки
	* @var $action - текущая action
	* @returns array массив готовых текущих отчетов
	*/
	
	private function prepare_report ($action) {
		$reports=$this->split_report();
		if (!$link_text) 
			$link_text=metadata::$lang['lang_autotest_fix'];
		
		if (sizeof($reports)) {
			for ($i=0, $n=sizeof($reports); $i<$n; ++$i) {
				for ($j=0, $m=sizeof($reports[$i]['contents']); $j<$m; ++$j) {
					// чтобы долго не писать
					$cur_el = &$reports[$i]['contents'][$j];
					
					// в зависимости от статуса указываем цвет текста
					if ($cur_el['status']==1) {
						$cur_el['descr']='<span class="at-color1">'.$cur_el['descr'];
					}
					elseif ($cur_el['status']) {
						$cur_el['descr']='<span class="at-color2">'.$cur_el['descr'];
					}
					else {
						$cur_el['descr']='<span class="at-color3">'.$cur_el['descr'];
					}
					$cur_el['descr'].='</span>';
					
					// формируем ссылку на исправление
					if ($cur_el['fix_link'] || $cur_el['link_descr']) {
						$link_html=$cur_el['link_descr']?$cur_el['link_descr']:$link_text;
						$el_action = $cur_el['action']?$cur_el['action']:$action;
						$on_click='';
						if ($cur_el['confirm_message']) {
							$on_click="onClick='return confirm(\"{$cur_el['confirm_message']}\")'";
						}
						elseif ($cur_el['alert_message']) {
							$on_click="onClick='alert('{$cur_el['alert_message']}')';return false";
						}
						$cur_el['fix_link']='<A '.$on_click.' HREF="'.$this->url->get_url($el_action, array('save_params'=>1)).'&SYSTEM_NAME='.$this->get_property('SYSTEM_NAME').'&'.$cur_el['fix_link'].'">'.$link_html.'</a>';
					}
					
					// дополнительные поля добавляем в корень report
					if (sizeof($cur_el['add_print_fields']))
						$cur_el=array_merge($cur_el, $cur_el['add_print_fields']);
				}
			}
		}
		return $reports;
	}


	/**
	* Делит общий отчет $report на несколько отчетов в зависимости от кол-ва таблиц, необходимых для вывода
	* @var $report массив отчета, можно не указывать, будет использовать свойство report
	* @returns array - массив отчета, разбитый на несколько  если встречались в нем ключи is_new_table
	*/
	
	private function split_report($report='') {
		if (!$report) 
			$report = $this->report;

		$res=array(); $res_i=-1; 
				
		for ($i=0, $n=sizeof($report); $i<$n; ++$i) {
			if (($res_i<0) || $report[$i]['is_new_table']) {
				++$res_i;
				$has_actions=false;
				if ($report[$i] && ($report[$i]['fix_link'] || $report[$i]['link_descr'])) 
					$has_actions=true;
				$res[$res_i]['header']=$this->get_report_header($report[$i], $has_actions);
				$res[$res_i]['caption']=$report[$i]['caption'];
				$res[$res_i]['text_before']=$report[$i]['text_before'];
				$res[$res_i]['text_after']=$report[$i]['text_after'];
			}

			$res[$res_i]['contents'][]=$report[$i];
		}
		return $res;
	}

	/**
	* возвращает массив header, пригодный для подстановки в template html_table.tpl
	* по первой строке таблицы, записанной в report
	*/
	private function get_report_header ($report_head_row, $has_actions) {
		$ret=array();
		if ($report_head_row['add_print_fields'])
			foreach (array_keys($report_head_row['add_print_fields']) as $key) {
				if (is_array($report_head_row['add_print_titles'][$key])) 
					$ret[$key]=$report_head_row['add_print_titles'][$key];
				else 
					$ret[$key]=array('title'=>$report_head_row['add_print_titles'][$key]);
			}

		$ret = array_merge($ret, array('descr'=>array('title'=>metadata::$lang['lang_autotest_messages'])));
		if ($has_actions) 
			$ret = array_merge($ret, array('fix_link'=>array('title'=>metadata::$lang['lang_autotest_actions'])));
		return $ret;
	}
	


	/**
	* возвращает на основе передаваемого отчета результат в html-форме
	* @var array $prepared_reports - массив report обработанный ф-ией prepare_report
	* @returns string html-представление отчета в табличной форме
	*/
	private function get_report_html($prepared_reports) {
			$template=params::$params["adm_data_server"]["value"]."tpl/te/autotest/report_table.tpl";
			for ($i=0, $n=sizeof($prepared_reports); $i<$n; ++$i) {
				$this->get_smarty()->assign('header', $prepared_reports[$i]['header']);
				$this->get_smarty()->assign('table_caption', $prepared_reports[$i]['caption']);
				$this->get_smarty()->assign('text_before', $prepared_reports[$i]['text_before']);
				$this->get_smarty()->assign('text_after', $prepared_reports[$i]['text_after'].$prepared_reports[$n-1]['text_after']);
				$this->get_smarty()->assign('list', $prepared_reports[$i]['contents']);
				$res.=$this->get_smarty()->fetch($template);
				$this->get_smarty()->clear_assign('table_caption', 'text_before', 'text_after', 'list');
			}
			return $res;
	}
	
	/**
	 * Проверяет наличие в системе модуля с переданным именем
	 *
	 * @var string $system_name		системное имя модуля
	 * @returns boolean
	 */
	public function is_module_exists( $system_name )
	{
		return count( db::sql_select( 'select * from PRG_MODULE where lower( SYSTEM_NAME ) = lower( :system_name )', array( 'system_name' => $system_name ) ) ) > 0;
	}
}
?>
