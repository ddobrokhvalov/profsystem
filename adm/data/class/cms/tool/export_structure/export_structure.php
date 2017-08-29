<?PHP

	include_once(params::$params["adm_data_server"]["value"]."class/cms/tool/export_structure/export_process.php");
	
/**
 * Класс для реализации утилиты Экспорт структуры и контента сайта
 *
 * @package    RBC_Contents_5_0
 * @subpackage cms
 * @copyright  Copyright (c) 2007 RBC SOFT
 * @author Alexandr Vladykin  
 */

	class export_structure extends tool {
		
		/**
		* Время одной итерации
		*/
		
		const TIME_FOR_RELOAD = 3;
		
		/**
		* Метод по умолчанию, выводится список разделов и параметры экспорта для выбора 
		*/
		public function action_index () {
			$page_obj = self::factory('PAGE');
			$page_obj -> apply_object_parameters( $none = array() );
			
			$headers=$page_obj->get_index_header("tree");
			unset($headers['link_to_block'], $headers['link_to_module'], $headers['link_to_preview']);
			$headers['action'] = array('escape'=>0);
			
			$done_records = $page_obj->get_tree_records(0, array('simple_list'=>1));
			
			for ($i=0, $n=sizeof($done_records); $i<$n; $i++)
				$done_records[$i]['action'] = '<input type="radio" name="_f_page_id" value="'.$done_records[$i]['PAGE_ID'].'" onclick="radio_changed('.$done_records[$i]['PARENT_ID'].')">';
			
			$this->title=metadata::$objects[$this->obj]["title"];
			
			$flags_tpl = new smarty_ee( metadata::$lang );
			unset(metadata::$objects['PAGE']['fields']['VERSION']['filter_short']);
			$flags_tpl->assign('filter', html_element::html_filter( $page_obj, 1, 1, $this, true ));
			$flags = $flags_tpl->fetch($this -> tpl_dir . 'cms/export_structure/first_header.tpl');
			
			$fields = $this -> url -> get_hidden();
			$fields .= '<input type="hidden" name="action" value="distributed"> <input type="hidden" name="do_op" value="export">';
						
			$tpl = new smarty_ee( metadata::$lang );
			
			$tpl -> assign( 'title', $this -> get_title() );
			$tpl -> assign( 'header', $flags);
			$tpl -> assign( 'table', html_element::html_table( array( 'header' => $headers, 'list' => $done_records, 'counter' => $counter, 'html_hidden' => $fields ), $this -> tpl_dir . 'core/html_element/html_table.tpl', $this -> field ) );
			
			$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_grid.tpl' );			
			
			$page_obj->__destruct();
			
			return;
		}
		
		/**
		* Информация об распределенной операции
		* @param array $status данные распределенной операции
		* @return array
		*/
	
		public function export_info (&$status) {
			$status = $_REQUEST;
			$status['export_processed'] = true;
			$total=distributed_process::get_count($status, 'export_process');
			return array("title"=>metadata::$lang["lang_es_export_processing"], "back_url"=>$this->url->get_url(), "total"=>$total, "complete_message"=>metadata::$lang["lang_operation_completed_succesfully"], "for_once"=>1, "exception_fatal"=>1);
		}
		
		/**
		* Возвращает информацию закончена ли операция экспорта на данной итерации или нет
		* @return array
		*/
		
		public function export_list (&$status, $from, $for_once) {
			return $status['export_processed']?array(1):array();
		}
		
		/**
		* Запускает итерацию распределенной операции экспорта
		*/
		
		public function export_item ($item, &$status) {
			$status['export_processed'] = distributed_process::process($status, 'export_process', self::TIME_FOR_RELOAD);
			$status['counter']=distributed_process::get_current_counter();
			if ($status['export_processed'] && ($status['counter']+1)>=$status['total']) 
				$status['counter']=$status['total']-2;
		}
	}
?>