<?PHP
	/**
	* Класс исключений для системы обновлений RBC Contents
	*
	* @package		RBC_Contents_5_0
	* @subpackage te
	* @copyright	Copyright (c) 2007 RBC SOFT
	* @author Alexandr Vladykin
	*
	*/
	class SystemUpdateException extends Exception {

		/**
		* Конструктор
		* @param string $message - строка сообщения об ошибке
		* @param system_update $system_update_obj - объект system_update
		* @param mixed $system_update_data - ID или массив данных записи SYSTEM_UPDATE
		*/
		public function __construct ($message, $system_update_obj, $system_update_data='', $code = 0) {
			parent::__construct($message, $code);
			$this->message=$this->__toString();
			$system_update_obj->log_register_update('update_error', $system_update_data, 'update_error', $this->__toString());
		}
		
		/**
		* Используется для вывода инфы
		*/
		public function __toString() {
			return "{$this->message} -- ".' File: '.$this->getFile().' Line: '.$this->getLine();
		}
	}
?>