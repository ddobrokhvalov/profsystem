<?php
/**
 * Класс-обертка для Смарти для работы модулей
 *
 * Помещает в шаблон модуля переменные окружения ($this->env), параметры представления ($this->view_param) модуля, а также системные слова
 *
 * @package    RBC_Contents_5_0
 * @subpackage lib
 * @copyright  Copyright (c) 2007 RBC SOFT
 */
class smarty_ee_module extends smarty_ee{

	/**
	 * Конструктор класса, который все и делает
     *
	 * @param object module $module_instance	экземпляр модуля из которого вынимаются все необходимые данные
	 */
	function __construct($module_instance){
		parent::__construct($module_instance->lang);
		$this->current_lang=$module_instance->env["lang_root_dir"];
		$this->assign("env", $module_instance->env);
		$this->assign("view_param", $module_instance->view_param);
	}
}
?>