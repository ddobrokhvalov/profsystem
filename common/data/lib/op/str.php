<?php
setlocale(LC_ALL, "ru_RU.CP1251");

define('str_date_format_digit_ru','d.m.Y H:i');
define('str_date_format_word_ru','d ru-rp_M Y H:i');
if (!defined('date_std')) define('date_std','ru-todaywithweek_d+ H:i');
/**
 * Basic string functions:
 */
class str{
	/**
	 * Прикрепить к строке $To непустую строку $What через разделитель $Implode (e.c. implode без таскания массива)
	 *
	 * @param string $To
	 * @param string $What
	 * @param string $Implode
	 * 
	 * $str='';
	 * str::Add($str, "name='a'", ' AND ');// name='a'
	 * str::Add($str, "name='b'", ' AND ');// name='a' AND name='b'
	 */
	static static function Add(&$To, $What, $Implode){
		if (strlen($What)){
			if (strlen($To)) $To.=$Implode;
			$To.=$What;			
		}
	}
	
	/**
	 * Транслитерация:
	 *
	 * @var string $string
	 * @return string
	 */
	static function Translate($string){
		 	//$string=str::Lower($string);	
			    $tr = array(
        "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
        "Д"=>"D","Е"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
        "Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
        "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
        "У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
        "Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"YI","Ь"=>"",
        "Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b",
        "в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
        "з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
        "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
        "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
        "ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
        "ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya","ё"=>"yo","Ё"=>"Yo"
    );
    return strtr($string,$tr);
		
			$LettersFrom = "абвгдезиклмнопрстуфхэыйАБВГДЕЗИКЛМНОПРСТУФХЭЫЙЪЬъь";				
			$LettersTo   = "abvgdeziklmnoprstufheyyABVGDEZIKLMNOPRSTUFHEYY''''";
						
		
			$BiLetters = array( 
			"ж" => "zh", "ц"=>"ts", "ч" => "ch", 
			"ш" => "sh", "щ" => "sch", "ю" => "yu", "я" => "ya", "ё"=>"yo",
			"Ж" => "ZH", "Ц"=>"TS", "Ч" => "CH", 
			"Ш" => "SH", "Щ" => "SCH", "Ю" => "YU", "Я" => "YA", "Ё"=>"YO",
			);
				
			
			for ($i=0; $i<strlen($LettersFrom); $i++){
				
				$string=mb_str_replace(substr($LettersFrom,$i,1),substr($LettersTo,$i,1),$string);
			}
			return str_replace(array_keys($BiLetters),array_values($BiLetters),$string);	
	}
	/**
	 * Привести строку с строгому виду - транслитерация+ обрезка и замена небуквенных символов на -
	 * 
	 * @var $string 	строка
	 * @var $MaxLength	макс длина
	 * 
	 * @return string
	 */
	static function StrongString($string, $MaxLength=0){
			
			
			$string=trim(preg_replace('/-+/','-',preg_replace('/[^a-zA-Z0-9]/', '-', self::Translate($string))));
			

			$string=trim($string,'-');
			if (!strlen($string)) return null;			
			if ($MaxLength) $string=substr($string, 0, $MaxLength);
			return $string;
		}
	/**
	 * К нижнему регистру (выноситься в отдельную ф-ю так как phpшная strtolower иногда косячит)
	 * 
	 * @var string $str		
	 * @return string
	 */
	static function lower($str){
		return mb_strtolower($str,'utf-8');
	}		
	/**
	 * Привести к виду для вывода в форме:
	 */
	static function formString($str){
		return str_replace(array('<','>','"'), array('&lt;','&gt;','&quot;'), $str);						
	}
	/**
	 * Отладочный вывод (с бэкрейсом)
	 */
	static function print_r(){
		$a= func_get_args();
		?><div style="border:1px solid red;"><div style="max-height:150px; overflow: auto; font-size: 10pt;"><?
			foreach ($a as $arg){
				if (is_array($arg)&&!count($arg)) $arg='[]';
		?>
				<div style="border-top: 1px dashed #AAAAAA;"><? highlight_string((is_string($arg)||is_numeric($arg))?$arg:print_r($arg, true));?></div>
		<?
			}			
		?></div>
		<?
			self::_printTrace(debug_backtrace());
		?>
		</div><?		
	}
	/**
	 * Вывести цифирку с соотв. суффиксом
	 * 
	 * @param $i 	значение
	 * @param $one	
	 */
	static function ToNum($i, $one, $two, $five, $null=false, $tpl=' $ ...'){
		$i=(int)$i;
		if ($i<=0&&!$null) return false;
		
		
		
		if ($i%10==1&&$i%100!=11){
			$ret=$one;
		}
		elseif (($i%10==2||$i%10==3||$i%10==4)&&$i%100!=12&&$i%100!=13&&$i%100!=14){
			$ret=$two;
		}
		else {
			$ret=$five;
		}
		if (false!==strpos($ret,'$')) return str_replace('$',$i,$ret);
		elseif ($tpl) return str_replace(array('$','...'),array($i, $ret),$tpl);
		else return $ret;
	}
	
	/**
	 * Приводит к сумме
	 *
	 * @param int/string $Summ	сумма
	 * @param string $Delimiter	разделитель
	 * @return unknown
	 */
	static function ToSumm($Summ, $Delimiter=' ', $Double=2){
		if ($Summ==(int)$Summ) $Double=0;
		return number_format($Summ, $Double, null, $Delimiter);
	}
	
	static $Lang=array(
		'm'=>array(
			'ru-ip'=>array('01'=>'январь','02'=>'февраль','03'=>'март','04'=>'апрель','05'=>'май',
			'06'=>'июнь','07'=>'июль','08'=>'август','09'=>'сентябрь','10'=>'октябрь','11'=>'ноябрь','12'=>'декабрь'),
			'ru-rp'=>array('01'=>'января','02'=>'февраля','03'=>'марта','04'=>'апреля','05'=>'мая','06'=>'июня','07'=>'июля','08'=>'августа','09'=>'сентября','10'=>'октября','11'=>'ноября','12'=>'декабря')
		),		
		'w'=>array(
			'ru-ip'=>array('воскресенье','понедельник','вторник','среда','четверг','пятница','суббота'),
			'ru-ipuc'=>array('Воскресенье','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота')
		),	
		'd+'=>array(
			'ru-today'=>array(-1=>'вчера',0=>'сегодня',1=>'завтра','.default'=>'d ru-rp_M'),
			'ru-notoday'=>array(-1=>'вчера',0=>'',1=>'завтра','.default'=>'d ru-rp_M'),
			'ru-todaywithweek'=>array(-1=>'вчера',0=>'сегодня',1=>'завтра','.default'=>'ru-ipuc_W, d ru-rp_M'),
		),				
	);
	/**
	 * Вернуть дату в виде массива элементов (с учетом языка):
	 * @param date $date
	 * @return array
	 */
	static function getDateArray($date){
		
		$ret= array(
			'ts'=>strtotime($date),
		);
		
		list($ret['Y'],$ret['m'],$ret['d'],$ret['w'],$ret['H'],$ret['i'],$ret['s'])=explode('/',date('Y/m/d/w/H/i/s',$ret['ts']));
		foreach (self::$Lang as $key=>$list){
			if (isset($ret[$key])){
				foreach ($list as $format=>$assoc){
					$ret[$key.'_'.str_replace('-','_',$format)]=$assoc[$ret[$key]];
				}
			}
		}
		
		return $ret;
	}
		
	static function getMonthLang($M=null, $Lang=null){			
		static $Month=array();			
		$Month=array(
		'ru-ip'=>array('','01'=>'январь','02'=>'февраль','03'=>'март','04'=>'апрель','05'=>'май','06'=>'июнь','07'=>'июль','08'=>'август','09'=>'сентябрь','10'=>'октябрь','11'=>'ноябрь','12'=>'декабрь'),
		'ru-rp'=>array('','01'=>'января','02'=>'февраля','03'=>'марта','04'=>'апреля','05'=>'мая','06'=>'июня','07'=>'июля','08'=>'августа','09'=>'сентября','10'=>'октября','11'=>'ноября','12'=>'декабря')
		);			
		
		if ($M===null&&$Lang==null) return $Month;
		if (isset($Month[$Lang])){			
			if ($M!==null) return (isset($Month[$Lang][$M]))?$Month[$Lang][$M]:$M;			
			else return $Month[$Lang];
		}
	}
	
	const dateRu='d.m.Y H:i';
	const dateRuMonth='d ru-rp_M Y H:i';
	const dateSql='Y-m-d H:i:s';
	
	
	static function Date($Template, $ts){	
					
		return date(preg_replace('/([a-z]{2})-([a-z]*)_([A-Z]{1})/e','self::getDateLang("$1","$2","$3",$ts)',preg_replace('/([a-z]{2})-([a-z]*)_d\+/e','self::getDayRel("$1","$2",$ts)',$Template)),$ts);		
	}
	
	static function getDateLang($lng, $type, $key, $ts){
		$key=str::lower($key);
		$value=date($key, $ts);
		if (isset(self::$Lang[$key][$lng.'-'.$type][$value])) return self::$Lang[$key][$lng.'-'.$type][$value];
		else return $value;
	}
	
	static function getDayRel($lng, $type, $ts){
		$Ymd=date('Ymd');
		if ($Ymd==date('Ymd',$ts)) $key=0;
		elseif ($Ymd==date('Ymd',$ts+date::Day)) $key=-1;
		elseif ($Ymd==date('Ymd',$ts-date::Day)) $key=1;
		else $key='.default';
		
		return isset(self::$Lang['d+'][$lng.'-'.$type][$key])?self::$Lang['d+'][$lng.'-'.$type][$key]:'';
	}
	
	static function json($data){
		return json_encode(str::to_utf_r($data));
	}
	
	static function to_utf_r($data, $encoding='cp1251'){
		if (is_array($data)||is_object($data)){
			$ret=array();
			foreach ($data as $k=>$v) $ret[$k]=str::to_utf_r($v);
			return $ret;	
		}
		elseif (is_string($data)){
			return iconv('cp1251','utf-8',$data);
		}
		else {
			return $data;		
		}
	}

	static function from_utf_r($data, $encoding='cp1251'){
	if (is_array($data)||is_object($data)){
			$ret=array();
			foreach ($data as $k=>$v) $ret[$k]=str::to_utf_r($v);
			return $ret;	
		}
		elseif (is_string($data)){
			return iconv('utf-8','cp1251',$data);
			
		}
		else {
			return $data;		
		}
	}
	/**
	 * Позволяем разбирает дату из строки $Date согласно формату $DateFormat.
	 * @var $Date		дата
	 * @var $DateFormat	Y-m-d h:i
	 */
	static function ParseDate($Date, $DateFormat='d.m.Y'){		
		/**
		 * Разбираем дату:
		 */
		preg_match_all('/(\w+)/',$DateFormat,$m);		
		$Format=$m[0];
	
		$PregTemplate=array(
			'd'=>'([0-3]{0,1}[0-9]{1})',
			'm'=>'([0-9]{1,2})',
			'Y'=>'([0-9]{4})',
			'h'=>'([0-9]{1,2})',
			'i'=>'([0-9]{1,2})',
			's'=>'([0-9]{1,2})',
		);
		
		$Find[]='.';	$Replace[]='\.';
		$Find[]='-';	$Replace[]='\-';
		
		for ($i=0;$i<count($Format);$i++){
			if (!isset($PregTemplate[$Format[$i]])){
				throw new Exception('Unsupported date format');		
			}
			$Find[]=$Format[$i];
			$Replace[]=$PregTemplate[$Format[$i]];
		}
		
		$DateFormat=str_replace($Find, $Replace, $DateFormat);

		$DateInfo=array();
		if (preg_match('/'.$DateFormat.'/', $Date, $m)){
			for ($i=1; $i<count($m);$i++){
				$DateInfo[$Format[$i-1]]=$m[$i];
			}
		}
		else {
			throw new Exception('Wrong date format');
		}
				
		if (!isset($DateInfo['Y'])) $DateInfo['Y']=date('Y');
		if (!isset($DateInfo['m'])) $DateInfo['m']=date('m');
		if (!isset($DateInfo['h'])) $DateInfo['h']=0;
		if (!isset($DateInfo['i'])) $DateInfo['i']=0;
		if (!isset($DateInfo['s'])) $DateInfo['s']=0;
		
		if (!checkdate($DateInfo['m'], $DateInfo['d'], (isset($DateInfo['Y'])?$DateInfo['Y']:date('Y')))) throw new Exception('Wrong date');
		if ($DateInfo['h']>23||$DateInfo['i']>59||$DateInfo['s']>59) throw new Exception('Wrong date');
		
		return mktime($DateInfo['h'],$DateInfo['i'],$DateInfo['s'],$DateInfo['m'],$DateInfo['d'],$DateInfo['Y']);	
	}
	/**
	 * Вызвать эксэпщн
	 */
	const throwException='throwException';
	/**
	 * Приведение к натуральному числу:
	 * 
	 * @var string|array	$value	значение 		'1','qwertty'
	 * 								массив-> ключ 	array($_REQUEST,$page)
	 * 
	 */
	static function natural($value, $default=0, $key=null){
		if ($key){
			if (isset($value[$key])){
				if ($ret=(int)$value[$key]) return $ret;
			}			
		}
		elseif ($ret=(int)$value){
			return $ret;
		}
		if ($default===str::throwException) throw new Exception('InvalidFormat');
		return $default;
		
	}
	/**
	 * Приведение размера файла в байтах к человекочитаемому виду:
	 * 
	 * @var $size	размер в байтах
	 * @var $glue	разделитель
	 * @var $double	количество десятичных знаков
	 */
	static function filesize($size, $glue=' ', $double=2){
		if ($size<1024) return $size.$glue.'byte';
		elseif($size<1048576) return str::ToSumm($size/1024,'',$double).$glue.'Kb';
		elseif($size<1073741824) return str::ToSumm($size/1048576,'',$double).$glue.'Mb';
		else return str::ToSumm($size/1073741824,'',$double).$glue.'Gb';
	}
	
	const StripTags=1;	
	const nl2br=2;
	const ParseBB=4;
	const ParseLinks=8;
	const RemoveNL=16;
	const Typograf=32;
	/**
	 * Обработка HTML:
	 * 
	 * @var stirn $str
	 * @var string $mode
	 * @var strign $meta	доп. данные
	 * @return string
	 */
	static function HTML($str, $mode=1, $meta=''){
		$str=trim($str);
		if ($mode&self::StripTags){
			$str=preg_replace('/<a(.*?)href="(http|https|mailto):\/\/([a-z0-9\/\?\=\&\%\.#]+)"(.*?)>(.*?)<\/a>/i','\2://\3 (\5)',$str);
			$str=strip_tags($str, $meta);
		}
		if ($mode&self::nl2br&&strlen($str)){
			$lines=explode(PHP_EOL,$str);
			$str='';
			$p=false;
			$cnt=0;
			foreach ($lines as $line){
				if ($line){
					if ($cnt){
						$p=true;
						$str.='</p><p>';
						$cnt=0;
					}
					elseif ($str) $str.='<br/>';
					$str.=$line;
				}
				else $cnt++;
			}
			if ($p) $str='<p>'.$str.'</p>';
		}
		if ($mode&self::RemoveNL){
			$str=str_replace(PHP_EOL,'',$str);
			$str=preg_replace('/(\s+)/',' ',$str);
		}
		if ($mode&self::Typograf){
			$str=preg_replace('/"([а-я].*?)"/','&laquo;\\1&raquo;',str_replace(array('--','---','(c)','(tm)'),array('&mdash;','&ndash;','&copy;','&trade;'),$str));			
		}
		if ($mode&self::ParseLinks) $str=preg_replace('/(?<!")(http|https|mailto):\/\/([a-z0-9\/\?\=\&\%\.#]+)/i','<a href="\1://\2">\1://\2</a>',$str);
		return $str;
	}
	
	/**
	 * Случайная строка: vk90i90ssdprw3aq
	 *
	 * @param int $length
	 * @return string
	 */
	static function randString($length){
		$ret='';
		for ($i=0; $i<$length; $i++){
			$ord=rand(0,34);
			if ($ord<10) $ret.=chr($ord+48);
			else $ret.=chr($ord+87);
		}
		return $ret;
	}
	/**
	 * Обрезать, если длиннее
	 * 
	 * @var string $str строка
	 * @var int	$length длина
	 * @var string $end конец
	 * @var int $delta	разброс
	 * 
	 * Ищет в пределах delta окончание слова или знак припинания- если находит режет по нему и добавляет $end (многоточие)
	 * Если строка короче- возвращает строку целиком
	 * 
	 * @return string
	 */
	static function cut($str, $length, $end='&#133;', $delta=0){
		if (strlen($str)<=$length) return $str;
		if (preg_match('/^(\w+)/',substr($str, $length-$delta,2*$delta),$m)){
			return substr($str,0,$length-$delta).$m[0].$end;
			
		}
		else return substr($str,0,$length).$end;
	}
	/**
	 * Отрезает хвост по разделителю $separator у строки $str
	 * Возвращает массив [голова,хвост]
	 * 
	 * str::tail('asdf.querty.abc','.') -> ['asdf.querty','abc']
	 * 
	 * если разделителя нет полагаем хвост пустым:
	 * 
	 * str::tail('aaaa','/') -> ['aaaa','']
	 *
	 * @param string $str
	 * @param string $separator
	 * 
	 * @return array
	 */
	static function tail($str, $separator){
		if (false!==$pos=strrpos($str, $separator)){
			return array(substr($str,0,$pos),substr($str,$pos+1));
		}
		else {
			return array($str,'');
		}
	}
	/**
	 * Возвращает директория
	 * 
	 * @param $url
	 * @return string
	 */
	static function url($url){
		if (substr($url,0,1)!='/') $url='/'.$url;
		if (strpos($url,'.')!==false) return dirname($url).'/';
		elseif (substr($url,-1,1)!='/') return $url.'/';
		else return $url;
	}		
	
	static function isStrongString($str, $length=50){
		return preg_match('@[a-z][a-z0-9\-]{1,'.$length.'}@', $str);
	}
	/**
	 * Вывести исключение
	 * @param Exception $e
	 */
	static function _printException($e){
		?>
		<div style="border:2px solid black">
		<div style="background: #AAAAAA;"><?=$e->getMessage();?></div>
		<? self::_printTrace($e->getTrace());?>
		</div>
		<?
	}
	
	static $uid=0;
	
	static function _printTrace($traces){
		?><ul style="font-size: 10pt; list-style: none; margin: 0px; padding: 0px; max-height:100px; overflow: auto;"><?			
			$count=count($traces);						
			for ($i=$count-1; $i>=0; $i--){
				$uid=self::$uid++;				
				$trace=$traces[$i];
				
				?><li><b><?
				echo (isset($trace['class']))?$trace['class']:'',
					 (isset($trace['type']))?$trace['type']:'',
				     (isset($trace['function']))?$trace['function']:'';
				?></b>
				<small><?=$trace['file']?> <font color="red"><?=$trace['line'];?></font></small>
				<?
				/**
				<a onclick="document.getElementById('exception<?=$uid;?>').style.display='block';">args</a>
				<div id="exception<?=$uid;?>" style="display: none;"><p style="text-align: right; margin:0px;" onclick="this.parentNode.style.display='none';">close</p><? highlight_string(print_r($trace['args'], true));?></div></li>
				*/
				
			}	
		?></ul><?
	}
}
?>