<?php
/**
 * Модуль поиска
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2008 RBC SOFT
 */

class m_mnogosearch extends module {

	var $host_name='';

	function get_hash_code()
	{
		if ($this->q_param['s_result_area_id'] && $this->q_param['s_result_area_id']==$this->env['area_id']) {
			foreach ($_GET as $k => $v) {
				if (!preg_match('~\d~',$k)) {
					//$k - не пропарсен в q_param
					$this->q_param[$k]=$v;
				}
			}
		}

		$p=parent::get_hash_code();
		if($this->env['version']==1)return 0;
		return md5($p.$_COOKIE['client_id'].$_COOKIE['random_number']);
	}
	
	function get_url($page_id){
		$page_row=db::sql_select("SELECT * FROM PAGE WHERE VERSION=:version AND PAGE_ID=:page_id", array('page_id'=>$page_id, 'version'=>$this->env["version"]));
		$parent=$page_row[0]["PARENT_ID"];
		$redir_path=$page_row[0]["DIR_NAME"]."/";
		while($parent!=0){
			$parent_rows=db::sql_select("SELECT PARENT_ID,DIR_NAME FROM PAGE WHERE PAGE_ID=:parent AND version=:version", array('parent'=>$parent, 'version'=>$this->env['version']));
			$redir_path=$parent_rows[0]["DIR_NAME"]."/".$redir_path;
			$parent=$parent_rows[0]["PARENT_ID"];
		}
		return "/".$redir_path;
	}


	/**
	*
	* @todo protected_access ???
	*/
	function get_search_results()
	{
		$template = $this->tpl_dir.'search.htm';
		
		$q_string = "s_result_area_id_{$this->env['area_id']}=".$this->area_id.
		"&q=".urlencode($this->querier($this->q_param["q"])).
		"&ul=".urlencode($this->q_param["ul"]).
		"&ps=".urlencode(( $this->q_param["ps"] > 0  ? $this->q_param["ps"] : 10)).
		"&wf=".urlencode($this->q_param["wf"])
		."&m=".urldecode($this->q_param["m"])
		."&np=".urldecode($this->q_param["np"])
		."&sy=".urldecode($this->q_param["sy"])
		."&sp=".urldecode($this->q_param["sp"])
		."&wm=".urldecode($this->q_param["wm"])
		;
		
		if (!isset(params::$params['db_type']['value'])) {
			$db_type = 'mysql';
		}
		else {
			$db_type = params::$params['db_type']['value'];
		}
		/* if ($protected_access) {
			if (check_autorization()) {
				//авторизован
				switch ($protected_access) {
					case 'isRegistratedClient':
						//
						break;

					case 'isRightClient':
						$q_string.='&fl=isRightClient_'.$db_type
						.'&CLID='.intval($_COOKIE['client_id'])
						.'&R_N='.db_quote($_COOKIE['random_number'])
						;
						break;

					case 'isRightGroup':
						$q_string.='&fl=isRightGroup_'.$db_type
						.'&CLID='.intval($_COOKIE['client_id'])
						.'&R_N='.db_quote($_COOKIE['random_number'])
						;
						break;

					default:
						$q_string.='&fl=UN_PROTECTED_'.$db_type;
						break;
				}
			}
			else {
				$q_string.='&fl=UN_PROTECTED_'.$db_type;
			}
		}*/

		return $this->TranslateSearchResult(file_get_contents(params::$params['common_cgi_http']['value'].'/mnogosearch/runsearch.cgi?'.$q_string));
	}
	#----------------------------------------------------------------------------------------------------------------------------
	/*
	*    Функция заменяет некоторые переменные в результатах поиска системными словами
	*/

	function TranslateSearchResult($result)
	{
		// парсим ссылки
		$regexp = array();
		$regexp[]='~(<a[^>]+href=[\'"])([^\'"]+)([\'"])+~i';$call_back=array($this,'parse_ancor');
		$result = preg_replace_callback ($regexp, $call_back, $result);
		
		//  удаляем номера страниц, если результат помещается только на одну строку (В. Демьяненко)
		if ((strpos($result, '<!-- start page nav -->') !== false) && (strpos($result, '<!-- end page nav -->') !== false)) {
			$cut_start_phrase = '<!-- start of nav -->';
			$cut_end_phrase = '<!-- end of nav -->';
			$cut_start = strpos($result, $cut_start_phrase);
			$cut_end = strpos($result, $cut_end_phrase);
			$result = substr_replace($result, '', $cut_start, $cut_end-$cut_start+strlen($cut_end_phrase));
		}		
		
		$this -> tpl = new smarty_ee_module( $this );
		$this -> tpl -> assign ('eval', $result);
		return $this -> tpl -> fetch ('eval.tpl');;
	}

	function parse_ancor($m)
	{
		if (preg_match('~q=[^&]~',$m[2])) {
			$rx=array();
			$r=array();
			$rx[]='~[&]?(amp;)?fl=[^&]+~';$r[]='';
			$rx[]='~[&]?(amp;)?CLID=[^&]+~';$r[]='';
			$rx[]='~[&]?(amp;)?R_N=[^&]+~';$r[]='';
			// везде добавляем номер area_id
			$rx[]='~[&]?(amp;)?([^=]+)(=[^&]+&?)~'; $r[]='\2_'.$this->env['area_id'].'\3';
			$m[2]=preg_replace($rx,$r,$m[2]);
		}
		return $m[1].$m[2].$m[3];
	}

	function content_init() {
		// Инициализация шаблонизатора
		$this -> tpl = new smarty_ee_module( $this );
		
		switch ($this->view_param["used_type"]) {
			case "quick" :  $template_name="search_quick.tpl"; break;
			case "advanced" :  $template_name="/search_advanced.tpl"; break;
		}

		$template = $this->tpl_dir.$template_name;
		
		if (file_exists ($template)) {
			//какой сайт индексировать?
			//получаем параметр ul
			$sql="select SITE.* from SITE, PAGE where PAGE.PAGE_ID=:page_id	and PAGE.SITE_ID=SITE.SITE_ID";
			$rs=db::sql_select($sql, array('page_id'=>$this->env['page_id']));
			$this->host_name='http'.($_SERVER['HTTPS'] == 'on' ? 's' : '')."://".$rs[0]['HOST'];
			if ($this->q_param['ul']=='') {
				$this->q_param['ul']=$this->host_name."/".$env['lang_root_dir'];
			}

			//количество ссылок на страницу:
			if (!(int)$this->q_param['ps']){
				$this->q_param['ps']=$this->view_param['page_num'];
			}

			//где производить поиск?
			if ($this->q_param['wf']==''){
				$this->q_param['wf']=2221;//во всех частях документа
			}

			//использование синонимов
			if ($this->q_param['sy']==''){
				$this->q_param['sy']=$this->view_param["used_type"]=='advanced'?1:0;//да
			}

			//Words forms
			if ($this->q_param['sp']==''){
				$this->q_param['sp']=$this->view_param["used_type"]=='advanced'?1:0;//все 0 - точно
			}

			//Search for
			if ($this->q_param['wm']==''){
				$this->q_param['wm']=$this->view_param["used_type"]=='advanced'?'wrd':'sub';//Whole word
			}

			//Совпадение
			if ($this->q_param['m']==''){
				$this->q_param['m']='all';//вся фраза
			}


			//быстрый поиск
			if ($this->view_param["used_type"] == 'quick' ){
				$tpl = new smarty_ee_module( $this );

				
				if ($this->view_param["page"] > 0){
					$path=$this->get_url($this->view_param["page"]);
					$area_id = $this->get_area_id( $this->view_param["page"], $this->env['block_id']);
				}
				else {
					$area_id = $this->env['area_id'];
					$path = $this->get_url($this->env['page_id']);
				}
				
				$tpl -> assign ('tarea_id', $area_id);
				$tpl -> assign ('search_page_url', $path);
	
				if ($this->q_param['q']!=''){
					$tpl->assign('search_result',$this->GetSimpleResult());
				}
				
				$tpl -> assign('q_param', $this->q_param);
				$this->body = $tpl->fetch($template);
			}
			//--/быстрый поиск

			//расширенный поиск
			if ($this->view_param["used_type"] == 'advanced' ){
				$this->modAdvancedSearch($template);
			}
			//--/расширенный поиск
			
		}else{
			$this->body = "Шаблон $template не найден";
		}
	}


	#-------------------------------------------------------------------------------------------------------------------------
	/**
	* @return void
	* @param unknown $template
	* @desc Вариант использования расширенный поиск
	*/
	function modAdvancedSearch($template)
	{
		// поправить запрос
		$sql_first_page_row=db::sql_select("SELECT PAGE_ID,PAGE.DIR_NAME FROM PAGE WHERE PARENT_ID=0 AND SITE_ID='".$env['site_id']."' AND LANG_ID='".$env['lang_id']."'");
		$sql_result_set=db::sql_select("SELECT PAGE.PAGE_ID AS PAGE_ID, PAGE.PARENT_ID AS PARENT_ID, PAGE.TITLE AS NAME, PAGE.PAGE_ORDER AS PAGE_ORDER, PAGE.VERSION AS VERSION, SITE_ID,PAGE.DIR_NAME FROM PAGE WHERE SITE_ID = '".$env['site_id']."' AND LANG_ID='".$env['lang_id']."' AND IS_MENU_PUBLISHED=1 AND VERSION='".$env['version_id']."'");

		//$rs = get_tree("PAGE_ID", $sql_first_page_row[0][0], "PAGE_ORDER",'',0, '', $sql_result_set);
		$rs = get_tree::get($sql_result_set, "PAGE_ID", "PARENT_ID", "PAGE_ORDER");
		
		// создаем массив для списка разделов по которым будет производится поиск
		$search_list=array();
		//print urldecode($this->q_param["ul"]);

		/*marhipov*/
		$cur_deep=-1;
		$cur_path="/{$sql_first_page_row[0]["DIR_NAME"]}/";
		/*marhipov*/

		while($current_row = current($rs)) {
			if ($current_row["TREE_DEEP"]+1 > $this->view_param["tree_deep"]){
				next($rs);
			}else{
				$list_item['name']=str_repeat("&nbsp;&nbsp;", ($current_row["TREE_DEEP"]+1) * 2).$current_row["NAME"];
				//$list_item['URL']='http'.($_SERVER['HTTPS'] == 'on' ? 's' : '').'://'.$_SERVER["SERVER_NAME"].$this->get_url($current_row["PAGE_ID"]);

				/*marhipov*/
				if($cur_deep<$current_row["TREE_DEEP"])
				{
					$cur_path.="{$current_row["DIR_NAME"]}/";
				}
				elseif($cur_deep==$current_row["TREE_DEEP"])
				{
					$cur_path=preg_replace("@/[\w-]+/$@","/{$current_row["DIR_NAME"]}/",$cur_path);
				}
				else
				{
					$regexp="/".str_repeat("[\\w-]+/",$current_row["TREE_DEEP"]+1);
					$regexp="@^({$regexp}).+@";
					$cur_path=preg_replace($regexp,"\\1",$cur_path)."{$current_row["DIR_NAME"]}/";
				}
				$list_item['url']=$this->host_name.$cur_path;
				$cur_deep=$current_row["TREE_DEEP"];
				/*marhipov*/

				if ($list_item['url'] == urldecode($this->q_param["ul"])){
					$list_item["selected"]=1;
				}else{
					$list_item["selected"]=0;
				}
				array_push($search_list,$list_item);
				next($rs);
			}
		}
		
		if ($this->view_param["page"] > 0){
			$path=$this->get_url($this->view_param["page"]);
			$area_id = $this->get_area_id( $this->view_param["page"], $this->env['block_id']);
		}
		else {
			$area_id = $this->env['area_id'];
			$path = $this->get_url($this->env['page_id']);
		}
		
		
		// формируем строку поиска

		$tpl = new smarty_ee_module($this);
		$tpl->assign('all_site',$this->host_name."/");
		$tpl->assign('q',htmlspecialchars($this->q_param["q"]));
		$tpl -> assign ('tarea_id', $area_id);
		$tpl -> assign ('search_page_url', $path);
		$tpl->assign('wf_'.htmlspecialchars($this->q_param["wf"]),1);
		$tpl->assign('ps_'.htmlspecialchars($this->q_param["ps"]),1);
		$tpl->assign('wm_'.htmlspecialchars($this->q_param["wm"]),1);
		$tpl->assign('sp_'.htmlspecialchars($this->q_param["sp"]),1);
		$tpl->assign('sy_'.htmlspecialchars($this->q_param["sy"]),1);
		$tpl->assign('m_'.htmlspecialchars($this->q_param["m"]),1);
		if (urldecode($this->q_param["q"])){
			//ищем, только если задан критерий поиска
			$search_res = $this->get_search_results();
			$tpl->assign('search_result',$search_res);
		}

		$tpl->assign("section_list", $search_list );
		$this->body = $tpl->fetch($template);
	}//function modAdvancedSearch

	#-------------------------------------------------------------------------------------------------------------------------
	function GetSimpleResult()
	{
		return $this->get_search_results();
	}//function GetSimpleResult

	function querier($q)
	{
		return $q;
		//сюда можно добавить предварительную обработку запроса на поиск.
	}

	#-------------------------------------------------------------------------------------------------------------------------

	function ShowMass($arr,$mess='',$release=false) //<= for debug
	{
		if (!$release || $_GET['debug']){
			print "<pre>$mess\n";
			print_r($arr);
			print "\n</pre>";
		}
	}

}
?>