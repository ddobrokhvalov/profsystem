<?PHP
/**
* Класс, позволяющий сделать подтверждение на уровне сервера
* @package    RBC_Contents_5_0
* @subpackage te
* @author Alexandr Vladykin <avladykin@rbc.ru>
* @copyright  Copyright (c) 2007 RBC SOFT 
*/

class confirm_action extends tool {
	
	/**
	* @var array $get_params Параметры GET
	*/
	
	private $get_params;

	/**
	* @var array $get_params Параметры POST
	*/
	
	private $post_params;
	
	/**
	* Вывод формы
	*/
	
	public function action_index(){
		$get_params = unserialize($_POST['GET']);
		$post_params = unserialize($_POST['POST']);
		
		// для того чтобы подсветить правильный раздел в верхнем меню
		$from_obj = $get_params['obj']?$get_params['obj']:$post_params['obj'];
		
		if ($from_obj) {
			$obj = object::factory($from_obj);
			$this->auth_system_section = $obj->auth_system_section;
		}
		
		$tpl = new smarty_ee( metadata::$lang );
		$tpl->assign('message', $_POST['message']);
		$tpl->assign('get_params', $get_params);
		$tpl->assign('post_params', $post_params);
		
		$this -> body = $tpl -> fetch( $this -> tpl_dir.'core/html_element/html_confirm_action.tpl' );
	}

}
?>