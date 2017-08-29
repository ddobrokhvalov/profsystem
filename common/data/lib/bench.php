<?php
/**
 * Класс для измерения времени работы кода
 *
 * Содержит статический метод {@bencher()} для подсчета интервалов времени. Также класс может инстанцироваться для сбора информации об
 * интервалах времени и вывода их в виде красивой таблички с подсветкой самых продолжительных интервалов.
 *
 * @package    RBC_Contents_5_0
 * @subpackage lib
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class bench{

	/**
	 * Хранилище интервалов времени и сообщений о них - array(array("interval"=>.01, "message"=>"Some message", "summarized"=true))
	 * @var array
	 */
	 private $report=array();

	/**
	 * Сумма интервалов
	 * @var array
	 */
	 private $sum=0;

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Функция для подсчета времени работы кода
	 *
	 * Возвращает разность времени в секундах между предыдущим отсчетом и текущим моментом времени и устанавливает отсчет переданной метки на текущее время.
	 * Для удобства использования в bencher() применяется статическая переменная, поэтому нужно соблюдать осторожность - чтобы параллельное использование не ломало чужие отсчеты времени
	 *
	 * @param string $label			метка отсчета
	 * @param string $message		сообщение; если передано, то функция выведет это сообщение и время работы
	 * @param int $precision		точность в знаках после запятой
	 * @param boolean $move_label	передвигать метку на текущее время (если даже $move_label==false, но метки нет, то она будет выставлена на текущее время)
	 * @return float
	 */
	public static function bencher($label, $message="", $precision=4, $move_label=true){
		static $time_by_label;
		$cur_time=array_sum(explode(" ", microtime()));
		$output=sprintf("%1.{$precision}F", $cur_time-$time_by_label[$label]);

		if(!$time_by_label[$label] || $move_label){
			$time_by_label[$label]=$cur_time;
		}
		if($message){
			echo $message.": ".$output."<br>\n";
		}
		return $output;
	}

	/**
	 * Выводит стек вызовов до текущего метода
	 *
	 * Метод снабжен отрезанием вызовов __call и call_user_func_array для более симпатичной работы с декораторами. Себя также не выводит
	 * @todo наверно стоит ХТМЛ-код вынести в шаблон
	 */
	public static function backtrace(){
		$debug=self::filter_trace(self::get_full_trace());
		
		echo "<table border='1'><tr bgcolor='#cccccc'><td>File, that asks</td><td>class</td><td>for method</td><td>on line</td></tr>";
		foreach($debug as $item)
			echo "<tr><td>{$item["file"]}</td><td>{$item["class"]}</td><td>{$item["function"]}</td><td>{$item["line"]}</td></tr>";
		echo "</table>";
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Регистрация интервала времени
	 *
	 * @param string $message		сообщение
	 * @param float $interval		интервал времени
	 * @param boolean $summarize	суммировать этот интервал (false, если нужно в отчет включить какие-нибудь подитоги, компоненты которых уже суммировались)
	 */
	public function register($interval, $message){
		$interval = strtr($interval, ',', '.');
		$this->report[]=array("interval"=>$interval, "message"=>$message);
		$this->sum+=$interval;

	}

	/**
	 * Печать отчета в виде таблички (по умолчанию горизонтальной)
	 *
	 * @param boolean $is_vertical		печатать вертикальную табличку
	 * @param boolean $dot_to_comma		конвертация точек в запятые для удобства использования в русифицированном Экселе
	 * @todo решить, нужно ли здесь применять языковые константы в шаблоне. Если да, то внедрить их
	 * @todo перенести шаблоны в common
	 */
	public function echo_report($is_vertical=false, $dot_to_comma=false){
		foreach($this->report as &$report_item){
			$report_item["percent"]=round($report_item["interval"]/$this->sum*100, 2);
			$not_red=255-$report_item["interval"]/$this->sum*150;
			$report_item["color"]=sprintf("#ff%2x%2x", $not_red, $not_red);
		}

		$tpl=new smarty_ee();
		$tpl->assign("report", $this->report);
		$tpl->assign("sum", $this->sum);
		echo $tpl->fetch(params::$params["adm_data_server"]["value"]."tpl/lib/bench/report_".($is_vertical ? "v" : "h").".tpl");
	}
	

	/**
	* Ф-ия возвращает массив с бектрейсом
	* @param int $start кол-во начальных записей, которые необходимо пропустить в результате
	* @return array
	*/
	public static function get_full_trace ($start=1) {
		return array_slice(debug_backtrace(), $start);
	}
	
	/**
	 * Получает трейс текущее положение в коде
	 *
	 * @param int $start кол-во позиций, которые необходимо пропустить
	 * @return string
	*/
	public static function get_trace_as_string ($trace) {
		$ret='';
		
		$j=0;
		
		for($i = 0, $n=count($trace); $i < $n; $i++) {
			$method=$trace[$i]['function'];
			if ($trace[$i]['class']) {
				$method = $trace[$i]['class'].$trace[$i]['type'].$method;
			}
			
			$args=array();
			foreach ($trace[$i]['args'] as $arg) {
				$a = gettype($arg);
				if (is_object($arg)) {
					$a .= '('.get_class($arg).')';
				}
				elseif (!is_array($arg) && strlen($arg)<255 && isset($arg)) {
					$a="'".$arg."'";
				}
				$args[]=$a;
			}
			
			$fileline=$trace[$i]['file'].'('.$trace[$i]['line'].')';
			
			if ($fileline=='()') {
				$fileline = '[internal function]';
			}
			
			$args=implode(", ", $args);
			
			$ret.='#'.$j++.' '.$fileline.': '.$method.'('.$args.')'."\n";
		}
		
		return $ret;
	}
	
	/**
	* ф-ия фильтрации бектрейса
	* @param array $trace массив бектрейса
	* @return array
	*/
	public static function filter_trace ($trace) {
		return array_values(array_filter($trace, array(self, 'callback_filter_trace')));
	}
	
	/**
	* Ф-ия обратного вызова фильтрации бектрейса
	* @param array $item - элемент массива бектрейса
	* @return boolean
	*/
	public static function callback_filter_trace ($item) {
		return 
			!empty($item['function']) 
				&& 
					!in_array($item['function'], array('__call', 'call_user_func_array', 'backtrace', 'call_parent'));
	}
}
?>