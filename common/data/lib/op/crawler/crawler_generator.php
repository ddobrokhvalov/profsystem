<?php
/**
 * Генератор конфига для паука
 * @author atukmanov
 *
 */
class crawler_generator {
	
	var $html=null;
	
	function __construct($html){
		$this->html=$html;
	}
	/**
	 * Получить выражение:
	 * @param $contentMap
	 * @return string
	 */
	function getPreg($markers){
		$markersPos=array();
		$start=strlen($html);
		$end=0;
		foreach ($markers as $k=>$v){
			if (!$v) continue;
			
			$preg=$v;
			$preg=str_replace(
				array('[',']','(',')',':','-'),
				array('\[','\]','\(','\)','\:','-'),
				$preg
			);
						
			$preg=str_replace(array('&nbsp;',' '),array('\s*(&nbsp;)*','\s*(&nbsp;)*'),$preg);
			//str::print_r($preg);
			if (preg_match('@'.$preg.'@uis', $this->html, $m, PREG_OFFSET_CAPTURE)){
				//str::print_r('match',$m[0]);
				str::print_r($k, $m[0]);		
				$start=min($start, $m[0][1]);
				$end=max($end, $m[0][1]+strlen($m[0][0]));		
			}
			else {
				throw new Exception('Invalid marker: '.$k);
			}
		}		
		//ищем ближайшие теги:		
		$start=strrpos(substr($html,0,$start),'<');
		str::print_r($end);
		$end=strpos($html,'>',$end+1);
		$pattern=substr($html, $start, $end+1-$start);
		
		foreach ($markers as $k=>$v){
			$preg=preg_replace('/\s+/','\s+',$v);
			$replace=($k[0]=='_')?'':$k;
			$pattern=preg_replace('@'.$preg.'@is', '{$'.$k.'}',$pattern);
		}
		str::print_r('pattern',$pattern);
		/**
		 * Вычищаем title, alt:
		 */
		$pattern=preg_replace('@title="(.*?)"@','title="{$}"',$pattern);
		$pattern=preg_replace('@title=\'(.*?)\'@','title=\'{$}\'',$pattern);
		$pattern=preg_replace('@alt="(.*?)"@','alt="{$}"',$pattern);
		$pattern=preg_replace('@alt=\'(.*?)\'@','alt=\'{$}\'',$pattern);
		
		return $pattern;
	}
}
?>