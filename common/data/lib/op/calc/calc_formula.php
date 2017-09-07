<?php
/**
 * Формула калькулятора
 * @author atukmanov
 *
 */
class calc_formula {
	/**
	 * Разрешенные ф-и
	 * @var array
	 */
	static $allowedFunctions=array(
		'SQRT','LOG','POW','MAX','MIN'
	);
	/**
	 * Соответсвие js и php функций:
	 * @var array
	 */
	static $jsFunctions=array(
		'SQRT'=>'Math.sqrt',
		'LOG'=>'Math.log',
		'POW'=>'Math.pow',
		'MAX'=>'Math.max',
		'MIN'=>'Math.min',
	);
	/**
	 * Формула рассчета:
	 * @var unknown_type
	 */
	protected $formula;
	/**
	 * 
	 * @param $formula -формула	 
	 */
	function __construct($formula){
		$this->formula=$formula;
	}
	/**
	 * Проверить функции:
	 * @throws calc_exception
	 * @return void
	 */
	protected function _validateFunctions(){
			
		if (!preg_match_all('/([A-_]+)/', $this->formula, $m)) return true;//thereis no functions here
		foreach ($m[1] as $function){
			if (!in_array($function, self::$allowedFunctions)){
				throw new calc_exception(calc_exception::invalidFormula, $m[1]);
			}
		}		
	}
	/**
	 * Список переменных
	 * @return string
	 */
	protected function _getVariables(){
		if (!preg_match_all('/([a-z]+)/', $this->formula, $m)) return array();//thereis no variables here
		return $m[1];
	}
	/**
	 * Возвращает функцию:
	 * @return function
	 */
	function getFunction(){
		$this->_validateFunctions();		
		$q=preg_replace('/([a-z]+)/','$q[\'\1\']',$this->formula);
		return create_function('$q','return '.$q.';');
	}
	
	var $errors=array();
	
	function execute($q){
		$vars=$this->_getVariables();
		foreach ($vars as $varname){
			if (!isset($q[$varname])||!(preg_match('/^\d+$/',$q[$varname]))) throw new calc_exception(calc_exception::undefinedVariable,$varname);
			$this->q[$varname]=$q[$varname];	
		}		
		$f=$this->getFunction();
		
		return $f($q);
	}
	/**
	 * Получить javascript:
	 * @return string
	 */
	function getJavacript(){
		$this->_validateFunctions();
		$f=preg_replace('/([a-z]+)/','q[\'\1\']',$this->formula);
		
		$f=str_replace(array_keys(self::$jsFunctions),array_values(self::$jsFunctions),$f);
		return 'function(q){return '.$f.';}';
	}
	/**
	 * Переменные:
	 * @var array
	 */
	var $v=array();
	/**
	 * Запрос:
	 * @var array
	 */
	var $q=array();
	/**
	 * Проверка переменных:
	 * @return boolean
	 */
	function checkVariables($v){
		//Получаем переменные:
		$arr=array();
		foreach ($v as $var){
			//Запомним для вывода ошибок:
			calc_exception::$lang['var_'.$var['NAME']]=$var['TITLE'];
			$arr[]=$var['NAME'];
		}		
		//Проверяем достаточность:
		foreach ($this->_getVariables() as $var){
			if (!in_array($var, $arr)){				
				return false;
			}
		}
		return true;
	}
}
?>