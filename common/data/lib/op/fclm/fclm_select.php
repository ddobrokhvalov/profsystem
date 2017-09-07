<?php
class fclm_select {
	/**
	 * Сотрудники
	 * @return dbSelect
	 */
	static function employee(){		
		return dbselect::factory(
			array('EMPLOYEE_ID','SURNAME','NAME','PATRONOMIC','EMPLOYEE_ORDER'),
			'EMPLOYEE',null,$a
		);
	} 
}
?>