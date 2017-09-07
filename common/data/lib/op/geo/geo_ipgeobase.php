<?php
/**
 * Работа с базой ipgeobase
 * @author atukmanov
 *
 */
class geo_ipgeobase {	
	
	const startip=0;
	const endip=1;
	const city=4;
	const region=5;
	const district=6;
	
	/**
	 * Обновить базу данных
	 * @param $fileName
	 * @return 
	 */
	static function updateDb($fileName, $activeCommit=40){
		$sel=dbselect_util::createTemporaryTable(self::select(), false);
		$ins=dbselect_insert::fromSelect($sel);
		$ins->activeCommit=$activeCommit;
		$fp= fopen($fileName, 'r');
		if (!$fp) throw new Exception('Invalid ipgeobase file');
		
		while ($line=fgets($fp)){
			$arr=preg_split("/\t+/u", iconv('windows-1251', 'utf-8', $line));
			$ins->Insert(array(
				'startip'=>$arr[self::startip],
				'endip'=>$arr[self::endip],
				'city'=>$arr[self::city],
				'region'=>$arr[self::region],
				'district'=>$arr[self::district],				
			));
			
		}
		
		$ins->Commit();
		dbselect_util::commitTemporaryTable($sel);
	}
	
	static function crontab($tmpDir, $url='http://ipgeobase.ru/files/db/Main/db_files.tar.gz'){
		chdir($tmpDir);
		`cd $tmpDir`;
		`wget $url`;
		`tar -xzf db_files.tar.gz`;
		self::updateDb('cidr_ru_master_index.db');
	}
	/**
	 * Выборка:
	 * @return dbSelect
	 */
	static function select(){
		return dbselect::factory(array('startip','endip','city','region','district'),'ipgeobase');
	}
	
	static $debug=false;
	/**H
	 * Получить регион:
	 * @param $ip
	 * @return array
	 */
	function getRegion($ip){
		$sel=self::select();
		$sel->Where("startip<=INET_ATON('{$ip}')");
		$sel->OrderBy('startip','DESC');	
		$sel->Limit(0,1);	
		if (self::$debug) str::print_r($sel->getSql());		
		if ($ret=$sel->selectObject()){
			return array($ret['city'],$ret['region'],$ret['district']);
		}
		return null;
	}
}
?>