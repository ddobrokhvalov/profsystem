<?php
dbselect_exception::$Mode=1;//Debug it
dbselect_exception::$PhpMyAdminUrl=(PHP_OS=='WINNT')?dbselect_exception::PhpMyAdmin_Localhost:'https://172.16.9.47/039vJgG5r-myadm/';
//Better use for server:
//dbselect_exception::$PhpMyAdminUrl='https://'.$_SERVER['SERVER_NAME'].'/phpMyAdmin/';

class dbselect_exception extends Exception {
	static $Mode=0;
	
	const ModeLog=0;
	const ModeDebug=1;
		
	/**
	 * sql ������
	 *
	 * @var string
	 */
	var $sql;
	/**
	 * DB_Error
	 *
	 * @var unknown_type
	 */
	var $dbError;
	/**
	 * ������ �������:
	 * 
	 * @var dbselect
	 */
	var $dbSelect;
	
	function __construct($sql, $dbError){
		$this->sql=$sql;
		$this->dbError=($dbError)?$dbError:mysql_error();
		$this->dbSelect=$dbSelect;		
		if (self::$Mode==self::ModeDebug){$this->printMessage();}		
		parent::__construct('database error');	
	}
		
	static $PhpMyAdminUrl='';
	const PhpMyAdmin_Localhost='http://localhost/tools/phpMyAdmin/';
	const PhpMyAdmin_Server='https://172.17.9.47/039vJgG5r-myadm/';
	
	function printMessage(){
		$Tables=array();				
		if (preg_match_all('/\s`(\w+?)`\s/is', $this->sql, $m)){
			if ($count=count($m[1])){		
				for ($i=0;$i<$count;$i++){
					$obj=&$m[1][$i];
					if (!in_array($obj, $Tables)) $Tables[]=$obj;
				}
			}
		}
				
		?><div class="debugMessage" style="border: 2px solid red; margin:10px 0px 0px 0px; padding:5px;">	
		<div><?=$this->sql;?></div>		
		<div><?=$this->dbError;?></div>	
		<div id="dbselect_exception_UserInfo" style="display: block;"><?=$this->dbError;?></div>
		<?
			if ($count=count($Tables)){
				
		?><div style="font-size:80%;margin-top:4px;border-top:1px solid #CCCCCC;">Tables in use:<? 
			for ($i=0;$i<$count;$i++){
				$obj=&$Tables[$i];
				?> <a href="<?=self::$PhpMyAdminUrl;?>tbl_properties_structure.php?lang=ru-win1251&server=1&collation_connection=cp1251_general_ci&db=<?=dbselect::getDBProvider()->_db;?>&table=<?=$obj;?>" target="_blank"><?=$obj;?></a><?				
			}
		?></div><?
			}
		?>
		</div><?
	}
}
?>