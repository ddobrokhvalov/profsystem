<?php


/**
* Загрузка из файла
*/

define ("__LOAD_FROM_FILE", 0);

/**
* Загрузка из памяти
*/

define ("__LOAD_FROM_MEM", 1);

/**
 * Класс "Парсер XML"
 *
 * Производит парсинг XML
 * @package    RBC_Contents_5_0
 * @subpackage lib
 * @copyright  Copyright (c) 2007 RBC SOFT
 */


class ExpatXMLParser
{

#----------------------------------------------------------------------------------------------------------------------

	/**
	* @var boolean $convert	Если установлен то конвертирует данные из UTF-8 в CP1251
	*/
	
	private $convert 	= false;
	
	/**
	* @var boolean $strtoupper	Если установлен то конвертирует все данные в верхний регистр
	*/
	
	public $strtoupper	= false;

	/**
	* @var array $stack	Сохраняются данные при парсинге xml
	*/
	
	private $stack		= array();
	
	/**
	* @var string $error	Сохраняется ошибка
	*/
	
	private $error		= '';
	
	/**
	* @var object $xmlh	Объект xml_parser
	*/
	
	private $xmlh		= null;
	
	/**
	* @var string $value	Сохраняется значение элемента
	*/
	private $value		= "";

	
	/**
	* Конструктор
	*/

	function __construct()
	{
		$this->xmlh = xml_parser_create();
		if ( $this->xmlh )
		{
			xml_set_object( $this->xmlh, $this );
			xml_parser_set_option($this->xmlh, XML_OPTION_CASE_FOLDING, false);
			xml_set_element_handler( $this->xmlh, "bTag", "eTag" );
			xml_set_character_data_handler( $this->xmlh, "cdata" );
		}
		else $this->error = "Can't create xml reference";
		
		// если кодировка системы не UTF, то нужно конвертировать
		if (
			(
				class_exists(params) 
					&& 
						(strtoupper(params::$params['encoding']['value'])!='UTF-8')
			) 
			||
			// для инсталлятора 
			(
				!class_exists(params) 
					&& 
						$GLOBALS['params'] 
							&& 
								(strtoupper($GLOBALS['params']['encoding'])!='UTF-8')
			)
		)
			$this -> convert = 1;
	}

	
	/**
	* Деструктор
	*/
	
	function __destruct()
	{
		if ( $this->xmlh )
		{
			xml_parser_free($this->xmlh);
		}
	}

	/**
	* Возвращает объект xml_parser
	* @return object
	*/
	
	public function get_obj() {
		return $this->xmlh;
	}
	
	/**
	* Парсинг
	* @param string $value	Путь к XML-файлу или XML-текст, в зависимости от $mode
	* @param int $mode	Параметр, указывающий чем является параметр $value - путем к файлу или текстом XML.
	* Может быть задан константами __LOAD_FROM_FILE или __LOAD_FROM_MEM соответственно
	* @return array Пропарсенный xml-файл в виде ассоциативного массива
	*/
	
	public function Parse( $value, $mode = __LOAD_FROM_FILE )
	{
		if ( $this->convert && !function_exists('iconv'))
		{
			$this->error = "Can't found iconv() function. Install PHP with iconv extension";
		}
		else
		{
			if ( $mode == __LOAD_FROM_FILE )
			{
				if ( file_exists($value) )
				{
					$xml_src = file_get_contents($value);
				}
				else $this->error = "Can't open XML file: ".$value;
			}
			else $xml_src = $value;

			if ( !$this->error )
			{
				if ( $this->convert )
				{
					$xml_src = preg_replace('/\bencoding="WINDOWS-1251"/i', 'encoding="UTF-8"', $xml_src);
					$xml_src = @iconv('WINDOWS-1251', 'UTF-8', $xml_src);
				}

				$data = xml_parse( $this->xmlh, $xml_src );

				if( !$data )
				{
					$this->error = sprintf("XML error: %s at line %d",
											xml_error_string(xml_get_error_code($this->xmlh)),
											xml_get_current_line_number($this->xmlh));
				}
			}

			unset($xml_src);
		}

		if ( $this->error ) 
			throw new Exception ( $this->error );

		return $this->stack;
	}


	/**
	* Очистка объекта
	*/
	
	public function Free()
	{
	 	unset( $this );
	}


	/**
	* Парсинг тега XML
	* @param object $parser	Объект xml-парсер
	* @param string $name	Имя тега
	* @param array $attrs	Атрибуты тега
	*/
	
	private function bTag( $parser, $name, $attrs )
	{
		$rname 	= $name;
		$rattrs = $attrs;
		$this->value = "";

		if ( $this->strtoupper )
		{
			$rname 	= strtoupper($rname);
			$rattrs = array_change_key_case($rattrs, CASE_UPPER);
		}

		#convert tag name data
		if ( $this->convert )
		{
			$rname 	= @iconv('UTF-8', 'WINDOWS-1251', $rname);
		}

		if ( !empty( $rattrs ) )
		{
			#convert attributes data
			if ( $this->convert )
			{
				$buf = $rattrs;
				unset($rattrs);

				$keys = array_keys($buf);
				$index = sizeof($keys);
				for($i=0; $i<$index; $i++)
				{
					$rattrs[$keys[$i]] = @iconv('UTF-8', 'WINDOWS-1251', $buf[$keys[$i]]);
				}
			}

			$tag = array( "tag" => $rname, "attributes" => $rattrs, "line"=>xml_get_current_line_number($this->xmlh));
		}
		else
		{
			$tag = array( "tag" => $rname, "line"=>xml_get_current_line_number($this->xmlh));
		}

		array_push( $this->stack, $tag );

		unset($rattrs);
	}


	/**
	* Парсинг текстового содержимого xml-элемента
	* @param object $parser	Объект xml-парсер
	* @param string $cdata	Содержимое xml-элемента
	*/
	
	private function cdata( $parser, $cdata )
	{
		$buf = $cdata;

		if ( $this->convert )
		{
			$buf = @iconv('UTF-8', 'WINDOWS-1251', $buf);
		}

		if( isset($buf) )
		{
			$index = sizeof($this->stack);

			if ( $index > 0 )
			{
				$this->value .= $buf;
			}
		}

		unset($buf);
	}

	/**
	* Обрабатывает закрывающий элемент xml-тега
	* @param object $parser	объект xml-парсер
	* @param string $name имя тега
	*/
	
	private function eTag( $parser, $name )
	{
		$index = sizeof($this->stack);

		if ( $index > 1 )
		{
			$this->stack[$index-1]['value'] = trim($this->value);
			$this->stack[$index-2]['children'][] = $this->stack[$index-1];
			array_pop($this->stack);
		}
	}

}
?>