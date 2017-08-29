<?php
require_once(dirname(__FILE__)."/libs/Smarty.class.php");
/**
 * Класс-обертка для Смарти
 *
 * Настраивает в конструкторе параметры шаблонизатора и дополняет смарти нужными для RBC Contents функциями,
 * в том числе возможностью получать шаблон не с файловой системы, а из переменной (эта возможность не доделана)
 *
 * @package    RBC_Contents_5_0
 * @subpackage lib
 * @copyright  Copyright (c) 2006 RBC SOFT
 * @todo templates_c нужно вынести в корень common/data/ чтобы не нужно было все время прописывать ее в исключениях разных автотестов
 */
class smarty_ee extends Smarty {

	/**
	 * Свойство, в котором можно сохранять шаблон, для использования напрямую, а не с файловой системы
	 * @var string
	 */
	public $var_template;
	
	/**
	 * Свойство, в котором сохраняется массив системных слов
	 * @var array
	 */
	public static $lang = array();

	/**
	 * Системное название языка, используется для разделения компилированных шаблонов по языкам
	 * @var array
	 */
	public $current_lang;

	/**
	 * Конструктор класса
     *
	 * @param array $lang			массив системных слов 
	 * @param array $outputfilter	объект и название метода, расширяющего стандартные шаблоны
	 *
	 * Если нужно применять одновременно несколько наборов системных слов, то нужно делать это очень аккуратно, так как использование
	 * вложенного сбора шаблонов будет неправильно изменять smarty_ee::$lang. Такая кривизна из-за того, что в функцию префильтра
	 * передается не сам объект smarty_ee, а Smarty_Compiler, в котором нет свойств smarty_ee
	 */
	public function __construct( $lang = null, $outputfilter = null ) {
		if ( is_array( $lang ) ) self::$lang = $lang;
		$this->current_lang	= params::$params["default_interface_lang"]["value"];
		$this->Smarty();
		
		$this->template_dir = params::$params["common_data_server"]["value"]."lib/smarty/templates/";
		$this->compile_dir  = params::$params["common_data_server"]["value"]."templates_c/";
		$this->config_dir   = params::$params["common_data_server"]["value"]."lib/smarty/configs/";
		$this->cache_dir    = params::$params["common_data_server"]["value"]."lib/smarty/cache/";

		// Подключаем ресурс использования шаблона из переменной
		$this->register_resource("var", array(
			"var_get_template",
			"var_get_timestamp",
			"var_get_secure",
			"var_get_trusted"));
		
		$this->register_prefilter("smarty_prefilter_lang");
		
		if ( $outputfilter )
			$this->register_outputfilter( $outputfilter ); 
	}
	
	/**
     * Переодределяем метод fetch() с целью компиляции несколько различных версий одного шаблона на разных языках
     *
     * @param string $resource_name
     * @param string $cache_id
     * @param string $compile_id
     * @param boolean $display
	 *
	 * @todo Идентификатор текущего языка для админской части должен браться из массива текущих параметров, когда он будет сделан
     */
     
    function fetch($resource_name, $cache_id = null, $compile_id = null, $display = false)
    {
    	return parent::fetch($resource_name, $cache_id, $this->current_lang."|".$compile_id, $display);
    }
    
		/**
		* ф-ия переноса массивов в формат, пригодный для smarty html_options 
		* @param $arr - массив
		* @param $key_name - ключ идентификатора
		* @param $value_name - ключ текста
		* @return array
		*/
		
		public function get_array_for_smarty_html_options($arr, $key_name, $value_name) {
			$ret=array();
			if (sizeof($arr))
				foreach ($arr as $el) 
					if ($el[$key_name]) 
						$ret[$el[$key_name]]=$el[$value_name];
			return $ret;
		}
}

/**
 * Префильтр шаблонизатора
 * Добавляет в шаблон системные слова
 *
 * @param string $tpl_source	шаблон
 * @param smarty_ee $smarty		объект компилятора Smarty
 */
function smarty_prefilter_lang($tpl_source, &$smarty){
	$lang_regexp=array();
	foreach (smarty_ee::$lang as $lang_key => $lang_value ){
		$lang_regexp['/{\$'.$lang_key.'}/isU'] = $lang_value;
	}
	$tpl_source = preg_replace( array_keys($lang_regexp), array_values($lang_regexp), $tpl_source );
    return $tpl_source;
}

// Функции для работы ресурса шаблона из переменной
// Причем параметр $template игнорируется, а шаблон всегда берется из $smarty_obj->var_template
function var_get_template ($template, &$tpl_source, &$smarty_obj){
	$tpl_source = $smarty_obj->var_template;
	return true;
}

function var_get_timestamp($template, &$tpl_timestamp, &$smarty_obj){
	$tpl_timestamp = time();
	return true;
}

function var_get_secure($tpl_name, &$smarty_obj){
    return true;
}

function var_get_trusted($tpl_name, &$smarty_obj){
}


?>