<?php
/**
 * Класс, содержащий статические методы, возвращающие различные html-элементы для построения интерфейсов: формы, таблицы и т.д.
 *
 * @package    RBC_Contents_5_0
 * @subpackage core
 * @copyright  Copyright (c) 2006 RBC SOFT
 * @todo Сейчас отступы для иерархии в таблице собираются в самом шаблоне. Может быть лучше отступы не в шаблоне делать?
 */
class html_element{

	/**
	 * Счетчик форм
	 * 
	 * Используется для именования форм (чтобы js-скрипты могли правильно к ним обращаться).
	 * Название всех форм системы формируется как "form_<$form_counter>"
	 *
	 * ВНИМАНИЕ: Если начать собирать несколько форм одновременно, то их названия будут путаться - нужно соблюдать осторожность
	 *
	 * @var int
	 * @todo Конечно, хорошо бы сделать формы объектом, но там тоже возникают некоторые сомнения, поэтому пока делаем статическими методами.
	 */
	protected static $form_counter=0;

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Возвращает html-код произвольного списка записей, сформированного из переданных параметров.
	 *
	 * Причем в списке выводятся только те колонки, ключи которых есть среди ключей заголовков (отсечение реализовано в шаблоне)
	 *
 	 * Возможные компоненты описания списка записей:
 	 *     header		заголовок таблицы
 	 *     list			список записей
 	 *     counter		число записей
 	 *     html_hidden	набор скрытых полей
 	 *     form_name	имя формы
	 *
	 * @param array $vars					массив с компонентами описания списка записей
	 * @param string $template				шаблон
	 * @param object field $field_object	объект работы с полями
	 * @return string
	 * 	 
	 * @todo При большом числе записей метод приводит к заметным тормозам на стадии "table done" (bugs 225, 229). Причина в медленной работе метода fetch с большими объемами данных. Скорее всего с этим ничего не поделаешь.
	 */
	public static function html_table($vars, $template, $field_object=""){
		$tpl=new smarty_ee(metadata::$lang);
		// Выставляем принудительный эскейпинг, если он не был запрещен явно
		if(is_array($vars["header"])){
			foreach($vars["header"] as $k=>$header){
				if($header["escape"]!==0 && $header["escape"]!==false){
					$vars["header"][$k]["escape"]=1;
				}
			}
		}
		// Выполняем предобработку полей
		if(is_object($field_object)){
			foreach($vars["list"] as $num=>$record){
				foreach($record as $key=>$value){
					$method="index_".$vars["header"][$key]["type"];
					$vars["list"][$num][$key]=$field_object->$method($vars["list"][$num][$key], $vars["header"][$key]["view_type"]);
					
					// Если требуется, обрезаем контент текстовых полей до максимально допустимой длины
					if ( ( $vars['header'][$key]['type'] == 'text' || $vars['header'][$key]['type'] == 'textarea' ) &&
							$vars['header'][$key]['length'] > 0 && mb_strlen( $vars['list'][$num][$key], params::$params["encoding"]["value"] ) > $vars['header'][$key]['length'] )
						$vars['list'][$num][$key] = mb_substr( $vars['list'][$num][$key], 0, $vars['header'][$key]['length'], params::$params["encoding"]["value"] ) . '...';
				}
			}
		}
		$tpl->assign($vars);
		return $tpl->fetch($template);
	}

	/**
	 * Возвращает html-код для набора кнопок с операциями над записями
	 *
 	 * Возможные компоненты набора операций:
 	 *     operations	массив с операциями
 	 *     label		текстовая метка, отличающая списки операции, выведенные в разных частях страницы
	 *
	 * @param array $vars				массив с операциями
	 * @param string $template			шаблон
	 * @return string
	 */
	public static function html_operations($vars, $template){
		$tpl=new smarty_ee(metadata::$lang);
		$tpl->assign($vars);
		return $tpl->fetch($template);
	}

	/**
	 * Формирует html-код фильтра поиска по записям
	 * 
	 * @param object $object		объект, к которому применяется фильтр
	 * @param boolean $is_short		строить фильтр для сокращенного списка записей
	 * @param boolean $is_tree		строить фильтр для сокращенного списка записей в дереве
	 * @param object $exec_object	объект, обрабатываемый в настоящий момент. Если не задан, то равен $object
	 */
	public static function html_filter( $object, $is_short = false, $is_tree = false, $exec_object = null, $no_search_text=false )
	{
		if (!$exec_object)
			$exec_object = $object;
			
		html_element::get_next_form_name();
		$fields=$object->get_form_fields(($is_short ? "filter_short" : "filter") , "_f_", $_REQUEST, "_f_");
		
		// Для иерархических таблиц добавляем в фильтр флаг "Все уровни"
		if ( metadata::$objects[$object -> obj]['parent_field'] && !$is_tree )
			$fields['_ALL_LEVELS'] = array( 'title' => metadata::$lang['lang_all_levels'], 'name' => '_f__ALL_LEVELS',
				'type' => 'checkbox', 'view_type' => 'flag', 'value' => (string) $_REQUEST['_f__ALL_LEVELS'] );
		
		// Помечаем аттрибутом "display" пустые поля, которые должны быть видны в фильтре
		$display_fields = explode(",", $_REQUEST["display_fields"]);
		foreach($fields as $field_name=>$descr){
			if ( in_array( $fields[$field_name]["name"], $display_fields ) || params::$params["filter_expand"]["value"] || count( $fields ) == 1 )
				$fields[$field_name]["display"]=1;
		}
		
		foreach($fields as $field_name=>$descr)
		{
			$method="change_".$descr["type"];
			if (method_exists($object->field, $method))
			{
				if ( is_array( $fields[$field_name]["value"] ) )
					foreach ( $fields[$field_name]["value"] as $key => $value )
						$fields[$field_name]["value"][$key]=$object->field->$method($value, $fields[$field_name]["value_type"]);
				else
					$fields[$field_name]["value"]=$object->field->$method($fields[$field_name]["value"], $fields[$field_name]["value_type"]);
			}
			
			// Помечаем аттрибутом "display" непустые поля
			if ( is_array( $fields[$field_name]["value"] ) && join( "", array_values( $fields[$field_name]["value"] ) ) ||
					!is_array( $fields[$field_name]["value"] ) && $fields[$field_name]["value"] !== "" )
				$fields[$field_name]["display"]=1;
		}
		
		$tpl=new smarty_ee(metadata::$lang);
		
		$tpl->assign("fields", $fields);
		$tpl->assign("search", htmlspecialchars($_REQUEST["search"], ENT_QUOTES));
		$tpl->assign("fields_count", count( $fields ));
		$tpl->assign("form_name", self::get_form_name());
 		$tpl->assign("html_hidden", $exec_object->url->get_hidden("",array("filter"=>1)));
 		$tpl->assign('no_search_text', $no_search_text);
		
		return $tpl->fetch($object->tpl_dir."core/html_element/html_filter.tpl");
	}

	/**
	* Возвращает html-код для списка значений
	*
	* @param array $val_array	Массив списка значений, в формате table::get_form_fields
	* @param string $template	шаблон
	* @param mixed $field_object	объект для работы с полями
	*
	* @return string
	*/
	
	public static function html_value_list($val_array, $template, $field_object='')
	{
		$arr_for_table=array();
		$i=0;
		
		// формируем массив для таблицы с полями key - ключ и value - значение
		foreach ($val_array as $key=>$value) {
			$arr_for_table[$i]['key'] = $value['title'];
			if(is_object($field_object)){
				$method='index_'.$value['type'];
				$value['value']=$field_object->$method($value['value'], $value['view_type']);
			}
			
			// это в случае есть выборка из select1 или select2
			if ($value['value_list']) {
				foreach ($value['value_list'] as $el) {
					if ($el['_VALUE']===$value['value']) {
						$value['value']=$el['_TITLE'];
						break;
					}
				}
			}
			
			$arr_for_table[$i]['value'] = $value['value'];
			++$i;
		}
		
		return self::html_table( array( 'header' => array( 'key' => array( 'title' => metadata::$lang["lang_parameter_title"] ), 'value' => array( 'title' => metadata::$lang["lang_parameter_value"], 'nl2br' => 1 ) ), 'list' => $arr_for_table ), $template, $field_object );
	}
	
	/**
	 * Возвращает html-код произвольного списка полей для форм, сформированного из переданных параметров.
	 *
	 * @param array $record					запись
	 * @param string $template				шаблон
	 * @param object field $field_object	объект работы с полями
	 * @return string
	 */
	public static function html_fields($record, $template, $field_object, $change_fields=true){
		$tpl=new smarty_ee(metadata::$lang);
		if($change_fields){
			foreach($record as $field_name=>$descr){
				$method="change_".$descr["type"];
				$record[$field_name]["value"]=$field_object->$method($record[$field_name]["value"], $record[$field_name]["view_type"]);
			}
		}
		$tpl->assign("record", $record);
		$tpl->assign("form_name", self::get_form_name());
		return $tpl->fetch($template);
	}

	/**
	 * Возвращает html-код произвольной формы, сформированной из переданных параметров.
	 *
	 * @param array $html_fields		массив с полями
	 * @param string $html_hidden		строка с готовыми хидденами
	 * @param string $template			шаблон
	 * @param boolean $no_submit		не отображать кнопку submit
	 * @param string $message			отобразить сообщение перед формой
	 * @return string
	 */
	public static function html_form($html_fields, $html_hidden, $template, $no_submit = false, $message=''){
		$tpl=new smarty_ee(metadata::$lang);
		$tpl->assign("html_fields", $html_fields);
		$tpl->assign("html_hidden", $html_hidden);
		$tpl->assign("form_name", self::get_form_name());
		$tpl->assign("interface_lang", params::$params["default_interface_lang"]["value"]);
		$tpl->assign("no_submit", $no_submit);
		$tpl->assign("message", $message);
		return $tpl->fetch($template);
	}

	/**
	* Формирует и высылает форму методом POST
	* @param string $obj	Название объекта
	* @param array $post	Массив параметров
	* @param string $template 	шаблон
	* @return string
	*/
	public static function html_post_form ($obj, $post=array(), $template) {
		$hidden = "<input type='hidden' name='obj' value='$obj'>";
		foreach ($post as $key => $value) {
			$value=htmlspecialchars($value);
			$hidden .= "<input type='hidden' name='$key' value='$value'>";
		}
		

		$return = self::html_form ('', $hidden, $template, true);

		$submit_script = "<SCRIPT type='text/javascript' languare='javascript'>document.form_0.submit();</SCRIPT>";
		
		return $return.$submit_script;
	}

	/**
	 * Метод формирует общий вид ответа web-сервиса
	 *
	 * @param string $content		содержимое ответа
	 * @param string $mark			метка команды
	 * @return string
	 */
	public static function xml_response( $content = '', $mark = '' )
	{
		$response  = '<?xml version="1.0" encoding="' . params::$params['encoding']['value'] . '"?' . '>';
		$response .= '<response mark="' . $mark . '">' . $content . '</response>';
		
		return $response;
	}

	/**
	 * Возвращает название текущей формы
	 */
	public static function get_form_name(){
		return "form_".self::$form_counter;
	}

	/**
	 * Формирует название следующей формы и возвращает его
	 */
	public static function get_next_form_name(){
		self::$form_counter++;
		return "form_".self::$form_counter;
	}
}
?>
