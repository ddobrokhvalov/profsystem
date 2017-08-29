<?PHP
	
	include_once params::$params["adm_data_server"]["value"]."class/te/tool/autotest/module/check_workflow/test_workflow.php";
	include_once params::$params["adm_data_server"]["value"]."class/te/table/wf_workflow/wf_workflow_graph.php";


/**
* Дополнительная функциональность для wf_workflow
* @package		RBC_Contents_5_0
* @subpackage te
* @copyright	Copyright (c) 2007 RBC SOFT 
*/

	class wf_workflow extends table_translate {
		
		/**
		* Нужно показать в общем списке ссылки на схему
		*/
		
		public function get_index_ops($record) {
			$ret = $this->call_parent('get_index_ops', array($record));
			$ret['_scheme'] = array('url'=>$this->url->get_url('scheme', array("pk"=>$this->full_object->primary_key->get_from_record($record))));
			return $ret;
		}
		
		/**
		* Нужно показать в общем списке ссылки на схему
		*/
		
		public function ext_index_header($mode) {
			$ret = $this->call_parent('ext_index_header', array($mode));
			$ret['_scheme'] = array (
						"title"=>metadata::$lang['lang_wf_workflow_scheme'],
						'type'=>'_link'
			);
			return $ret;
		}
		
		/**
		* Вывод схемы
		*/
		
		public function action_scheme() {
			$pk=$this->primary_key->get_from_request();
			
			$this->primary_key->is_record_exists($pk, true);
			
			$test_obj = new test_workflow($pk['WF_WORKFLOW_ID']);
			if (!$test_obj->test()) {
				$this->body=object::html_error(metadata::$lang['lang_wf_workflow_bad_workflow'], '', '', '', '').$this->show_workflow_test_report($test_obj->get_report());
				return;
			}

			if(!function_exists("imagecreatetruecolor"))
				throw new Exception(metadata::$lang['lang_wf_workflow_gd_is_not_installed']);
			
			if(!function_exists("imagepng")) 
				throw new Exception(metadata::$lang['lang_wf_workflow_png_is_not_supported']);

			if(!function_exists("imagettftext"))
				throw new Exception(metadata::$lang['lang_wf_workflow_ttf_is_not_supported']);

			$tpl = new smarty_ee( metadata::$lang );
			$this->title=metadata::$lang['lang_wf_workflow_scheme'];
			$tpl -> assign( 'workflow_name',  $this->get_record_title($pk));
			$tpl -> assign( 'scheme_src', $this->url->get_url('scheme_image', array('pk'=>$pk)).'&rand='.mt_rand(1,999999));
			$this -> body = $tpl -> fetch( $this -> tpl_dir . 'te/wf_workflow/wf_scheme.tpl' );
		}
		
		/**
		* Возврашает страницу с ошибками тестирования цепочки
		* @var array $report Отчет, возвращенный автотестом
		* @return string
		*/
		
		private function show_workflow_test_report($report) {
				$tpl = new smarty_ee( metadata::$lang );
				$tpl->assign('header', array('descr'=>array('title'=>metadata::$lang['lang_autotest_messages'])));
				$tpl->assign('list', $report);
				return $tpl->fetch($this -> tpl_dir.'/core/html_element/html_table.tpl');
		}
		
		/**
		* Вывод картинки
		*/
		
		public function action_scheme_image () {
			$test_obj = new test_workflow($_REQUEST['WF_WORKFLOW_ID']);
			if (!$test_obj->test()) {
				$im = ImageCreate(115, 30);
				$bgc = ImageColorAllocate($im, 255, 255, 255);
				$tc = ImageColorAllocate($im, 0, 0, 0);
				ImageFilledRectangle($im, 0, 0, 150, 30, $bgc);
				ImageString($im, 10, 5, 5, "Scheme error", $tc);
				imagepng($im);
				exit;
			}
			
			$wf_drawer = new wf_workflow_graph($_REQUEST['WF_WORKFLOW_ID']);
			$wf_drawer -> draw();
			$wf_drawer -> __destruct();
		}
	}
?>