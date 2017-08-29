<?php
/**
 * Класс "Процессор XML"
 *
 * Содержит набор методов для организации процессинга XML.
 * Дочерние классы должны содержать методы с названиями "process_tag_<тэг>" содержащие логику обработки тэгов
 *
 * @package    RBC_Contents_5_0
 * @subpackage lib
 * @copyright  Copyright (c) 2007 RBC SOFT
 */
abstract class xml_processor{

	/**
	 * Путь, где лежит xml-файл (включая название файла)
	 * @var string
	 */
	protected $xml_file;


///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Конструктор. Назначает свойства
	 *
	 * @param string $xml_file		путь, где лежит xml-файл (включая название файла)
	 */
	public function __construct($xml_file){
		$this->xml_file=$xml_file;
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Выполняет обработку xml
	 *
	 * Все тэги, что есть в этом xml, должны обязательно иметь соответсвующие методы обработки "process_tag_".$tag["tag"]
	 *
	 * @param array $preprocess_params	параметры препроцессинга
	 */
	public function process_xml($preprocess_params=""){
		if(!file_exists($this->xml_file)){
			$this->error_register("Cannot find xml file: {$this->xml_file}", "");
		}else{
			$parser=new ExpatXMLParser();
			$xml=file_get_contents($this->xml_file);
			$xml=$this->preprocess_xml($xml, $preprocess_params);
			$parsed=$parser->Parse($xml, __LOAD_FROM_MEM);
			$this->process_tag($parsed[0], array());
		}
	}

	/**
	 * Может быть переопределен, чтобы предобработать xml-файл. Например, пропустить через смарти
	 *
	 * @param string $xml		содержиме xml-файла
	 * @param array $preprocess_params	параметры препроцессинга
	 * @return string
	 */
	protected function preprocess_xml($xml, $preprocess_params){
		return $xml;
	}

	/**
	 * Исполняет общие операции тэгов, запускает выполнение специальных операций тэгов
	 *
	 * Конкретные тэги могут возвращать контекст, который передается дочерним тэгам, чтобы управлять их поведением (они должны понимать этот контекст).
	 * Есть один глобальный контекст - array("ignore_children"=>1) запрещает обрабатывать детей возвратившего такой контекст тэга
	 *
	 * @param array $tag		тэг (вместе с детьми)
	 * @param array $context	вышележащий тэг может через эту переменную указать нижележащему, что ему делать
	 */
	protected function process_tag($tag, $context){
		$method_name="process_tag_".$tag["tag"];
		if(method_exists($this, $method_name)){
			$context=$this->$method_name($tag, $context);
			if($tag["children"] && !$context["ignore_children"]){
				$inner_context=array();
				foreach($tag["children"] as $children){
					$inner_context[]=$this->process_tag($children, $context);
				}
				$processed_method_name = "processed_tag_".$tag["tag"];
				if (method_exists($this, $processed_method_name)) {
					$this->$processed_method_name($tag, array_filter($inner_context));
				}
			}
			return $context;
		}else{
			$this->error_register("Unknown tag", $tag);
		}
	}

	/**
	 * Регистрация ошибки обработки xml
	 *
	 * @param string $message	сообщение об ошибке
	 * @param array $tag		тэг, чтобы можно было из него извлечь информацию для формирования адреса ошибки
	 */
	abstract protected function error_register($message, $tag);

	/**
	 * Собирает красивый адрес ошибки
	 *
	 * @param string $message	сообщение об ошибке
	 */
	protected function error_address($tag){
		return " in tag <{$tag["tag"]}> in file {$this->xml_file} on line {$tag["line"]}";
	}

	/**
	 * Проверяет наличие обязательного атрибута в тэге
	 *
	 * В случае отсутствия регистриует ошибку. Возвращает bool с результатом проверки
	 *
	 * @param string $message	сообщение об ошибке
	 * @todo наверно стоит доработать его на проверку по нашим стандартным ошибкам полей
	 */
	protected function check_attribute($tag, $attribute){
		if(!isset($tag["attributes"][$attribute])){
			$this->error_register("Attribute '{$attribute}' is not found", $tag);
			$verdict=false;
		}else{
			$verdict=true;
		}
		return $verdict;
	}
}
?>