<?PHP
/**
 * Модуль статистики, выводит в интерфейсе RBC Contents статистику, созданную программой awstats
 *
 * @package		RBC_Contents_5_0
 * @subpackage app
 * @copyright	Copyright (c) 2008 RBC SOFT
*/

class awstats extends tool {
	
	/**
	* Собирается информация об ошибках
	* @var string
	*/
	private $error='';
	
	/**
	* Путь к дистрибутиву AwStats, устанавливается в конструкторе
	* @var string
	*/
	
	private $awstats_path;
	
	/**
	* Основной метод
	*/
	
	public function action_index () {
		$this->awstats_path = params::$params['common_data_server']['value'].'external_soft/awstats/distrib/';
		
		$this->clear_status();

		if (!$_GET['_f_year']) $_GET['_f_year']=date('Y');
		if (!$_GET['_f_month']) $_GET['_f_month']=intval(date('m'));
		if (!$_GET['_f_config']) $_GET['_f_config']=$this->get_config();
		
		
		$this -> title = metadata::$objects[$this -> obj]['title'];
		$tpl = new smarty_ee( metadata::$lang);
		$tpl -> assign( 'title', $this -> get_title() );
		$tpl -> assign( 'filter', html_element::html_filter($this, false, false, null, true ) );
		$tpl -> assign( 'table',  $this->get_data($_GET['_f_month'], $_GET['_f_year'], $_GET['_f_config'], $_GET['filemode']));
		$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_grid.tpl' );

		if ($this->error) 
			throw new Exception($this->error);
	}
	
	
	/**
	* Очистка сессии
	*/
	
	private function clear_status() {
	}
	

	/**
	* Возвращает сайт по умолчанию
	* @todo Перевести fdir в параметры класса
	* @todo Переделать на стандартную обработку директорий
	*/
	
	private function get_config ($site='') {
		$fdir=$this->awstats_path.'wwwroot/cgi-bin/';
		if (@$dh = opendir($fdir))
		{
			while (($file = readdir($dh)) !== false) {
				if (preg_match('~awstats\\.(.+)\\.conf~',$file,$mathes) && $file!='awstats.model.conf' && $file!='awstats.site1.conf') {
					if ("awstats.$site.conf"==$file)
					{
						return $site;
					}
					elseif ($site=='') {
						return $mathes[1];
					}
				}
			}
			closedir($dh);
		}
		else {
			$this->error.="\n".metadata::$lang['lang_awstats_can_not_open_dir'].' '.$fdir;
			return false;
		}
		$this->error.="\n".metadata::$lang['lang_awstats_can_not_find_any_conf_file'];
		return false;		
	}


	/**
	* Здесь он нам нужен только для того, чтобы получить поля для фильтра
	* @see object::get_form_fields
	*/
	
	public function get_form_fields($mode, $field_prefix, $record="", $record_prefix="", $fields="", $escape=true) {
		$fields = array (
			'month' => array (
				'type' => 'select1',
				'title' => metadata::$lang['lang_awstats_month'],
				'value_list' => array (
					array ('value'=>'01', 'title'=>metadata::$lang['lang_awstats_january']),
					array ('value'=>'02', 'title'=>metadata::$lang['lang_awstats_february']),
					array ('value'=>'03', 'title'=>metadata::$lang['lang_awstats_march']),
					array ('value'=>'04', 'title'=>metadata::$lang['lang_awstats_april']),
					array ('value'=>'05', 'title'=>metadata::$lang['lang_awstats_may']),
					array ('value'=>'06', 'title'=>metadata::$lang['lang_awstats_june']),
					array ('value'=>'07', 'title'=>metadata::$lang['lang_awstats_july']),
					array ('value'=>'08', 'title'=>metadata::$lang['lang_awstats_august']),
					array ('value'=>'09', 'title'=>metadata::$lang['lang_awstats_september']),
					array ('value'=>'10', 'title'=>metadata::$lang['lang_awstats_october']),
					array ('value'=>'11', 'title'=>metadata::$lang['lang_awstats_november']),
					array ('value'=>'12', 'title'=>metadata::$lang['lang_awstats_december'])
				),
				'filter' => 1
			),
			'year' => array (
				'type' => 'select1',
				'title' => metadata::$lang['lang_awstats_year'],
				'value_list' => $this->get_used_years(),
				'filter' => 1
			),
			'config' => array (
				'type' => 'select1',
				'title' => metadata::$lang['lang_site'],
				'value_list' => $this->get_config_sites(),
				'filter' => 1
			)
		);
		return parent::get_form_fields($mode, $field_prefix, $record, $record_prefix, $fields, $escape);
	}


	/**
	* Возвращает сайты для фильтра, для которых мы можем отобразить статистику
	* @return array
	*/
	
	private function get_config_sites () {
		$fdir=$this->awstats_path.'wwwroot/cgi-bin/';
		$sites=array();
		if (@$dh = opendir($fdir))
		{
			while (($file = readdir($dh)) !== false) {
				if (preg_match('~awstats\\.(.+)\\.conf~',$file,$mathes) && $file!='awstats.model.conf') {
					$sites[]=array('title'=>$mathes[1], 'value'=>$mathes[1]);
				}
			}
			closedir($dh);
		}
		else {
			$this->error.="\n".metadata::$lang['lang_awstats_can_not_open_dir']." $fdir";
			return false;
		}
		return $sites;
	}
	
	/**
	* Возвращает года для фильтра, для которых мы можем отобразить статистику
	* @return array
	*/
	
	private function get_used_years () {
		$yearsu=array();
		$years=array();
		
		$fdir=$this->awstats_path.'static/';
		if (params::$params['default_interface_lang']['value']=='en')
			$fdir=$this->awstats_path.'static_en/';
		

		if (@$dh = opendir($fdir))
		{
			while (($file = readdir($dh)) !== false) {
				if (preg_match('~(\d\d\d\d)~',$file,$matches) && is_dir($fdir.$file)) {
					$yearsu[$matches[1]]=($matches[1]==$_GET['_f_year']?1:0);
				}
			}
			closedir($dh);
		}
		else {
			$this->error.="\n ".metadata::$lang['lang_awstats_can_not_open_dir']." $fdir";
		}
		
		foreach ($yearsu as $k=>$v)
		{
			$years[]=array('title'=>$k, 'value'=>$k, 'selected'=>$v);
		}
		return $years;
	}


	/**
	* Возвращает html для основной части страницы
	* @var string $month  Месяц статистики
	* @var string $year Год статистики
	* @var string $site Сайт
	* @var string $filemode указатель на файл статистики, который нужно вывести
	* @return string
	*/
	
	private function get_data ($month, $year, $site, $filemode='') {
		$last_update='';
		
		$filename = 'awstats.'.$site;
		if ($filemode) 
			$filename.='.'.$filemode;
		$filename.='.html';
		
		$fname=$this->awstats_path.'static/'.$year.sprintf('%02u',$month).'/'.$filename;
		if (params::$params['default_interface_lang']['value']=='en')
			$fname=$this->awstats_path.'static_en/'.$year.sprintf('%02u',$month).'/'.$filename;
		

		if (file_exists($fname)) {
			$sout=file_get_contents($fname);
		
			preg_match('~Последнее обновление.+?(\d\d\D+\d\d\d\d\D+\d\d\D\d\d)~',$sout,$matches);
			$last_update=metadata::$lang['lang_awstats_last_update'].":<br>\n".$matches[1];
		
			$pattern=array(
			/*01*/'~<!DOCTYPE[^>]+>~i',
			/*02*/'~</?html[^>]*>~i',
			/*03*/'~</?head[^>]*>~i',
			/*04*/'~</?meta[^>]*>~i',
			/*05*/'~<title[^>]*>[^<]*</title>~i',
			/*06*/'~</?body[^>]*>~i',
			/*07*/'~td\\s+{([^{}<>]+)}~i',
			/*08*/'~<td( (colspan|width|valign)="[^"]*")?>~i',
			/*09*/'~<a([^>]+)href="awstats\\.'.$site.'\\.([^"]+)\\.html[^"]*"([^>]*)(target=["\'][^"\']+["\'])?([^>]*)>~i',
			/*10*/'~<table>.+?<a([^>]+)href="javascript:parent.window.close\\(\\);">[^<]*</a>.+?</table>~ims',
			/*11*/'~<table>.+<b>Когда.+?</table>~ims',
			/*12*/'~<form[^>]+action=(.)[^\\1]+?awstats.pl[^\\1]+?\\1[^>]+>.+?</form>~ims',
			/*13*/'~<a[^>]+name=.?top.?[^>]*>[^<]+</a>(<br[^>]*>)*~ims',
			/*14*/'~<a[^>]+name=.?menu.?[^>]*>[^<]+</a>(<br[^>]*>)*~ims',
			/*15*/'~\\sa(\\s+?|:(link|visited|hover)\\s+?){[^{}<>]+?}~',
			/*16*/'~\\sb(\\s+?|:(link|visited|hover)\\s+?){[^{}<>]+?}~',
			);
			$replacement=array(
			/*01*/'',
			/*02*/'',
			/*03*/'',
			/*04*/'',
			/*05*/'',
			/*06*/'',
			/*07*/'td.aws_common {\\1}',
			/*08*/'<td class="aws_common" \\1>',
			/*09*/'<a\\1href="index.php?obj=AWSTATS&clearstatus=1&filemode=\\2">',
			/*10*/'',
			/*11*/'',
			/*12*/'',
			/*13*/'',
			/*14*/'',
			/*15*/'',
			/*16*/'',
			);
			$sout=preg_replace($pattern,$replacement,$sout,-1);
		}
		else {
			//$this->error.=metadata::$lang['lang_awstats_nostats']; //.'.<br><!--Не найден файл: '.$fname.' -->';
			$tpl = new smarty_ee( metadata::$lang );
			$tpl->assign('msg', metadata::$lang['lang_awstats_nostats']);
			$info_block = $tpl->fetch($this->tpl_dir."core/object/html_warning.tpl");
			return $info_block;
		}
		
		if ( params::$params["encoding"]["value"] != "windows-1251" )
			$sout = iconv( "windows-1251", params::$params["encoding"]["value"], $sout );
		
		return $sout;
	}
	
}
?>