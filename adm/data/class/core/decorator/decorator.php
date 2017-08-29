<?php
/**
 * Класс абстрактный декоратор
 *
 * @package    RBC_Contents_5_0
 * @subpackage core
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
abstract class decorator{

	/**
	 * Внутренний (декорируемый) объект
	 * @var object
	 */
	protected $inner_object=0;

	/**
	 * Внешний объект
	 * @var object
	 */
	protected $full_object=0;

	/**
	 * "Ключевой" хэш декораторов (то есть array("lang"=>"lang", "version"=>"version"))
	 *
	 * Нужен для того, чтобы декораторы могли узнать о наличии других декораторов, для корректного их сосуществования
	 * @var array
	 */
	protected $decorators;

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Неопределенные текущим декоратором методы автоматически перегружаются на внутренний объект
	 *
	 * @param string $method	название перегружаемого метода
	 * @param array $vars		массив с параметрами метода
	 * @return mixed
	 */
	public function __call($method, $vars0){
		//return call_user_func_array(array($this->inner_object, $method), $vars);
		
     $vars = array(); foreach($vars0 as &$var) $vars[] = &$var;
     return call_user_func_array(array(&$this->inner_object, $method), $vars);
 		
	}

	/**
	 * Метод выборочной перегрузки на родителя, а если не получится, то на внутренний объект
	 *
	 * Во всех конкретных классах, наследующихся от декораторов, нельзя вызывать внутренний объект, а нужно
	 * использовать parent::, чтобы задействовать код декоратора. Если же в декораторе вызываемый метод
	 * не описан, а есть только на более внутренних уровнях, то нужно в конкретном классе использовать call_parent()
	 * для корректного вызова родительского метода, чтобы в случае, если в декораторе вызываемый метод
	 * все же появится, то конкретный класс не потребовалось бы переделывать для его вызова.
	 *
	 * Разумеется, те методы, что уже описаны в наследуемых декораторов нужно вызывать по-прежнему через parent::,
	 * потому что хоть и накладные расходы невелики, но лучше обходиться без них.
	 * Замеры показали, что накладные расходы использования этого метода составляют около 0.0001 секунды
	 *
	 * @param string $method	название перегружаемого метода
	 * @param array $vars		массив с параметрами метода
	 * @return mixed
	 */
	protected function call_parent($method, $vars=array()){
		$parent_class=new ReflectionClass(get_parent_class($this));
		if($parent_class->hasMethod($method)){
			$parent_method=new ReflectionMethod(get_parent_class($this), $method);
			return $parent_method->invokeArgs($this, $vars);
		}else{
			return $this->__call($method, $vars);
		}
	}

	/**
	 * Конструктор. Создает ссылку на самый внешний объект, а также список декораторов
	 *
	 * @see object::__construct()
	 */
	function __construct(&$full_object, $decorators){
		$this->full_object=&$full_object;
		$this->decorators=$decorators;
	}

	/**
	 * Деструктор. Устраняет циклические ссылки объекта
	 *
	 * Должен вызываться явно, в противном случае объект будет оставаться в памяти - http://bugs.php.net/bug.php?id=33595
	 */
	function __destruct(){
		unset($this->full_object);
		unset($this->inner_object);
	}

	/**
	 * Создает ссылку на декорируемый объект
	 *
	 * Заполняет свойства ссылками на соответствующие свойства декорируемого объекта.
	 * Кроме, конечно, свойства inner_object, которое должно быть своим собственным у каждого декоратора.
	 *
	 * @param object &$inner_object		декорируемый объект
	 */
	public function apply_inner_object(&$inner_object){
		$this->inner_object=&$inner_object;
		foreach($this->inner_object as $property=>$value){
			if($property!="inner_object"){
				$this->$property=&$this->inner_object->$property;
			}
		}
	}
}
?>