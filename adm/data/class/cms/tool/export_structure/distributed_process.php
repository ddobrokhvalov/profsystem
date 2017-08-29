<?PHP
/**
* Абстрактный класс для операций, которые достаточно длительный и для них необходимо применить механизм распределенных операций
* При этом операции сложно разделить на какие-то равновременные этапы
* Класс позволяет прерывать процесс по времени, для использования данного класса необходимо во всех случаях подозрения на длительную операцию
* Вызывать метод check_time, сохранять все необходимые данные в параметр current_state для восстановления точки запуска в следующей итерации
* И везде выше проверять прерывание процесса методом is_terminated
* Метод, который будет вызывать данный класс у своих потомков называется run, его обязательно необходимо переопределить
* В методах, которые являются шагами алгоритма необходимо в начале писать 
* if (in_array(__METHOD__, $this->processed_stages)) return;
* А в конце
* array_push($this->processed_stages, __METHOD__);
* Чтобы на последующих итерациях они не вызывались более
* 
* Вызов производится при помощи статического метода process
*
* @todo Добавить функционал __sleep, __wakeup
*
* @package		RBC_Contents_5_0
* @subpackage cms
* @copyright	Copyright (c) 2007 RBC SOFT
* @author Alexandr Vladykin 
*/

	
abstract class distributed_process {	
	
	/**
	* Время начала итерации. Устанавливается в методе process по microtime
	* @var int
	*/
	
	private static $start_time;
	
	/**
	* Максимальное время продолжения итерации в миллисекундах, если равен -1 - время неограничено
	* @var int
	*/
		
	private static $max_time;
	
	/**
	* Данные об операции
	* @var array
	*/
	
	protected static $data;
	
	/**
	* Внутренний объект, сериализуется в $data['run_obj'] между итерациями
	* @var object distributed_process
	*/
	
	protected static $run_obj;
	
	
	/**
	* Массив прошедших стадий, заполняется всеми стадиями, которые должны вызываться в процессе 1 раз
	* @var array
	*/
	
	protected $processed_stages = array();

	/**
	* Признак завершения всех операций в итерации процесса, заполняется методом terminate, проверяется методом is_terminated
	* @var boolean
	*/
	
	private $terminated = false;

	/**
	* Массив, в который сохраняются все данные процесса, которые необходимо перенести между итерациями
	* @var array
	*/
	
	protected $current_state = array();

	/**
	* Счетчик операций
	*/ 
	
	public $counter=0;
	
	/**
	* Указывает что объект только подсчитывает необходимое кол-во операций
	*/ 
	
	public $only_counter=false;
	
	
	/**
	* Метод, запускающий процес. Возвращает признак завершения процесса. true - не завершен, false - завершен
	* @param array $data Ссылка на данные, сохраняемые между итерациями. 
	* @param int $time Максимальное время для одной итерации, если не задано, то время неограничено
	* @param array $params Параметры, передаваемые конструктору  объекта при его создании
	*
	* @return boolean
	*/

	public static function process (&$data, $obj=null, $time=-1, $params=array()) {
		// если в данных есть объект, то восстанавливаем его, иначе создаем новый
		if ($data['run_obj']) {
			self::$run_obj = unserialize($data['run_obj']);
			unset($data['run_obj']);
		}
		else {
			self::$run_obj = new $obj($params);
		}
		
		self::$data = &$data;
		self::$start_time = microtime(true);
		self::$max_time = $time;
		
		self::$run_obj->run($data);
		
		return self::$run_obj->is_terminated();
	}
	
	/**
	* Возвращает число - сколько всего операций необходимо проделать
	*/
	
	public static function get_count(&$data, $obj) {
		$cnt_obj = new $obj(array('only_counter'=>true));
		$cnt_obj->run($data);
		return $cnt_obj->counter;
	}
	
	/**
	* Возвращает текущее кол-во пройденных операций
	*/

	public static function get_current_counter() {
		if (self::$run_obj)
			return self::$run_obj->counter;
		return 0;
	}
	
	
	/**
	* Проверка на превышение максимального метода итерации. 
	* Необходимо вызывать в процессе после каждой предположительно затратной операции в процессе
	* Вызывает метод terminate внутреннего объекта в случае если операцию пора прекращать
	* Возвращает true если операции можно продолжить, false - если операции необходимо прервать
	* @return boolean
	*/
	
	protected static function check_time () {
		if (!self::$run_obj) return true;
		$current_time = microtime(true);
		if ((self::$max_time>=0) && (($current_time - self::$start_time) > self::$max_time)) {
			self::$data['run_obj'] = serialize(self::$run_obj);
			self::$run_obj->terminate();
			return false;
		}
		
		return true;
	}
	
	/**
	* Конструктор объекта, передаваемые параметры в случае если есть св-ва класса с такими ключами заменяет их на значения параметров
	* @param array $params Параметры
	*/
	protected function __construct($params=array()) {
		foreach ($params as $key=>$value)
			if (property_exists($this, $key))
				$this->$key=$value;
	}

	/**
	* Остановка работы итерации.
	*/
	
	protected function terminate() {
		$this->terminated = true;
	}
	
	/**
	* Проверка, остановлена ли итерация
	* @return boolean
	*/
	
	protected function is_terminated() {
		return $this->terminated;
	}
	
	
	/**
	* Абстрактый главный метод действия, вызываемый в потомках
	* @param $data Общие данные распределенной операции
	*/
	
	abstract protected function run (&$data);
}
?>