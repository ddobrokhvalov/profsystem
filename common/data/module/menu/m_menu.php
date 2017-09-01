<?php
/**
 * Модуль "Главное меню"
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2007 RBC SOFT
 * @todo Сделать поддержку разделения доступа
 * @todo Переделать с общего сбора вспомогательных массивов на спецсбор у каждого варианта использования. Цель - повышение производительности, экономия памяти (к карте сайта не относится, там по прежнему будет использоваться максимум ресурсов)
 * @todo Принять решение по поводу возможности строить дочерние уровни, даже если кто-то из их родителей не имеет публикации в меню
 * @todo Нужно ли выводить ссылки на "карте сайта"?
 */
class m_menu extends module{

	/**
	 * Объект шаблона модуля
	 * @var object smarty_ee_module
	 */
	protected $tpl;

	/**
	 * Массив, по которому можно собирать разделы ВВЕРХ по иерархии (разделы, переиндексированные по идентификаторам)
	 * @var array
	 */
	protected $page_id_array;

	/**
	 * Массив, по которому можно собирать разделы ВНИЗ по иерархии (списки разделов, сгруппированные по идентификаторам родителей)
	 * @var array
	 */
	protected $parent_id_array;

	/**
	 * Массив, который содержит записи разделов от текущего до корня (с ключами равными идентификатору раздела)
	 * @var array
	 */
	protected $up_path;

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Дополняем хэш кэша идентификатором страницы, чтобы менюшка на разных страницах была разной
	 * 
	 * @return string
	 */
	protected function ext_get_hash_code(){
		return $this->env["page_id"]."|".$_COOKIE["client_id"]."|".$_COOKIE["client_key"];
	}

	/**
	 * Диспетчер модуля
	 */
	protected function content_init(){
		// Настройка view_mode
		$view_mode=$this->view_param["view_mode"];
		if($view_mode!="current_level" && $view_mode!="child_levels" && $view_mode!="status" && $view_mode!="map"){
			$view_mode="top_levels";
		}
		$this->view_param["view_mode"]=$view_mode;
		// Сборка готовой менюшки
		$this->prepare_raw_data();
		$this->tpl=new smarty_ee_module($this);
		$method="vm_".$view_mode;
		$this->tpl->assign("ITEMS", $this->$method());
		$this->body=$this->tpl->fetch($this->tpl_dir.$view_mode.".tpl");
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Вариант использования "Верхние уровни"
	 */
	protected function vm_top_levels(){
		return $this->get_level_array($this->parent_id_array[0][0]["PAGE_ID"], 1, "/".$this->parent_id_array[0][0]["DIR_NAME"]."/");
	}

	/**
	 * Вариант использования "Текущий уровень"
	 */
	protected function vm_current_level(){
		return $this->get_level_array($this->page_id_array[$this->env["page_id"]]["PARENT_ID"], 1, $this->up_path[$this->env["page_id"]]["_PARENT_PATH"]);
	}

	/**
	 * Вариант использования "Дочерние уровни"
	 */
	protected function vm_child_levels(){
		$ret = $this->get_level_array($this->env["page_id"], 1, $this->up_path[$this->env["page_id"]]["_FULL_PATH"]);
		/*print_r("<pre>");
		print_r($ret);
		print_r("</pre>");*/
		return $ret;
	}

	/**
	 * Вариант использования "Статусная строка"
	 */
	protected function vm_status(){
		// Считаем длину первого и последнего пункта
		$reverted_up_path=array_reverse($this->up_path,	true);
		$main_page=current($reverted_up_path);
		$length=mb_strlen($this->page_id_array[$this->env["page_id"]]["TITLE"], params::$params["encoding"]["value"])+
			mb_strlen($main_page["TITLE"], params::$params["encoding"]["value"]);
		// Считаем длину промежуточных пунктов и определяем, помещаются	они	или	нет
		$i=0;
		foreach($reverted_up_path as $k=>$rup){
			if($i++>0 && $i<count($this->up_path)){
				$length+=mb_strlen($this->page_id_array[$k]["TITLE"], params::$params["encoding"]["value"]);
				if($length<=$this->view_param["status_length"]){
					$possible[$k]=true;
				}
			}else{
				$possible[$k]=true;
			}
		}
		// Собираем	готовые	пункты,	в том числе	затычку, если что-то не	влезло
		foreach($reverted_up_path as $k=>$rup){
			if($possible[$k]){
				$items[]=array("URL"=>$rup["_FULL_PATH"])+$rup;
			}elseif(!$shorted){
				$items[]=array("TITLE"=>"..");
				$shorted=1;
			}
		}
		return $items;
	}

	/**
	 * Вариант использования "Карта сайта"
	 */
	protected function vm_map(){
		return $this->get_level_array($this->parent_id_array[0][0]["PAGE_ID"], 1,  "/".$this->parent_id_array[0][0]["DIR_NAME"]."/");
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Рекурсивный обход {@link m_menu::$parent_id_array} для сбора уровней меню
	 *
	 * @param int $page_id				идентификатор родителя обрабатываемого уровня
	 * @param int $level				глубина обрабатываемого уровня (нужна для сравнения с максимально допустимой глубиной)
	 * @param string $parent_directory	директория родительского уровня
	 * 
	 */
	protected function get_level_array($page_id, $level, $parent_directory){
		if(is_array($this->parent_id_array[$page_id])){
			foreach($this->parent_id_array[$page_id] as $item){
				$is_link=$item["PAGE_TYPE"]=="link";
				$item["URL"]=($item["URL"] ? $item["URL"] : $parent_directory.$item["DIR_NAME"]."/");
				$item["SELECTED"]=is_array($this->up_path[$item["PAGE_ID"]]);
				$item["CURRENT"]=$item["PAGE_ID"]==$this->env["page_id"];
				// Детей разрешается иметь выделенному разделу (кроме "текущего уровня") или любому разделу в случае "дочерних уровней" или "карты сайта", но только в том случае, если не превышена глубина из соответствующего параметра представления
				$show_children=($item["SELECTED"] || $this->view_param["view_mode"]=="child_levels" || $this->view_param["view_mode"]=="map") && $this->view_param["view_mode"]!="current_level";
				if($show_children && $level<$this->view_param["depth"] && is_array($this->parent_id_array[$item["PAGE_ID"]])) {
					$item["CHILDREN"]=$this->get_level_array($item["PAGE_ID"], $level+1, $parent_directory.$item["DIR_NAME"]."/");
				}
				// Ссылки не выводятся в карте сайта. Папки в меню выводятся только те, что имеют опубликованных в меню детей
				if($item["PAGE_TYPE"]=="page" || ($item["PAGE_TYPE"]=="link" && $this->view_param["view_mode"]!="map") || ($item["PAGE_TYPE"]=="folder" && is_array($this->parent_id_array[$item["PAGE_ID"]]))){
					$level_array[]=$item;
				}
			}
		}
		return $level_array;
	}

	/**
	 * Сбор вспомогательных массивов, из которых варианты использования будут собирать свои менюшки
	 */
	protected function prepare_raw_data(){ 
		// В статусную строку выбираются даже разделы без публикации в меню
		if($this->view_param["view_mode"]!="status"){
			$only_published_clause="AND (IS_MENU_PUBLISHED=1 OR PARENT_ID=0)";
		}
		$pages=db::sql_select("SELECT * FROM PAGE WHERE PAGE.SITE_ID=:site_id AND PAGE.VERSION=:version AND PAGE.LANG_ID=:lang_id {$only_published_clause} ORDER BY PAGE.PAGE_ORDER", array("site_id"=>$this->env["site_id"], "version"=>$this->env["version"], "lang_id"=>$this->env["lang_id"]));
		if ( params::$params["protected_access"]["value"] ){
			include_once(params::$params["common_data_server"]["value"]."cms_class/client_access.php");
			$is_registrated_client=client_access::check_autorization();
		}
		foreach($pages as $pgs){
			// Проверка прав пользователя на раздел
	    	if(params::$params["protected_access"]["value"] && $pgs["IS_PROTECTED"] && $this->view_param["view_mode"]!="status"){
				if($is_registrated_client){
					$rights=client_access::check_rights_on_page($pgs["PAGE_ID"],$this->env["version"],$this->env["site_id"],$this->env["lang_id"]);
				}else{
					$rights=false;
				}
			}else{
				$rights=true;
			}
			if($rights){
				// Массивы для движения соответственно вверх и вниз по иерархии
				$this->page_id_array[$pgs["PAGE_ID"]]=$pgs;
				$this->parent_id_array[$pgs["PARENT_ID"]][]=$pgs;
			}
		}
		// Собираем список разделов от текущего до корня
		$cur_id=$this->env["page_id"];
		$this->up_path[$cur_id]=$this->page_id_array[$cur_id];
		while($this->page_id_array[$cur_id]["PARENT_ID"]!=0){
			$cur_id=$this->page_id_array[$cur_id]["PARENT_ID"];
			$this->up_path[$cur_id]=$this->page_id_array[$cur_id];
		}
		// Собираем полный путь для каждого из разделов в $this->up_path (а также путь до родителя)
		$reverted_up_path=array_reverse($this->up_path);
		$cur_path="/";
		foreach($reverted_up_path as $rup){
			$this->up_path[$rup["PAGE_ID"]]["_PARENT_PATH"]=$cur_path;
			$this->up_path[$rup["PAGE_ID"]]["_FULL_PATH"]=$cur_path.=$rup["DIR_NAME"]."/";
		}
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Редирект на первый опубликованный в меню дочерний раздел с типом "page", если такой существует
	 *
	 * Этот метод работает вне обычного контекста модуля - он пользуется только своими параметрами.
	 * Если ничего подходящего среди детей не окажется, то будет вызвана ошибка 404 (по возможности красивая)
	 *
	 * @param int $page_id				идентификатор раздела, из которого нужно перейти ниже
	 * @param int $version				версия раздела, из которогу нужно перейти ниже
	 * @todo учесть то, что под модреврайтом в случае проваливания из главной страницы должен формироваться не относительный, а абсолютный путь
	 */
	public function go_to_next_level($page_id, $version){
		// Директория сайта и языка
		$site_info=db::sql_select("
			SELECT SITE.*, LANG.ROOT_DIR FROM PAGE, SITE, LANG
			WHERE PAGE.PAGE_ID=:page_id
				AND PAGE.VERSION=:version
				AND PAGE.SITE_ID=SITE.SITE_ID
				AND PAGE.LANG_ID=LANG.LANG_ID
		", array("page_id"=>$page_id, "version"=>$version));
		// Первый опубликованный ребенок
		$child=db::sql_select("
			SELECT * FROM PAGE
			WHERE PARENT_ID=:page_id AND VERSION=:version AND IS_MENU_PUBLISHED=1 AND PAGE_TYPE='page'
			ORDER BY PAGE.PAGE_ORDER LIMIT 0, 1
		", array("page_id"=>$page_id, "version"=>$version));
		// Если ребенок есть, то перенаправляем на него, иначе ошибка 404
		if($child[0]["PAGE_ID"]){
			header("Location: ".$child[0]["DIR_NAME"]."/index.php");
		}else{
			header("HTTP/1.0 404 Not Found");
			$site_path=($version ? $site_info[0]["TEST_PATH"] : $site_info[0]["PATH"]);
			$page_404=$site_path.$site_info[0]["ROOT_DIR"]."/errors/404/index.php";
			if(file_exists($page_404)){
				global $bench; // Обеспечиваем видимость этого объекта в локальном контексте, чтобы сохранить работоспособность подключаемой страницы
				include_once($page_404);
			}
		}
		exit();
	}
}
?>
