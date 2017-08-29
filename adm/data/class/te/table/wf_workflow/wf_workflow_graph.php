<?PHP

	include_once params::$params["adm_data_server"]["value"]."class/te/tool/autotest/module/check_workflow/test_workflow.php";
	
/**
* Вывод картинки схемы цепочки публикаций
* Сделан с минимальной проверкой ошибкой, необходимо проверять все раньше
* Главная ф-ия - draw
* @package		RBC_Contents_5_0
* @subpackage te
* @todo остались некоторые баги с прошлой версии, желательно поменять полностью алгоритм рисования резолюций
* @copyright	Copyright (c) 2007 RBC SOFT 
*/
	
	class wf_workflow_graph { 
		/**
		* @var object wf_workflow $wf_worjflow_obj Объект цепочки публикаций
		*/
		
		private $wf_workflow_obj;
		
		/**
		* @var int $workflow_id ID цепочки публикаций
		*/
		
		private $workflow_id;
		
		/**
		* @var object auth_privilege $auth_privilege_obj Объект AUTH_PRIVILEGE
		*/
		
		private $auth_privilege_obj;
		
		/**
		* @var object auth_privilege $wf_state_obj Объект WF_STATE
		*/
		
		private $wf_state_obj;
		
		/**
		* @var array $states Массив состояний
		*/
		
		private $states;
		
		/**
		* @var array $resolutions Массив резолюций
		*/
		
		private $resolutions;
		
		/**
		* @var array $privileges Массив привилегий
		*/
				
		private $privileges;
		
		/**
		* @var array $cols Массив колонок
		*/
				
		private $cols;
		
		/**
		* @var array $num_cols Кол-во колонок
		*/
				
		private $num_cols;
		
		/**
		* @var array $deleted_state Удаленное состояние
		*/
		
		private $deleted_state;
		
		/**
		* @var int Наибольная длина колонки состояний
		*/
		
		private $max_order;
		
		/**
		* @var int Расстояние между боксами по x
		*/
		
		private $space_x=140; 
		
		/**
		* @var int Расстояние между боксами по y
		*/
		
		private $space_y=50;
		
		/**
		* @var int Размер шрифта
		*/
		
		private	$font_size=9;
		
		/**
		* @var int $x_offset смещения резолюций по x
		*/
		
		private	$x_offset=15;
		
		/**
		* @var int $x_par_offset смещение параллельных резолюций по x
		*/
		
		private $x_par_offset=5;
		
		/**
		* @var int $y_offset смещения резолюций по y
		*/
		
		private $y_offset=5;
		
		/**
		* @var int $y_par_offset смещение параллельных резолюций по y
		*/

		private $y_par_offset=3;
		
		
		/**
		* @var int $del_offset смещения резолюций по x, если это резолюция удаления
		*/
				
		private $del_offset=8;
		
		
		/**
		* @var string $font Шрифт
		*/
		
		private $font="arial.ttf";
		
		/**
		* @var int $state_x Размер бокса по x
		*/
		
		private $state_x;

		/**
		* @var int $state_y Размер бокса по y
		*/
		
		private $state_y;
		
		/**
		* @var int $legend_x Размер легенды по x
		*/
		
		private $legend_x;
		
		/**
		* @var int $maxx Размер картинки по x
		*/

		private $maxx;

		/**
		* @var int $maxx Размер картинки по y
		*/

		private $maxy;
		
		/**
		* @var object $im Объект GDImage
		*/

		private $im;
		
		/**
		* @var int $bg_color Цвет заднего фона
		*/
		
		private $bg_color;
		
		/**
		* @var int $text_color Цвет текста
		*/
		
		private $text_color;
		
		/**
		* @var object $test_version картинка тестовой версии
		*/
		
		private $test_version;

		/**
		* @var object $work_version картинка рабочей версии
		*/

		private $work_version;
		
		/**
		* @var object $gray_g Рисунок фона серого блока
		*/
		
		private	$gray_g;

		/**
		* @var object $red_g Рисунок фона красного блока
		*/

		private	$red_g;
		
		
		/**
		* @var object $blue_g Рисунок фона синего блока
		*/
		
		private	$blue_g;		
		
		/**
		* @var array $colors Массив цветов для разных прав
		*/
		
		private $colors;
		
		/**
		* @var object $light_colors Массив светлых цветов для разных прав
		*/
		
		private $light_colors;
		
		
		/**
		* Конструктор
		* @var int $workflow_id ID воркфлоу
		*/
		
		public function __construct($workflow_id) {
			$this->wf_workflow_obj = object::factory('WF_WORKFLOW');
			$this->auth_privilege_obj = object::factory('AUTH_PRIVILEGE');
			$this->wf_state_obj = object::factory('WF_STATE');
			$this->workflow_id = $workflow_id;
			$this->path = params::$params["adm_data_server"]["value"]."class/te/table/wf_workflow/";
			$this->font = $this->path.$this->font;
			$this->load_data();
		}


		/**
		* Деструктор
		*/

		public function __destruct() {
			$this->wf_workflow_obj->__destruct();
			$this->auth_privilege_obj->__destruct();
			$this->wf_state_obj->__destruct();
		}


		/**
		* Подгрузка данных
		*/

		private function load_data() {
			$this->states = lib::array_reindex(
				db::sql_select(
					'SELECT WS.*, WES.EDGE_TYPE, WES.LANG_ID AS EDGE_LANG_ID FROM WF_STATE WS LEFT JOIN WF_EDGE_STATE WES ON (WS.WF_STATE_ID=WES.WF_STATE_ID) WHERE WF_WORKFLOW_ID=:workflow_id', 
					array('workflow_id'=>$this->workflow_id)
				), 
				'WF_STATE_ID'
			);
			
			$this->resolutions = lib::array_reindex(
				db::sql_select(
					'SELECT * FROM WF_RESOLUTION WHERE FIRST_STATE_ID IN ('.implode(', ', array_keys($this->states)).')'
				),
				'WF_RESOLUTION_ID'				
			);
			
			$this->privileges = lib::array_reindex(
				db::sql_select(
					'
					SELECT 
						AP.* 
					FROM 
						AUTH_PRIVILEGE AP 
							INNER JOIN
								WF_PRIVILEGE_RESOLUTION PR
									ON (AP.AUTH_PRIVILEGE_ID=PR.AUTH_PRIVILEGE_ID)
					WHERE
						PR.WF_RESOLUTION_ID IN ('.implode(', ', array_keys($this->resolutions)).')
					'
				),
				'AUTH_PRIVILEGE_ID'
			);
		}

		/**
		* Рисование картинки и вывод на экран
		*/
		
		public function draw() {
			$this->prepare_data();
			$this->do_graph();
		}
		
		/**
		* Подготовка данных
		*/
		
		private function prepare_data() {
			$this->set_state_edges();
			$this->set_resolution_privileges();
			$this->sort_cols();
		}
		
		/**
		* Подготовка состояний с учетом крайних 
		*/
		
		private function set_state_edges() {
			$col_c=0;
			$unresolved_states=array();
			$edges = test_workflow::get_edges_for_workflow ($this->workflow_id);
			$col_langs = array();
			$start_states = array();
			$without_lang = '';
			foreach ($edges as $edge) {
				if (($edge['EDGE_TYPE']=='new')) {
					if ($edge['LANG_ID']) {
						$col_langs[$edge['LANG_ID']]=$col_c;
						$start_states[$edge['WF_STATE_ID']]=$edge;
						$col_c++;
					}
					else {
						$without_lang=$edge;
					}
				}
				elseif (($edge['EDGE_TYPE']=='deleted')) {
					$this->states[$edge["WF_STATE_ID"]]["deleted"]=1;
					if ($this->states[$edge["WF_STATE_ID"]]['VERSIONS']=='no_version') {
						$this->states[$edge["WF_STATE_ID"]]["full_deleted"]=1;
						$this->states[$edge["WF_STATE_ID"]]["done"]=1;
						$this->deleted_state=&$this->states[$edge["WF_STATE_ID"]];
					}
				}
			}
			
			if ($without_lang) {
				$this->states[$without_lang["WF_STATE_ID"]]["WITHOUT_LANG"]=1;
				if (in_array($without_lang['WF_STATE_ID'], array_keys($start_states))) {
					$this->states[$without_lang["WF_STATE_ID"]]["WITHOUT_LANG"]=1;
				}
				else {
					$col_langs[$without_lang['LANG_ID']]=$col_c;
					$start_states[]=$without_lang;
				}
			}
			
			foreach ($start_states as $edge) {
				$st=&$this->states[$edge['WF_STATE_ID']];
				if (!$st['done']) {
					$st['done']=1;
					$st['col']=$col_langs[$edge['LANG_ID']];
					$st['LANG_ID']=$edge['LANG_ID'];
					$st['order']=0;
					$this->cols[$st['col']][]=&$st;
				}
			}
			
			foreach ($start_states as $edge) {
				$child_states=test_workflow::get_states_with_langs($edge['WF_STATE_ID'], $edge["LANG_ID"]);
				
				foreach ($child_states as $ch_state) {
					$st=&$this->states[$ch_state['WF_STATE_ID']];
					$ch_state['LANG_ID']=(int)$ch_state['LANG_ID'];
						
					if (!$st['done']) {
						$st['done']=1;
						$st['col']=$col_langs[$ch_state['LANG_ID']];
						$st['LANG_ID']=$ch_state['LANG_ID'];
						$st['order']=sizeof($this->cols[$st['col']]);
						$this->cols[$st['col']][]=&$st;
					}
				}
			}
			
			$this->num_cols=sizeof($this->cols);
		}
		
		/**
		* Подготовка привилегий на резолюции
		*/
		
		private function set_resolution_privileges() {
			foreach (array_keys($this->resolutions) as $resolution_id) {
				$res=lib::array_reindex(db::sql_select('SELECT AUTH_PRIVILEGE_ID FROM WF_PRIVILEGE_RESOLUTION WHERE WF_RESOLUTION_ID=:resolution_id', array('resolution_id'=>$resolution_id)), 'AUTH_PRIVILEGE_ID');
				$this->resolutions[$resolution_id]['PRIVILEGES']=array_keys($res);
			}
		}
		
		/**
		* Сортируем колонки состояний
		*/
		
		private function sort_cols () {
			// Применен метод Монте-Карло, так как полный перебор вариантов в случае длинной цепочки будет занимать время пропорциональное факториалу количества вариантов, а быстрый алгоритм разрабатывать долго
			foreach($this->cols as $k=>$col){
				unset($variants);
				unset($best_variant);
				unset($min_weight);
				for(!$i=0;$i<200;$i++){
					// Готовим вариант
					foreach($col as $k2=>$state){
						if($this->states[$state["WF_STATE_ID"]]["order"]==0){
							$this->cols[$k][$k2]["weight"]=-1000000;
						}
						elseif ($this->states[$state["WF_STATE_ID"]]["VERSIONS"]=="two_versions") {
							$this->cols[$k][$k2]["weight"]=1000000+mt_rand(0,1000);
						}
						else {
							$this->cols[$k][$k2]["weight"]=mt_rand(0,1000);
						}
					}
					uasort($this->cols[$k], array($this,"cmp_for_cols"));
					// Регистрируем последовательность состояний в варианте
					unset($col_states);
					$step=0;
					foreach($this->cols[$k] as $k2=>$state){
						$variants[$i]["path"][$step]=$state["WF_STATE_ID"];
						$col_states[$state["WF_STATE_ID"]]=$step;
						$step++;
					}
					// Вычисляем вес переходов варианта
					$variants[$i]["sum_weight"]=0;
					foreach($this->resolutions as $sg){
						if(isset($col_states[$sg["FIRST_STATE_ID"]]) && isset($col_states[$sg["LAST_STATE_ID"]])){
							$variants[$i]["sum_weight"]+=abs($col_states[$sg["FIRST_STATE_ID"]]-$col_states[$sg["LAST_STATE_ID"]]);
						}
					}
				}
				// Выбираем лучший вариант
				foreach($variants as $i=>$variant){
					if(!isset($min_weight) || $variant["sum_weight"]<$min_weight){
						$best_variant=$variants[$i]["path"];
						$min_weight=$variant["sum_weight"];
					}
				}
				
				// Передаем полученный порядок состояниям
				foreach($best_variant as $k2=>$bv){
					$this->states[$bv]["order"]=$k2;
				}

			}
			// Определяем наибольшую длину колонки состояний
			$this->max_order=0;
			foreach($this->cols as $k=>$col){
				if(count($col)>$this->max_order){
					$this->max_order=count($col);
				}
			}
		}

		/**
		* Сравнение для колонок
		*/
		
		private function cmp_for_cols($a,$b){
			if($a["weight"]<$b["weight"])return -1;
			if($a["weight"]>$b["weight"])return 1;
			return 0;
		}
		
		/**
		* Рисование рисунка
		*/
		
		private function do_graph() {
			$this->calc_common_dimensions();
			$this->set_colors();
			$this->set_privileges_colors();
			$this->calc_states_position();
			$this->calc_resolutions_position();
			
			imagefilledrectangle($this->im, 1, 1, $this->maxx-2, $this->maxy-2, $this->bg_color);
			$this->draw_langs();
			$this->draw_resolutions();
			$this->draw_states();
			$this->draw_legend();
			header('Content-type: image/png');
			imagepng($this->im);
			exit;
		}
		
		/**
		* Вычисление общих размеров
		*/
		
		private function calc_common_dimensions () {
			// Определяем размеры боксов состояний и всего изображения
			$max_title_size=0;
			foreach($this->states as $state){
				$title_size=imagettfbbox($this->font_size,0,$this->font,$this->gd_text($this->wf_state_obj->get_record_title($this->wf_state_obj->primary_key->get_from_record($state))));
				if($max_title_size<$title_size[4]-$title_size[0]){
					$max_title_size=$title_size[4]-$title_size[0];
				}
			}
	
			$this->state_x=$max_title_size+30;
			$this->state_y=70;

			$max_title_size=0;
			
			foreach($this->privileges as $privilege){
				$title_size=imagettfbbox($this->font_size,0,$this->font,$this->gd_text($this->auth_privilege_obj->get_record_title($this->auth_privilege_obj->primary_key->get_from_record($privilege))));
				if($max_title_size<$title_size[4]-$title_size[0]){
					$max_title_size=$title_size[4]-$title_size[0];
				}
			}
			
			$this->legend_x=$max_title_size+115;
			if($this->legend_x<215)$this->legend_x=255;

			$this->maxx=$this->num_cols*($this->state_x+$this->space_x)+$this->legend_x;
	
			if($this->maxx<2*($this->state_x+$this->space_x)+$this->legend_x)
				$this->maxx=2*($this->state_x+$this->space_x)+$this->legend_x;
	
			if($this->num_cols==1)
				$add_for_del=0;
			else
				$add_for_del=1;

			$this->maxy=$this->space_y+($this->max_order+$add_for_del)*($this->state_y+$this->space_y);
			if($this->maxy<$this->space_y*2+135+count($this->privileges)*20+4*40)
				$this->maxy=$this->space_y*2+135+count($this->privileges)*20+4*40;
		}
		
		/**
		* Установка цветов
		*/
		
		private function set_colors() {
			// Инициализируем изображение и задаем цвета
			$this->im=imagecreatetruecolor($this->maxx,$this->maxy);

			
			$this->bg_color=imagecolorallocate($this->im, 255, 255, 255);
			$this->text_color=imagecolorallocate($this->im, 0, 0, 0);

			$color_del=count($this->privileges)/3;
			for($i=0;$i<3*$color_del;$i++){
				if($i<3*$color_del/2){
					$r=200-$i*200/$color_del;
				}else{
					$r=200-abs($i-ceil($color_del)*3)*200/$color_del;
				}
				$r=$this->correct_color($r);
    		
				$g=200-abs($i-ceil($color_del))*200/$color_del;
				$g=$this->correct_color($g);
    		
				$b=200-abs($i-ceil($color_del)*2)*200/$color_del;
				$b=$this->correct_color($b);
    		
				$colors[]=array($r,$g,$b);
			}
			
			foreach($colors as $k=>$color){
				$this->colors[$k]=imagecolorallocate($this->im, $color[0], $color[1], $color[2]);
				$this->light_colors[$k]=imagecolorallocate($this->im, floor(255-(255-$color[0])/2), floor(255-(255-$color[1])/2), floor(255-(255-$color[2])/2));
			}
		}
		
		/**
		* Корректировка цветов
		*/
		
		private function correct_color($color){
			if($color<=0){
				$color=0;
			}else{
				$color+=(200-$color)/2;
			}
			return $color;
		}
		
		/**
		* Установка цветов привилегиям
		*/
		
		private function set_privileges_colors() {
			$color=0;
			foreach($this->privileges as $k=>$privilege){
				$this->privileges[$k]["color"]=$color;
				$color++;
			}
		}
		
		/**
		* Вычисление координат боксов состояний
		*/

		private function calc_states_position() {
			foreach($this->states as $id=>$state){
				if($state["full_deleted"]){
					if($this->num_cols>1){
						$this->states[$id]["x"]=$this->space_x+$this->state_x/2;
						$this->states[$id]["y"]=$this->space_y+$this->max_order*($this->state_y+$this->space_y);
					}else{
						$this->states[$id]["x"]=$this->state_x+$this->space_x*3/2;
						$this->states[$id]["y"]=$this->space_y+($this->max_order-1)*($this->state_y+$this->space_y)/2;
					}
				}else{
					$this->states[$id]["x"]=$this->space_x/2+$state["col"]*($this->state_x+$this->space_x);
					$this->states[$id]["y"]=$this->space_y+$state["order"]*($this->state_y+$this->space_y);
				}
			}
		}
		
		/**
		*  Определяем смещения переходов (чтобы не упирались в одну точку)
		*/
		
		private function calc_resolutions_position() {
			for($i=0;$i<2;$i++){
				foreach($this->resolutions as $ko=>$sgo){
					foreach($this->resolutions as $ki=>$sgi){
						if($ko!=$ki){
							$fxi=$this->states[$sgi["FIRST_STATE_ID"]]["x"];
							$fyi=$this->states[$sgi["FIRST_STATE_ID"]]["y"];
							$lxi=$this->states[$sgi["LAST_STATE_ID"]]["x"];
							$lyi=$this->states[$sgi["LAST_STATE_ID"]]["y"];
    		
							$fxo=$this->states[$sgo["FIRST_STATE_ID"]]["x"];
							$fyo=$this->states[$sgo["FIRST_STATE_ID"]]["y"];
							$lxo=$this->states[$sgo["LAST_STATE_ID"]]["x"];
							$lyo=$this->states[$sgo["LAST_STATE_ID"]]["y"];
    		
							$oyi=$this->resolutions[$ki]["y_offset"];
							$oyo=$this->resolutions[$ko]["y_offset"];
							$oxi=$this->resolutions[$ki]["x_offset"];
							$oxo=$this->resolutions[$ko]["x_offset"];
    		
							$doi=$this->states[$sgi["FIRST_STATE_ID"]]["order"]-$this->states[$sgi["LAST_STATE_ID"]]["order"];
							$doo=$this->states[$sgo["FIRST_STATE_ID"]]["order"]-$this->states[$sgo["LAST_STATE_ID"]]["order"];
    		
							if($this->states[$sgi["LAST_STATE_ID"]]["full_deleted"] && $this->states[$sgo["LAST_STATE_ID"]]["full_deleted"]){
								if($lxi+$this->resolutions[$ki]["x_offset"]==$lxo+$this->resolutions[$ko]["x_offset"]){
									if($this->num_cols==1){
										$angle_func="angle";
									}else{
										$angle_func="angle2";
									}
									if($this->$angle_func($fxi,$fyi,$lxi,$lyi)>$this->$angle_func($fxo,$fyo,$lxo,$lyo)){
										$this->resolutions[$ki]["x_offset"]+=$this->del_offset*sizeof($this->resolutions[$ko]['PRIVILEGES']);
										
									}else{
										$this->resolutions[$ko]["x_offset"]+=$this->del_offset*sizeof($this->resolutions[$ki]['PRIVILEGES']);
									}
								}
							}elseif(!$this->states[$sgi["LAST_STATE_ID"]]["full_deleted"] && !$this->states[$sgo["LAST_STATE_ID"]]["full_deleted"] && $sgi["LAST_STATE_ID"]!=$sgi["FIRST_STATE_ID"] && $sgo["LAST_STATE_ID"]!=$sgo["FIRST_STATE_ID"]){
								if(($lyi+$oyi==$lyo+$oyo || $fyi+$oyi==$fyo+$oyo || $lyi+$oyi==$fyo+$oyo || $lyi+$oyi==$fyo+$oyo) && (abs($doi)>1 || $sgi["LANG_ID"]) && (abs($doo)>1 || $sgo["LANG_ID"])){
									if($lyi+$fyi+$oyi>$lyo+$fyo || ($ko<$ki && $lyi+$fyi+$oyi==$lyo+$fyo)){
										$this->resolutions[$ki]["y_offset"]+=$this->y_par_offset*sizeof($this->resolutions[$ko]['PRIVILEGES'])+$this->y_offset;
									}elseif($lyi+$fyi<$lyo+$fyo){
										$this->resolutions[$ko]["y_offset"]+=$this->y_par_offset*sizeof($this->resolutions[$ki]['PRIVILEGES'])+$this->y_offset;
									}
								}elseif(($lxi+$oxi==$lxo+$oxo) && (abs($doi)==1 && !$sgi["LANG_ID"]) && (abs($doo)==1 && !$sgo["LANG_ID"]) && $this->states[$sgi["LAST_STATE_ID"]]==$this->states[$sgo["LAST_STATE_ID"]] && sizeof($this->resolutions[$ko]['PRIVILEGES'])){
									if($ko<$ki){
										$this->resolutions[$ki]["x_offset"]+=($this->x_par_offset-1)*sizeof($this->resolutions[$ko]['PRIVILEGES'])+$this->x_offset;
									}else{
										$this->resolutions[$ko]["x_offset"]+=($this->x_par_offset-1)*sizeof($this->resolutions[$ki]['PRIVILEGES'])+$this->x_offset;
									}
								}
							}
						}
					}
				}
			}
		}
		
		/**
		* Рисуем названия языков
		*/
		
		private function draw_langs() {
			$lang_obj = object::factory('LANG');
			foreach ($this->cols as $col) {
				$lang_names=array();
				if ($col[0]['WITHOUT_LANG']) {
					$lang_names[] = metadata::$lang['lang_wf_workflow_without_language'];
				}
				if ($col[0]['LANG_ID'])
					$lang_names[] = $lang_obj->get_record_title(array('LANG_ID'=>$col[0]['LANG_ID']));
				imagettftext($this->im, $this->font_size, 0, $col[0]['x'], $col[0]['y']-$this->font_size, $this->text_color, $this->font, $this->gd_text(implode('; ', $lang_names)));
			}
			
			$lang_obj->__destruct();
		}

		/**
		* Рисуем состояния
		*/
		
		private function draw_states() {
			$this->test_version=imagecreatefromgif($this->path."test_version.gif");
			$this->work_version=imagecreatefromgif($this->path."work_version.gif");
			$this->gray_g=imagecreatefromgif($this->path."gray_g.gif");
			$this->red_g=imagecreatefromgif($this->path."red_g.gif");
			$this->blue_g=imagecreatefromgif($this->path."blue_g.gif");
			foreach($this->states as $id=>$state)
				$this->draw_state($this->states[$id]);
		}
		

		/**
		* Рисуем состояние
		* @var array $state Состояние
		*/
		private function draw_state($state){
			if(!isset($state["order"]) && !isset($state["col"]) && !$state["full_deleted"]){
				$x=0;$y=0;
			}else{
				$x=$state["x"];
				$y=$state["y"];
			}
			imagesetthickness($this->im,1);
			$border_color=imagecolorallocate($this->im, 100, 100, 100);
			imageline($this->im,$x+1,$y,$x+$this->state_x-1,$y,$border_color);
			imageline($this->im,$x+1,$y+$this->state_y,$x+$this->state_x-1,$y+$this->state_y,$border_color);
			imageline($this->im,$x,$y+1,$x,$y+$this->state_y-1,$border_color);
			imageline($this->im,$x+$this->state_x,$y+1,$x+$this->state_x,$y+$this->state_y-1,$border_color);
			imagefilledrectangle($this->im, $x+1, $y+1, $x+$this->state_x-1, $y+$this->state_y-1, $this->bg_color);
		
			if($state["EDGE_TYPE"]=='new'){
				$true_g=$this->gray_g;
			}elseif($state["deleted"]){
				$true_g=$this->red_g;
			}else{
				$true_g=$this->blue_g;
			}
			imagecopyresampled($this->im, $true_g, $x+2, $y+2, 0, 0, $this->state_x-2, $this->state_y-2, 20, 67);
		
			if(($state["VERSIONS"]=='test_version') ||($state["VERSIONS"]=='two_versions')){
				imagecopy($this->im, $this->test_version, $x+6, $y+6, 0, 0, 16, 16);
			}
			if($state["VERSIONS"]=='two_versions'){
				imagecopy($this->im, $this->work_version, $x+$this->state_x-16-5, $y+6, 0, 0, 16, 16);
			}
			$title_size=imagettfbbox($this->font_size,0,$this->font,$this->gd_text($this->wf_state_obj->get_record_title($this->wf_state_obj->primary_key->get_from_record($state))));
			$offset=($this->state_x-($title_size[4]-$title_size[0]))/2;
			imagettftext($this->im, $this->font_size, 0, $x+$offset, $y+($this->state_y+$this->font_size)/2, $this->text_color, $this->font, $this->gd_text($this->wf_state_obj->get_record_title($this->wf_state_obj->primary_key->get_from_record($state))));
		}

		/**
		* Риссуем переходы
		*/
		
		private function draw_resolutions() {
			foreach($this->resolutions as $rs)
				$this->draw_resolution($rs);
			
		}
		

		/**
		* Рисуем резолюцию
		* @var array $resolution Резолюция
		*/
		
		private function draw_resolution($resolution){
			for ($priv_i=0;$priv_i<sizeof($resolution["PRIVILEGES"]); $priv_i++) {
				$first=$this->states[$resolution["FIRST_STATE_ID"]];
				$last=$this->states[$resolution["LAST_STATE_ID"]];
				$color_index=$this->privileges[$resolution["PRIVILEGES"][$priv_i]]["color"];
				$color=$this->colors[$color_index];
				$y_start=$this->state_y/4;
				if($last["full_deleted"]){ // deleted
					$num_delete = $this->get_num_full_delete();
					if($this->num_cols==1){
						$x1=$first["x"]+$this->state_x;
						$y1=$first["y"]+$this->state_y/2+$priv_i*$this->y_offset;
						$x2=$last["x"];
						$y2=$last["y"]+$this->state_y/2+$resolution["x_offset"]-($num_delete-1)*$this->del_offset/2+$priv_i*$this->y_offset;
					}else{
						$x1=$first["x"]+$this->state_x/2+$resolution["x_offset"]+$priv_i*$this->del_offset;
						$y1=$first["y"]+$this->state_y;
						$x2=$last["x"]+$this->state_x/2+$resolution["x_offset"]-($num_delete-1)*$this->del_offset/2+$priv_i*$this->del_offset;
						$y2=$last["y"];
					}
					

					$this->set_style("deleted",$color_index);
					imageline($this->im, $x1, $y1, $x2, $y2, IMG_COLOR_STYLED);
					$this->draw_arrow($x2,$y2,$this->angle($x1, $y1, $x2, $y2),$this->light_colors[$color_index]);
					if ((sizeof($resolution['PRIVILEGES'])>1)&&($priv_i==(sizeof($resolution['PRIVILEGES'])-1))) {
						if ($this->num_cols==1) {
							if ($x1>$x2) 
								$x1-=15;
							imagefilledrectangle($this->im, $x1, $y1-$priv_i*$this->y_offset-5, ($x2<$x1)?$x1-15:$x1+15, $y1+5, $this->bg_color);
							imagerectangle($this->im, $x1, $y1-$priv_i*$this->y_offset-6, ($x2<$x1)?$x1-15:$x1+15, $y1+4, imagecolorallocate($this->im, 0, 0, 0));
							$quorum_size=imagettfbbox($this->font_size,0,$this->font,$this->gd_text($resolution['QUORUM']));
							imagettftext($this->im, $this->font_size, 0, $x1+((15-$quorum_size[2])/2), $y1-($priv_i*$this->y_offset-6)/2, $this->text_color, $this->font, $this->gd_text($resolution['QUORUM']));
						}
						else {
							imagefilledrectangle($this->im, $x1-$priv_i*$this->del_offset-2, $y1, $x1+2, $y1+12, $this->bg_color);
							imagerectangle($this->im, $x1-$priv_i*$this->del_offset-2, $y1, $x1+2, $y1+12, imagecolorallocate($this->im, 0, 0, 0));
							$quorum_size=imagettfbbox($this->font_size, 0, $this->font, $this->gd_text($resolution['QUORUM']));

							imagettftext ($this->im, $this->font_size, 0, $x1+$x1-$x1-$priv_i*$this->del_offset/2-2, $y1+(12+$this->font_size)/2, $this->text_color, $this->font, $this->gd_text($resolution['QUORUM']));
						}
					}
					
				}
				elseif($resolution["LANG_ID"]){ // translate
					if($first["col"]<$last["col"]){
						$xf=$this->state_x;
						$xl=0;
					}else{
						$xf=0;
						$xl=$this->state_x;
					}
					$x1=$first["x"]+$xf;
					$y1=$first["y"]+$y_start+$resolution["y_offset"]+$priv_i*$this->y_offset;
					$x2=$last["x"]+$xl;
					$y2=$last["y"]+$y_start+$resolution["y_offset"]+$priv_i*$this->y_offset;
					
		  	
					$this->set_style("translate",$color_index);
					imageline($this->im, $x1, $y1, $x2, $y2, IMG_COLOR_STYLED);
					$this->draw_arrow($x2,$y2,$this->angle($x1, $y1, $x2, $y2),$color);
					if ((sizeof($resolution['PRIVILEGES'])>1)&&($priv_i==(sizeof($resolution['PRIVILEGES'])-1))) {
						if ($x1>$x2) 
							$x1-=15;
						imagefilledrectangle($this->im, $x1, $y1-$priv_i*$this->y_offset-5, $x1+15, $y1+5, $this->bg_color);
						imagerectangle($this->im, $x1, $y1-$priv_i*$this->y_offset-6, $x1+15, $y1+4, imagecolorallocate($this->im, 0, 0, 0));
						$quorum_size=imagettfbbox($this->font_size,0,$this->font,$this->gd_text($resolution['QUORUM']));
						imagettftext($this->im, $this->font_size, 0, $x1+((15-$quorum_size[2])/2), $y1-($priv_i*$this->y_offset-6)/2, $this->text_color, $this->font, $this->gd_text($resolution['QUORUM']));
					}
				}
				elseif ($first!=$last){ // normal дальние
					if(abs($first["order"]-$last["order"])>1){
						$y_size=abs($first["y"]-$last["y"]);
						$x_size=60/sqrt(($this->state_y+$this->space_y)/abs($first["y"]-$last["y"]));
						if($first["order"]<$last["order"]){
							$a1=90;
							$a2=270;
							$arrow_angle=-15;
							$arrow_x=$last["x"];
							$center_x=$first["x"];
						}else{
							$a1=270;
							$a2=90;
							$arrow_angle=165;
							$arrow_x=$last["x"]+$this->state_x;
							$center_x=$first["x"]+$this->state_x;
							
						}
						$y1=$first["y"]+$y_start+$resolution["y_offset"]+$priv_i*$this->y_par_offset;
						$this->set_style("normal",$color_index);
						imagearc($this->im,$center_x,($first["y"]+$last["y"])/2+$y_start+$resolution["y_offset"]+$priv_i*$this->y_par_offset,$x_size,$y_size,$a1,$a2, IMG_COLOR_STYLED);
						$this->draw_arrow($arrow_x,$last["y"]+$y_start+$resolution["y_offset"]+$priv_i*$this->y_par_offset,$arrow_angle/360*2*pi(),$color);
						
						if ((sizeof($resolution['PRIVILEGES'])>1)&&($priv_i==(sizeof($resolution['PRIVILEGES'])-1))) {
							if ($first["order"]<$last["order"]) 
								$center_x-=15;
							imagefilledrectangle($this->im, $center_x, $y1-$priv_i*$this->y_par_offset, $center_x+15, $y1+5, $this->bg_color);
							imagerectangle($this->im, $center_x, $y1-$priv_i*$this->y_par_offset, $center_x+15, $y1+5, imagecolorallocate($this->im, 0, 0, 0));
							$quorum_size=imagettfbbox($this->font_size,0,$this->font,$this->gd_text($resolution['QUORUM']));
							imagettftext($this->im, $this->font_size, 0, $center_x+((15-$quorum_size[2])/2), 3+$y1-($priv_i*$this->y_par_offset-6)/2, $this->text_color, $this->font, $this->gd_text($resolution['QUORUM']));
						}

					}
					else { // normal соседние
						if($first["order"]<$last["order"]){
							$x=$first["x"]+$this->x_offset+$resolution["x_offset"]+$priv_i*$this->x_par_offset;
							$y1=$first["y"]+$this->state_y;
							$y2=$last["y"];
							$arrow_angle=-90;
						}else{
							$x=$first["x"]+$this->state_x-$this->x_offset-$resolution["x_offset"]+$priv_i*$this->x_par_offset;
							$y1=$first["y"];
							$y2=$last["y"]+$this->state_y;
							$arrow_angle=90;
						}
						$this->set_style("normal",$color_index);
						imageline($this->im, $x, $y1, $x, $y2, IMG_COLOR_STYLED);
						$this->draw_arrow($x,$y2,$arrow_angle/360*2*pi(),$color);
						
						if ((sizeof($resolution['PRIVILEGES'])>1)&&($priv_i==(sizeof($resolution['PRIVILEGES'])-1))) {
							if ($y1>$y2) 
								$y1-=12;

							imagefilledrectangle($this->im, $x-$priv_i*$this->x_par_offset-2, $y1, $x+2, $y1+12, $this->bg_color);
							imagerectangle($this->im, $x-$priv_i*$this->x_par_offset-2, $y1, $x+2, $y1+12, imagecolorallocate($this->im, 0, 0, 0));
							$quorum_size=imagettfbbox($this->font_size, 0, $this->font, $this->gd_text($resolution['QUORUM']));

							imagettftext ($this->im, $this->font_size, 0, $x+-$priv_i*$this->x_par_offset/2-2, $y1+(12+$this->font_size)/2, $this->text_color, $this->font, $this->gd_text($resolution['QUORUM']));
						}
					}
				}
				else{ // публикация изменений и отмена
					$arc_d=40+$priv_i*$this->x_offset+$resolution["x_offset"];
					if($resolution["MAIN_VERSION"]==1){
						$center_x=$first["x"]+$this->state_x/4;
						$arrow_x=$center_x+$arc_d/2;
						$arrow_angle=0.4*pi();
						$x=$center_x-$arc_d/2+$priv_i*$this->x_offset/2;
					}
					else{
						$center_x=$first["x"]+$this->state_x*3/4;
						$arrow_x=$center_x-$arc_d/2;
						$arrow_angle=0.6*pi();
						$x=$center_x+$arc_d/2;
					}
					$this->set_style("change",$color_index);
					imagearc($this->im,$center_x,$first["y"]+$this->state_y,$arc_d,$arc_d,0,180, IMG_COLOR_STYLED);
					$this->draw_arrow($arrow_x,$first["y"]+$this->state_y,$arrow_angle,$this->light_colors[$color_index]);
					
					$y1=$first['y']+$this->state_y;
					if ((sizeof($resolution['PRIVILEGES'])>1)&&($priv_i==(sizeof($resolution['PRIVILEGES'])-1))) {
						imagefilledrectangle($this->im, $x-$priv_i*$this->x_offset/2-2, $y1, $x+2, $y1+12, $this->bg_color);
						imagerectangle($this->im, $x-$priv_i*$this->x_offset/2-2, $y1, $x+2, $y1+12, imagecolorallocate($this->im, 0, 0, 0));
						$quorum_size=imagettfbbox($this->font_size, 0, $this->font, $this->gd_text($resolution['QUORUM']));

						imagettftext ($this->im, $this->font_size, 0, $x-$priv_i*$this->x_offset/4-2, $y1+(12+$this->font_size)/2, $this->text_color, $this->font, $this->gd_text($resolution['QUORUM']));
					}
					
				}
			}
		}
		
		/**
		* Вычисление колва полных удалений
		*/
		
		private function get_num_full_delete() {
			$ret=0;
			foreach ($this->resolutions as $rs) {
				if ($this->states[$rs['LAST_STATE_ID']]['full_deleted'])
					$ret+=sizeof($rs['PRIVILEGES']);
			}
			return $ret;
		}
		
		
		/**
		* Рисование линии
		* @var int $x x
		* @var int $y y
		* @var int $angle угол
		* @var int $color цвет
		*/
		
		private function draw_arrow($x,$y,$angle,$color){
			$angle=$angle*360/2/pi()-90;
			$mah=30;
			$len=6;
			$nx=$x+sin(($angle+$mah)/360*2*pi())*$len;
			$ny=$y+cos(($angle+$mah)/360*2*pi())*$len;
			$nx2=$x+sin(($angle-$mah)/360*2*pi())*$len;
			$ny2=$y+cos(($angle-$mah)/360*2*pi())*$len;
			imagefilledpolygon($this->im,array($x,$y,$nx,$ny,$nx2,$ny2),3,$color);
		}
		
		/**
		* Установка стиля резолюции
		* @var string $mode тип
		* @var int $color_index индекс цвета из colors или light_colors
		*/
		
		private function set_style($mode,$color_index){
			if($mode=="deleted"){
				$color=$this->light_colors[$color_index];
				$style=array($color,$color,$color,$this->bg_color,$this->bg_color);
				imagesetthickness($this->im,1);
				imagesetstyle($this->im,$style);
			}
			elseif($mode=="translate"){
				$color=$this->colors[$color_index];
				$style=array($color,$color,$color,$color,$color,$color,$color,$this->bg_color,$this->bg_color);
				imagesetthickness($this->im,1);
				imagesetstyle($this->im,$style);
			}
			elseif($mode=="normal"){
				$color=$this->colors[$color_index];
				$style=array($color,$color,$color);
				imagesetthickness($this->im,1);
				imagesetstyle($this->im,$style);
			}
			elseif($mode=="change"){
				$color=$this->light_colors[$color_index];
				$style=array($color,$color);
				imagesetthickness($this->im,1);
				imagesetstyle($this->im,$style);
			}
		}

		/**
		* вычисляет угол
		* @var int $x1 начальный x
		* @var int $y1 начальный y
		* @var int $x2 конечный x
		* @var int $x2 конечный y
		* @var double угол
		*/
		
		private function angle($x1,$y1,$x2,$y2){
			$angle=asin(($y1-$y2)/sqrt(($x1-$x2)*($x1-$x2)+($y1-$y2)*($y1-$y2)));
			if($x2-$x1<0){
				$angle=pi()-$angle;
			}
			return $angle;
		}
		
		/**
		* вычисляет угол 2-ой способ
		* @var int $x1 начальный x
		* @var int $y1 начальный y
		* @var int $x2 конечный x
		* @var int $x2 конечный y
		* @var double угол
		*/
		
		private function angle2($x1,$y1,$x2,$y2){
			$angle=asin(($y1-$y2)/sqrt(($x1-$x2)*($x1-$x2)+($y1-$y2)*($y1-$y2)));
			if($x2-$x1<0){
				$angle=pi()-$angle;
			}
			if($angle<0)$angle=-pi()/2-$angle;
			else $angle=3*pi()/2-$angle;
			return $angle;
		}
		
		/**
		* Обработка текста для вывода
		* @var string $string строка
		* @return $string
		*/
		
		private function gd_text($string){
			if (params::$params["encoding"]["value"] == "utf-8") return $string;
			$isostring = convert_cyr_string($string, "w", "i");
			$index = strlen($isostring);
		    for ($i=0; $i < $index; $i++){
				$char = substr($isostring,$i,1);
				$charcode = ord($char);
				$unistring.=($charcode>175) ? "&#" . (1040+($charcode-176)). ";" : $char;
			}
			return $unistring;
		}


		/**
		* Рисуем легенду
		*/
		
		private function draw_legend() {
			$c=0;
			$left_x=$this->maxx-$this->legend_x;
			imagefilledrectangle($this->im, $left_x, $this->space_y, $left_x+$this->legend_x-40, $this->space_y+25, imagecolorallocate($this->im,206,221,238));
			imagettftext($this->im, $this->font_size, 0, $left_x+15, $this->space_y+13+$this->font_size/2, $this->text_color, $this->font, $this->gd_text(metadata::$lang['lang_wf_workflow_LEGEND'].":"));
		
			imagefilledrectangle($this->im, $left_x, $this->space_y+25, $left_x+$this->legend_x-40, $this->space_y+40+count($this->privileges)*20, imagecolorallocate($this->im,250,250,250));
			foreach($this->privileges as $k=>$privilege){
				$yo=$this->space_y+40+$c*20;
				imageline($this->im, $left_x+15, $yo+6, $left_x+45, $yo+6, $this->light_colors[$privilege["color"]]);
				$this->draw_arrow($left_x+45, $yo+6,0,$this->light_colors[$privilege["color"]]);
				imageline($this->im, $left_x+15, $yo, $left_x+45, $yo, $this->colors[$privilege["color"]]);
				$this->draw_arrow($left_x+45, $yo,0,$this->colors[$privilege["color"]]);
				imagettftext($this->im, $this->font_size, 0, $left_x+55, $yo+3+$this->font_size/2, $this->text_color, $this->font, $this->gd_text(($this->auth_privilege_obj->get_record_title($this->auth_privilege_obj->primary_key->get_from_record($privilege)))));
				$c++;
			}
		
			imagefilledrectangle($this->im, $left_x, $this->space_y+40+count($this->privileges)*20, $left_x+$this->legend_x-40, $this->space_y+45+count($this->privileges)*20, imagecolorallocate($this->im,229,229,229));
		
			$styles=array("normal"=>metadata::$lang['lang_wf_workflow_resolutions_in_one']."\n".metadata::$lang['lang_wf_workflow_language_version'],"translate"=>metadata::$lang['lang_wf_workflow_translate_onto_another_language'],"change"=>metadata::$lang['lang_wf_workflow_publishing']."\n".metadata::$lang['lang_wf_workflow_revert_changes'],"deleted"=>metadata::$lang['lang_wf_workflow_deleting']);
			imagefilledrectangle($this->im, $left_x, $this->space_y+45+count($this->privileges)*20, $left_x+$this->legend_x-40, $this->space_y+45+count($this->privileges)*20+4*30, imagecolorallocate($this->im,250,250,250));
			foreach($styles as $style=>$name){
				$this->set_style($style,0);
				$yo=$this->space_y+60+$c*20+$c2*30;
				imageline($this->im, $left_x+15, $yo, $this->maxx-$this->legend_x+45, $yo, IMG_COLOR_STYLED);
				if($style=="deleted" || $style=="change"){
					$color=$this->light_colors[0];
				}else{
					$color=$this->colors[0];
				}
				$this->draw_arrow($left_x+45, $yo,0,$color);
				imagettftext($this->im, $this->font_size, 0, $left_x+55, $yo+$this->font_size/2, $this->text_color, $this->font, $this->gd_text($name));
				$c2++;
			}
			
			$workflow_data=$this->wf_workflow_obj->get_change_record(array('WF_WORKFLOW_ID'=>$this->workflow_id));
			
			$states = array (
				'new' => array ( 
					'color' => $this->gray_g, 
					'message' => metadata::$lang['lang_wf_workflow_initial_state']
				),
				'deleted' => array (
					'color' => $this->red_g, 
					'message' => metadata::$lang['lang_wf_workflow_after_delete_state'],
				),
				'one_version' => array (
					'color' => $this->blue_g,
					'message' => metadata::$lang['lang_wf_workflow_common_state'],
				),
			);
			

			imagefilledrectangle($this->im, $left_x, $this->space_y+45+count($this->privileges)*20+4*30, $left_x+$this->legend_x-40, $this->space_y+50+count($this->privileges)*20+4*30, imagecolorallocate($this->im,229,229,229));
			imagefilledrectangle($this->im, $left_x, $this->space_y+50+count($this->privileges)*20+4*30, $left_x+$this->legend_x-40, $this->space_y+105+count($this->privileges)*20+4*30, imagecolorallocate($this->im,250,250,250));
			imagesetthickness($this->im,1);
			$border_color=imagecolorallocate($this->im, 100, 100, 100);
			$left_st = $left_x+15;
			
			foreach ($states as $state) {
				$yo=$this->space_y+60+$c*20+$c2*30+$c3*20;
				imageline ($this->im, $left_st+1, $yo, $left_st+29, $yo, $border_color);
				imageline ($this->im, $left_st+1, $yo+10, $left_st+29,$yo+10, $border_color);
				imageline ($this->im, $left_st, $yo+1, $left_st, $yo+9, $border_color);
				imageline ($this->im, $left_st+30, $yo+1, $left_st+30, $yo+9, $border_color);
				imagefilledrectangle ($this->im, $left_st+1, $yo+1, $left_st+29, $yo+9, $this->bg_color);
				imagecopyresampled ($this->im, $state['color'], $left_st+2, $yo+2, 0, 0, 28, 8, 20, 67);
				imagettftext($this->im, $this->font_size, 0, $left_x+55, $yo+5+$this->font_size/2, $this->text_color, $this->font, $this->gd_text($state['message']));
				$c3++;
			}
			
			if ($workflow_data['WORKFLOW_TYPE']=='use_versions') {
				imagefilledrectangle($this->im, $left_x, $this->space_y+60+$c*20+$c2*30+$c3*20, $left_x+$this->legend_x-40, $this->space_y+60+$c*20+$c2*30+$c3*20+4, imagecolorallocate($this->im,229,229,229));
				imagefilledrectangle($this->im, $left_x, $this->space_y+60+$c*20+$c2*30+$c3*20+4, $left_x+$this->legend_x-40, $this->space_y+120+$c*20+$c2*30+$c3*20, imagecolorallocate($this->im,250,250,250));
				imagecopy($this->im, $this->test_version, $left_x+25,  $this->space_y+60+$c*20+$c2*30+$c3*20+14, 0, 0, 16, 16);
				imagettftext($this->im, $this->font_size, 0, $left_x+55, $this->space_y+82+$c*20+$c2*30+$c3*20+$this->font_size/2, $this->text_color, $this->font, $this->gd_text(metadata::$lang['lang_test_version']));
				imagecopy($this->im, $this->work_version, $left_x+25, $this->space_y+60+$c*20+$c2*30+$c3*20+34, 0, 0, 16, 16);
				imagettftext($this->im, $this->font_size, 0, $left_x+55, $this->space_y+102+$c*20+$c2*30+$c3*20+$this->font_size/2, $this->text_color, $this->font, $this->gd_text(metadata::$lang['lang_work_version']));
			}
		}

	}
?>