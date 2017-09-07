<?php
require_once params::$params['common_data_server']['value']."lib/lib_abstract.php";
require_once params::$params['common_data_server']['value']."lib/filesystem.php";
/**
 * Голосование
 */
class rating {

    static $table = 'RATING';

    /**
     * Проголосовать
     * @param $te_object_system_name string
     * @param $object_id integer
     * @param $rating string
     * @return integer|null
     */
    public static function setRating( $te_object_system_name, $object_id, $rating )
    {
		
        $object_id = intval( $object_id );
        if ( !$object_id ) {
            return null;
        }

        $rating = intval($rating);
        if ( !$rating ) {
            return null;
        }

        /*if ( !$te_object = rbcc5_te_object::loadBySystemName( $te_object_system_name) ) {
            return null;
        }*/
		$te_object_id = db::sql_select("select TE_OBJECT_ID from TE_OBJECT where SYSTEM_NAME = :te_object_system_name", array('te_object_system_name'=>$te_object_system_name));
		if(!count($te_object_id)){
			return null;
		}
		
        if (self::checkCookieExists( $te_object_system_name, $object_id )) {
            //return null;
        }
		
		$te_object_id = $te_object_id[0]['TE_OBJECT_ID'];
		
		$inf_blocks = db::sql_select("select * from INF_BLOCK where TE_OBJECT_ID = :te_object_id", array("te_object_id"=>$te_object_id));
		foreach($inf_blocks as $inf_block){
			filesystem::rm_r(params::$params["common_data_server"]["value"]."block_cache/block{$inf_block["INF_BLOCK_ID"]}");
		}
		
		if(self::thisIpAlreadyVote($te_object_system_name, $object_id)){
			return null;
		}
		
		$real_ip = self::getRealIp();
		
		$ins_data = array('TE_OBJECT_ID'=>$te_object_id, 'OBJECT_ID'=>$object_id, 'RATING'=>$rating, 'DATE'=>date("YmdHis"), 'IP'=>$real_ip);
		db::insert_record("RATING", $ins_data);
		if($te_object_system_name == "CATALOG_ITEMS"){
			$ins_data2 = array('CATALOG_ITEMS_ID'=>$object_id, 'VOTE'=>$rating, 'VOTE_DATE_TIME'=>date("YmdHis"), 'VOTE_IP'=>$real_ip);
			db::insert_record("CATALOG_VOTES", $ins_data2);
		}
		
		$rating_data = self::getRating( $te_object_system_name, $object_id );
		//print_r($rating_data);
		/*if($rating_data){
			$upd_data = array('RATING'=>$rating_data['AVG_RATING'], 'RATING_COUNT'=>$rating_data['COUNT']);
			$where_upd = array($te_object_system_name."_ID"=>$object_id);
			db::update_record($te_object_system_name, $upd_data, array(), $where_upd);
		}*/
		

        /*$sel = rbcc5::select(self::$table);
        $rating_row_id = $sel->Insert(array(
            'TE_OBJECT_ID' => $te_object -> getId(),
            'OBJECT_ID' => $object_id,
            'RATING' => $rating,
            'IP' => request::getIP(),
            'DATE' => date('YmdHis')
        ));*/

        self::setCookie( $te_object_system_name, $object_id, $rating );

        return $rating_row_id;
    }

    /**
     * Получить рейтинг
     * @param $te_object_system_name string
     * @param $object_id integer
     */
    public static function getRating( $te_object_system_name, $object_id )
    {
         $object_id = intval( $object_id );
        if ( !$object_id ) {
            return null;
        }

        
		$te_object_id = db::sql_select("select TE_OBJECT_ID from TE_OBJECT where SYSTEM_NAME = :te_object_system_name", array('te_object_system_name'=>$te_object_system_name));
		if(!count($te_object_id)){
			return null;
		}
		$te_object_id = $te_object_id[0]['TE_OBJECT_ID'];

        $result = db::sql_select( "SELECT COUNT(*) as COUNT, AVG(RATING) as AVG_RATING FROM RATING WHERE TE_OBJECT_ID = :te_object_id AND OBJECT_ID = :object_id ",
            array('te_object_id' => $te_object_id, 'object_id' => $object_id)
        );
		
        if ($result[0]['AVG_RATING']) {
            $result[0]['AVG_RATING'] = round($result[0]['AVG_RATING'], 2);
        }
		
        return $result[0];
    }

    /**
     * Сохранить голосование в cookie
     * @param $te_object_system_name string
     * @param $object_id integer
     * @param $rating string
     */
    public static function setCookie( $te_object_system_name, $object_id, $rating )
    {
        $rating_data = array();

        if (isset($_COOKIE[self::getCookieName( $te_object_system_name )])) {
            $rating_data = unserialize(base64_decode($_COOKIE[self::getCookieName( $te_object_system_name )]));
        }

        $rating_data[$object_id] = $rating;

        setcookie( self::getCookieName( $te_object_system_name ), base64_encode(serialize($rating_data)), time()+(60*60*24*365), '/');
    }

    /**
     * Получить голосование из cookie
     * @param $te_object_system_name string
     * @return array|null
     */
    public static function getCookie( $te_object_system_name )
    {
        if (isset($_COOKIE[self::getCookieName( $te_object_system_name )])) {
            return unserialize(base64_decode($_COOKIE[self::getCookieName( $te_object_system_name )]));
        }

        return null;
    }

    /**
     * Получить голосование по объекту из cookie
     * @param $te_object_system_name string
     * @param $object_id integer
     * @return string|null
     */
    public static function getCookieByObjectId( $te_object_system_name, $object_id )
    {
        if ( $rating_data = self::getCookie( $te_object_system_name )) {
            if (isset( $rating_data[$object_id]) ) {
                return $rating_data[$object_id];
            }
        }

        return null;
    }

    /**
     * Провереить cookie
     * @param $te_object_system_name string
     * @param $object_id integer
     * @return bool
     */
    public static function checkCookieExists( $te_object_system_name, $object_id )
    {
        
		if(self::thisIpAlreadyVote($te_object_system_name, $object_id)){
			return true;
		}
		if ( self::getCookieByObjectId( $te_object_system_name, $object_id )) {
            //return true;
        }

        return false;
    }
	
	public static function checkVoted( $te_object_system_name, $object_id )
    {
		return false;
	}

    /**
     * Получить имя переменной в cookie для системного объекта
     * @param $te_object_system_name string
     * @return string
     */
    public static function getCookieName( $te_object_system_name )
    {
        return strtolower($te_object_system_name) . '_rating';
    }
	
	/**
	* Получить реальный IP клиента
	*/
	public static function getRealIp(){
		$ret = $_SERVER['REMOTE_ADDR'];
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']){
			$ret = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		if(isset($_SERVER['HTTP_X_REAL_IP']) && $_SERVER['HTTP_X_REAL_IP']){
			$ret = $_SERVER['HTTP_X_REAL_IP'];
		}
		return $ret;
	}
	
	/**
	* Проверка: голосовал ли этот юзер
	*/
	public static function thisIpAlreadyVote($te_object_system_name, $object_id){
		$te_object_id = db::sql_select("select TE_OBJECT_ID from TE_OBJECT where SYSTEM_NAME = :te_object_system_name", array('te_object_system_name'=>$te_object_system_name));
		
		if(!count($te_object_id)){
			return false;
		}
		
		$te_object_id = $te_object_id[0]['TE_OBJECT_ID'];
		
		$real_ip = self::getRealIp();
		$sql = "select * from RATING where TE_OBJECT_ID = :te_object_id and OBJECT_ID = :object_id and IP = :ip";
		$binds = array('te_object_id'=>$te_object_id, 'object_id'=>$object_id, 'ip'=>$real_ip);
		/*print_r("<pre>");
		print_r($sql);
		print_r("</pre>");
		print_r("<pre>");
		print_r($binds);
		print_r("</pre>");*/
		$exists = db::sql_select($sql, $binds);
		if(count($exists)){
			return true;
		}
		return false;
	}
}