<?php
class rbcc5_admin{
	static function renderPage(&$module, $template, $data, $printTabs=true, $printOperations=true){
		$tpl = new smarty_ee( metadata::$lang);
		$tpl->assign($data);
		if (false===strpos($template, '.')) $template.='.tpl';
		self::printPage($module, $tpl->fetch($module -> tpl_dir.$template), $printTabs, $printOperations);
	}
	
	static function printPage(&$module, $content, $printTabs=true, $printOperations=true){
		
		/**
		 * Шапочка:
		 */
		$tpl = new smarty_ee( metadata::$lang, array( $module->full_object, 'ext_index_template' ) );
		
		$tpl -> assign( 'title', $module->getPageTitle());
		$tpl -> assign( 'form_name', ($printOperations)?'checkbox_form':null );
		if ($printTabs=true){
			$tpl -> assign( 'tabs', $module -> full_object -> get_header_tabs( $module -> primary_key -> get_from_request(), $_REQUEST['m2m'] ) );
		}
		$tpl -> assign( 'filter', '' );
		$operations=$module->get_operations(array('apply','save','cancel'), 'checkbox_form');
		if ($printOperations){
			$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $module -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
			$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $module -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
		}
		$tpl -> assign( 'form', $content);
		$tpl -> assign( 'navigation', '');
		$module->set_blocked_tpl_params($tpl, $module->primary_key->get_from_request());		
		$module -> body = $tpl -> fetch( $module -> tpl_dir . 'core/html_element/html_card.tpl' );		
	}
	
	
	/**
	 * Вывод попапа:
	 * @return string
	 */
	static function renderPopup($module, $tpl, $data){
		$template = new smarty_ee( metadata::$lang);
		$template->assign($data);
		$content=$template->fetch($module->tpl_dir.$tpl);
		
		$popup=new smarty_ee( metadata::$lang);
		$popup->assign('content',$content);
		return $popup->fetch($module->tpl_dir.'core/object/popup.tpl');
	}
	
	static function renderPopupCallback($module,$obj){
		$popup=new smarty_ee( metadata::$lang);
		$popup->assign('obj',$obj);
		return $popup->fetch($module->tpl_dir.'core/object/popup_callback.tpl');
	}
}
?>