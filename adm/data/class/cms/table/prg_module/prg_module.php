<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Модули"
 *
 * @package    RBC_Contents_5_0
 * @subpackage cms
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class prg_module extends table_translate
{
	/**
	 * Выкидываем из списка модули без элементов контента, для которых уже существуют блоки
	 */
	public function ext_index_by_list_mode( $mode, $list_mode )
	{
		list( $where, $binds ) = $this -> call_parent( 'ext_index_by_list_mode', array( $mode, $list_mode ) );
		
		if ( $list_mode['with_elements'] )
			$where .= ' and PRG_MODULE.PRG_MODULE_ID not in (
				select PRG_MODULE.PRG_MODULE_ID from PRG_MODULE
				inner join INF_BLOCK on PRG_MODULE.PRG_MODULE_ID = INF_BLOCK.PRG_MODULE_ID
				where PRG_MODULE.IS_ELEMENTS <> 1 )';
		
		if($list_mode["by_auth_object"]){
			// Выбираем идентификаторы нужных объектов
			foreach(metadata::$objects as $obj=>$object){
				if($list_mode["by_auth_object"]=="inf_block" && $object["decorators"]["block"] && !$object["decorators"]["workflow"]){
					$system_names[]="'{$obj}'";
				}elseif($list_mode["by_auth_object"]=="workflow" && $object["decorators"]["workflow"]){
					$system_names[]="'{$obj}'";
				}
			}
			// Собираем кляузу
			$in=(is_array($system_names) ? join(", ", $system_names) : "''");
			$subwhere=" PRG_MODULE.SYSTEM_NAME IN ({$in}) ";
			// Дополняем кляузу молулями без элементов контента, если надо
			if($list_mode["by_auth_object"]=="inf_block"){
				$subwhere=" AND (PRG_MODULE.IS_ELEMENTS = 0 OR {$subwhere})";
			}else{
				$subwhere=" AND {$subwhere}";
			}
			$where.=$subwhere;
		}
		
		return array( $where, $binds );
	}
	
	/**
	 * Управляем видимостью поля "Название элемента контента" в зависимости от поля "Есть элементы контента"
	 *
	 * @see table::html_card()
	 */
	public function html_card( $mode, &$request )
	{
		list( $title, $html ) = $this -> call_parent( 'html_card', array( $mode, &$request ) );
		
		$form_name = html_element::get_form_name();
		
		$html .= <<<HTML
<script type="text/javascript">
	var oForm = document.forms['{$form_name}'];
	var oIsElements = oForm['_form_IS_ELEMENTS'][1];
	
	addListener( oIsElements, 'click', checkIsElements ); checkIsElements();
	
	function checkIsElements()
	{
		var aFormTableRows = oForm.getElementsByTagName( 'tr' );
		for ( var i = 0; i < aFormTableRows.length; i++ )
			if ( /_form_ELEMENT_NAME/.test( aFormTableRows[i].id ) )
				aFormTableRows[i].style.display = oIsElements.checked ? '' : 'none';
	}
</script>
HTML;
		return array( $title, $html );
	}
}
?>