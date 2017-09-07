<?php
class medialib_explorer extends DataStore{
	protected $url=null;
	protected $post=null;
	
	function __construct($url, $post=null){
		$this->url=$url;
		$this->post=$post;		
	}
	
	static $encoding='utf-8';
	static $href='?url=';
	
	function getHTML(){		
		if ($this->http_code!=200) return null;		
		if ($this->getContentType()!='text/html') return null;
		
		$charset=$this->getCharset();
		
		if ($charset&&$charset!=self::$encoding) $html=iconv($charset, self::$encoding, $this->data);
		else $html=$this->data;
		$url=parse_url($this->url);
		
		$html=preg_replace('@<script(.*?)</script>@is','',$html);
		$html=preg_replace('/src=(.*?)([\s|>])/ie', 'medialib_explorer::patchSrc(\'\\1\',\''.$url['scheme'].'\',\''.$url['host'].'\',\''.$url['path'].'\').\'\\2\'',$html);
		$html=preg_replace('/href=(.*?)([>|\s])/ie','medialib_explorer::patchHref(\'\\1\',\''.$url['scheme'].'\',\''.$url['host'].'\',\''.$url['path'].'\').\'\\2\'',$html);
		
		return $html;
	}
	
	protected static function patchSrc($src, $scheme, $host, $path){		
		$src=trim(stripslashes($src),'"');
		if (preg_match('/^(\w+):\/\//', $src)) return 'src="'.$src.'" ';
		elseif (substr($src,0,1)=='/') return 'src="'.$scheme.'://'.$host.$src.'" ';
		return 'src="'.$scheme.'://'.$host.$path.$src.'" ';	
	}
	
	protected static function patchHref($src, $scheme, $host, $path){
		
		$src=trim(stripslashes($src),'"\'');
		if (preg_match('/^(\w+):\/\//', $src)) $href=$src;
		elseif (substr($src,0,1)=='/') $href=$scheme.'://'.$host.$src;
		else $href=$scheme.'://'.$host.$path.$src;		
		return (substr($href,-4,4)=='.css')?'href="'.$href.'" ':'href="'.self::$href.rawurldecode($href).'" ';
	}
	
	function getContentType(){
		if (preg_match('/(.*?); charset=(.*)$/',$this->content_type,$m)){
			return $m[1];	
		}		
		
		return $this->content_type;
		
	}
	
	function getCharset(){
		if (!preg_match('/(.*?); charset=(.*)$/',$this->content_type,$m)){
			return null;
		}
		return $m[2];		
	}
	
	public $data=null;
	
	function loadData(){
		$c= curl_init($this->url);
		if ($this->post){
			curl_setopt($c, CURLOPT_POST,true);
			curl_setopt($c, CURLOPT_POSTFIELDS,urlencode($this->post));
		}
		curl_setopt($c, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($c, CURLOPT_RETURNTRANSFER,1);
		
		$this->data=curl_exec($c);		
		$this->Info=curl_getinfo($c);		
	}
}
?>