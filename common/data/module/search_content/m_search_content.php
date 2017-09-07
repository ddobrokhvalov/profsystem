<?php
/**
 * Модуль "Поиск"
 *
 */
class m_search_content extends module
{
	protected $tpl;
	protected function content_init()
	{
		// Инициализация шаблонизатора
		$this -> tpl = new smarty_ee_module( $this );
		
		$tpl_file = $this->view_param["view_mode"].".tpl";
		
		$page_search = $this->get_url_by_page($this->view_param["page"]);
		$this->tpl->assign("page_search", $page_search);
		/*print_r("<pre>");
		print_r($page_search);
		print_r("</pre>");*/
		
		if(trim($this->q_param["search_string"])){
			$this->tpl->assign("search_string", trim($this->q_param["search_string"]));
			$search_string = trim($this->q_param["search_string"]);
			
			$query_count = "select count(*) as RESULT_COUNT
											from SEARCH_CONTENT 
											where ( TITLE like :search_string1 or ANNOUNCE like :search_string2 or BODY like :search_string3 ) 
											and VERSION = :version and LANG_ID = :lang_id";
			$bind_count = array("search_string1"=>"%".$search_string."%", 
										"search_string2"=>"%".$search_string."%", 
										"search_string3"=>"%".$search_string."%",
										"version"=>$this->env["version"], 
										"lang_id"=>$this->env["lang_id"]);
			$count_results = db::sql_select($query_count, $bind_count);
			$count_results = $count_results[0]['RESULT_COUNT'];
			
			
			//if ( !$count_results ) return;
			
			$limit_str = ''; $items_per_page = max( intval( $this -> view_param['items_per_page'] ), 1 );
			// Подготавливаем навигационную стороку и строку ограничения числа записей
			if ( $count_results > $items_per_page )
			{
				$from = ( max( intval( $this -> q_param['from'] ), 1 ) - 1 ) * $items_per_page;
				
				$navigation = lib::page_navigation( $items_per_page, $count_results, 'from_'.$this -> env['area_id'], $this -> tpl_dir.'navigation.tpl' );
				
				$this -> tpl -> assign( 'navigation', $navigation);
				
				$limit_str = "limit $from, $items_per_page";
			}
			
			$search_results_sql = "select sc.*, teo.SYSTEM_NAME
											from SEARCH_CONTENT sc
											inner join TE_OBJECT teo on teo.TE_OBJECT_ID = sc.TE_OBJECT_ID
											where ( sc.TITLE like :search_string1 or sc.ANNOUNCE like :search_string2 or sc.BODY like :search_string3 ) 
											and sc.VERSION = :version and sc.LANG_ID = :lang_id ".$limit_str;
			$search_results_bind = array("search_string1"=>"%".$search_string."%", 
										"search_string2"=>"%".$search_string."%", 
										"search_string3"=>"%".$search_string."%",
										"version"=>$this->env["version"], 
										"lang_id"=>$this->env["lang_id"]);
			
			
			
			$search_results = db::sql_select($search_results_sql, $search_results_bind);
			foreach($search_results as $key=>$search_result){
				$path_and_area = $this->get_url_by_module_content($search_result["SYSTEM_NAME"], $search_result["OBJECT_ID"]);
				$search_results[$key]['path_and_area'] = $path_and_area;
				unset($_GET["search_string_".$this->env["area_id"]]);
				unset($_GET["from_".$this->env["area_id"]]);
				$search_results[$key]['URL'] = lib::make_request_uri( array( 'id_' . $path_and_area['AREA'] => $search_result['OBJECT_ID'] ), $path_and_area['PATH'] );
			}
			$this -> tpl -> assign( 'search_results', $search_results);
			/*print_r("<pre>");
			print_r($search_results);
			print_r("</pre>");*/
		}
		$this -> body = $this -> tpl -> fetch( $this -> tpl_dir.$tpl_file );
	}
}