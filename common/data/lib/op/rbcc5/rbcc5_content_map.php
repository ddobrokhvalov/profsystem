<?php
class rbcc5_content_map {
	static function select(){
		$a= func_get_args();
		return dbselect::factory(array('CONTENT_ID','INF_BLOCK_ID','IS_MAIN'),'CONTENT_MAP',null,$a);
	}
	/**
	 * Выбрать объекты блока в соответсвии с окружением:
	 * @param dbSelect $sel
	 * @param array $env
	 * @return dbSelect
	 */
	static function mapEnv($sel, $env){
		$sel->Where('VERSION',eq,$env['version']);
		$sel->Where('LANG_ID',eq,$env['lang_id']);
		$sel->Join(self::select('INF_BLOCK_ID',$env['block_id']),$sel->primary_key,'CONTENT_ID','CONTENT_MAP','INNER');
		return $sel;
	}
}
?>