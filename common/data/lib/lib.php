<?php
/**
 * Класс с разными полезными статическими методами
 *
 * @package    RBC_Contents_5_0
 * @subpackage lib
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class lib extends lib_abstract {
	/**
	 * Возвращает новое Request URI, собранное из $_SERVER["SCRIPT_NAME"], $_GET и $params
	 *
	 * Метод http_build_query() применяет url-кодирование к ключам и значениям параметров, чтобы избежать XSS, потому что эта функция применяется для сбора ссылок перед выводом их в браузер.
	 * Параметры, которые не содержат значений (пустая строка), не попадут в собираемую строку за ненадобностью, то есть, чтобы удалить параметр нужно передать его в $params с пустым значением.
	 * Если нет ни одного параметра, то возвращаемая строка будет все равно дополнена вопросительным знаком
	 *
	 * @param array $params			перечень новых параметров. Формат такой же как у $_GET
	 * @param string $script_name	имя скрипта, если необходимо, чтобы он отличался от текущего
	 * @return string
	 */
	public static function make_request_uri($params, $script_name = ''){
		$request_uri = explode( '&', http_build_query( $params + $_GET ) );
		foreach( $request_uri as $request_key => $request_value )
		{
			list( $key, $value ) = explode( '=', $request_value );
			if ( $value === '' ) unset( $request_uri[$request_key] );
		}
		return ( $script_name ? $script_name : $_SERVER['SCRIPT_NAME'] ) . '?' . join( '&', $request_uri );
	}

	/**
	 * Функция, возвращающая массив, переиндексированный по значениям указанных ключей
	 *
	 * Если исходный массив был, например, $arr=array(array("id"=>5, "name"=>"some name"), array("id"=>8, "name"=>"another name")),
	 * то вызов lib::array_reindex($arr, "id") вернет
	 * array(5=>array("id"=>5, "name"=>"some name"), 8=>array("id"=>8, "name"=>"another name"))
	 *
	 * Применение: формирования справочников из выборок, чтобы не ходить лишний раз за данными в БД и не бегать непроизводительно по циклам
	 *
	 * Если не указано ни одного ключа, то переиндексирует по значениям, то есть из, например, array(0=>"value1", 1=>"value2") получится array("value1"=>"value1", "value2"=>"value2")
	 *
	 * @param array $array	исходный массив
	 * @param string $key1	первый ключ (если надо)
	 * @param string $key2	второй ключ (если надо)
	 * @param string $key3	третий ключ (если надо)
	 * @param string $key4	четвертый ключ (если надо)
	 * @return array
	 */
	public static function array_reindex($array, $key1="", $key2="", $key3="", $key4=""){
		$reverted_array=array();
		if(is_array($array)){
			foreach($array as $item){
				if(!$key1){ // Если перестраиваем по значению
					$reverted_array[$item]=$item;
				}elseif(!$key2){ // Если перестраиваем по одному ключу
					$reverted_array[$item[$key1]]=$item;
				}elseif(!$key3){ // Если перестраиваем по двум ключам
					$reverted_array[$item[$key1]][$item[$key2]]=$item;
				}elseif(!$key4){ // Если перестраиваем по трем ключам
					$reverted_array[$item[$key1]][$item[$key2]][$item[$key3]]=$item;
				}
				else{ // Если перестраиваем по четырем ключам
					$reverted_array[$item[$key1]][$item[$key2]][$item[$key3]][$item[$key4]]=$item;
				}
			}
		}
		return $reverted_array;
	}

	/**
	 * Функция, возвращающая массив, сгруппированный по указанному ключу
	 *
	 * Если исходный массив был, например, $arr=array(array("id"=>5, "parent_id"=>0), array("id"=>8, "parent_id"=>5), array("id"=>18, "parent_id"=>5)),
	 * то вызов lib::array_group($arr, "parent_id") вернет
	 * array(0=>array(array("id"=>5, "parent_id"=>0)), 5=>array(array("id"=>8, "parent_id"=>5), array("id"=>18, "parent_id"=>5)))
	 *
	 * Применение: сбор массива для рекурсивного обхода детей или получение групп записей по значениям внешнего ключа
	 *
	 * @param array $array	исходный массив
	 * @param string $key	ключ, по которому нужно сгруппировать записи
	 * @return array
	 */
	public static function array_group($array, $key){
		$grouped_array=array();
		if(is_array($array)){
			foreach($array as $item){
				$grouped_array[$item[$key]][]=$item;
			}
		}
		return $grouped_array;
	}

	/**
	 * Формирование заготовки для кляузы IN (строка с переченем идентификаторов через запятую)
	 *
	 * Если указан $key, то $array считается списком записей и идентификаторы собираются из поля с ключом $key. 
	 * Если $key не указан, то $array трактуектся как array("key1"=>1, "key1"=>2) и заготовка собирается из значений элементов массива.
	 *
	 * @param array $array			записи или массив из которого надо собрать заготовку
	 * @param string $key			название поля идентификатора
	 * @param boolean $quote		обрамлять значения апострофами и экранировать против SQL-инъекций (то есть трактовать как строку)
	 * @return string
	 */
	public static function array_make_in($array, $key="", $quote=false){
		$in="0";
		$ids=array();
		
		if(is_array($array)){
			foreach($array as $record){
				if($quote){
					$id=($key ? db::db_quote($record[$key]) : db::db_quote($record));
				}else{
					$id=($key ? intval($record[$key]) : intval($record));
				}
				if($id){
					$ids[]=$id;
				}
			}
			if(!empty($ids)){
				$in=join(", ", $ids);
			}
		}	
		return $in;
	}

	/**
	 * Функция помещает записи, полученные функцией sql_select, в базу
	 *
	 * Главное применение - клонирование записей таблицы с внесением изменений в некоторые из полей.
	 * Параметры $forced_fields, $datatypes и $autoinc_fields являются опциональными и могут быть опущены. 
	 *
	 * @param string $table			название таблицы;
	 * @param array $rows			записи, которые необходимо поместить в таблицу;
	 * @param array $forced_fields	обновленные значения полей, т.е. те значения, которые должны быть помещены в базу вместо тех, что находятся в записях. Формат параметра - array("field_name1"=>"value1", "field_name2"=>"value2",…);
	 * @param array $datatypes		типы данных полей. Формат параметра - array ("field_name1"=>"type1", "field_name2"=>"type2",…);
	 * @param array $autoinc_fields	поля, которые не должны помещаться в базу, например, автоинкрементные. Формат параметра - array ("field_name1"=>1, "field_name2"=>1,…).
	 * @todo сделать поддержку типов данных, которые пока никак не прикладываются
	 * @todo не будет корректно работать, если нужно работать не через db, а через db_access напрямую; может быть стоит добавить еще один необязательный параметр, в котором может передаваться объект db_access
	 */
	public static function inserter($table, $rows, $forced_fields="", $datatypes="", $autoinc_fields=""){
		foreach($rows as $row){
			$prepared_row=array();
			foreach($row as $k=>$v){
				if($autoinc_fields[$k]!=1){
					if(isset($forced_fields[$k])){
						$value=$forced_fields[$k];
					}else{
						$value=$v;
					}
					$prepared_row[$k]=$value;
				}
			}
			db::insert_record($table, $prepared_row);
		}
	}

	/**
	 * Проверка существования записи
	 *
	 * ВНИМАНИЕ: Безопаснее использовать для этой цели {@link primary_key::is_record_exists()}, а данный
	 * метод стоит применять только в том случае если:<br>
	 * 1. Есть абсолютная уверенность, что состав первичного ключа проверяемой таблицы не изменится<br>
	 * 2. Потенциально потребуется большое количество проверок, так что инстанцирование в цикле объекта проверяемой таблицы станет расходовать большие ресурсы
	 *
	 * @param string $table				название таблицы
	 * @param array $pk					первичный ключ проверяемой записи
	 * @param boolean $throw_exception	бросать исключение в том случае, если проверка неуспешна
	 * @return boolean
	 */
	public static function is_record_exists($table, $pk, $throw_exception=false){
		foreach($pk as $key=>$value){
			$where[]="{$key}=:{$key}";
		}
		$where=join(" AND ", $where);
		$counter=db::sql_select("SELECT COUNT(*) AS COUNTER FROM {$table} WHERE {$where}", $pk);
		if($counter[0]["COUNTER"]>0){
			return true;
		}elseif($throw_exception){
			throw new Exception(metadata::$lang["lang_record_not_found"]);
		}else{
			return false;
		}
	}

	/**
	 * Постраничная навигация
	 *
	 * Помимо страниц формирует ссылки перехода на первую и последнюю страницы, а также на следующую и предыдущую страницы.
	 * Не проверяет валидность своих параметров, но проверяет и квотит то, что берет из $_GET. Первая страница имеет номер 1 (один).
	 * Использует {@link lib::make_request_uri()}
	 *
	 * @param int $rows_per_page	сколько выводить записей на одной странице
	 * @param int $count			общее количество записей
	 * @param string $var_name		название переменной в запросе, которая содержит номер страницы
	 * @param string $template		шаблон
	 * @param int $nav_pages		параметр размаха листалки. Количество страниц до и после текущей (включая ее) будет равняться этому параметру. Дальнейшие страницы будут обрезаться
	 * @param int $confirm_change	если true, то листалка будет проверяет, изменились ли данные в формe
	 * @return string
	 * @todo не перенесен из старой листалки параметр $excludeArr. Надо пособирать информацию - нужен ли он.
	 */
	public static function page_navigation($rows_per_page, $count, $var_name, $template, $nav_pages=5, $confirm_change=false){
		// Определяемся с текущей страницей листалки, а также сразу возвращаем пустую строку, если все умещается на одной странице
		$page=((int)$_GET[$var_name]>=1 ? (int)$_GET[$var_name] : 1);
		if($page==1 && $rows_per_page>=$count){
			return "";
		}
		// Общее количество страниц
		$pages=ceil($count/$rows_per_page);
		$pages=($pages==0 ? 1 : $pages);
		// Блокируем уход в нереально далекие страницы
		$page=($page>$pages ? $pages : $page);
		// Базовая ссылка, от которой мы будем достраивать рабочие ссылки
		$base_url=lib::make_request_uri(array($var_name=>""))."&{$var_name}=";
		// Центральные страницы листалки
		for($i=$page-$nav_pages+1; $i<$page+$nav_pages; $i++){
			if($i>=1 && ($i<=$pages || $i==$page)){ // Вторая часть в ИЛИ предназначена для выхода за пределы существующих страниц
				$links[]=array("num"=>$i,"url"=>($i!=$page ? $base_url.$i : ""));
			}
		}
		// Левое двоеточие
		if($page-$nav_pages>1){
			array_unshift($links, array("num"=>"..", "url"=>$base_url.($page-$nav_pages)));
		}
		// Самая первая страница - если только она уже не была выведена в $links
		if($page-$nav_pages>0){
			array_unshift($links, array("num"=>1, "url"=>$base_url."1"));
		}
		// Правое двоеточие
		if($page+$nav_pages<$pages){
			$links[]=array("num"=>"..", "url"=>$base_url.($page+$nav_pages));
		}
		// Самая последняя страница - если только она уже не была выведена  в $links
		if($page+$nav_pages<=$pages){
			$links[]=array("num"=>$pages, "url"=>$base_url.$pages);
		}
		// Ссылки помещаем в шаблон
		$tpl=new smarty_ee();
		$tpl->assign("links", $links);
		// Если страница не первая, то формируем ссылки на первую и предыдущую
		if($page>1){
			$tpl->assign("first_url", $base_url."1");
			$tpl->assign("prev_url", $base_url.($page-1));
		}
		// Если страница не последняя, то формируем ссылки на последнюю и следующую
		if($page<$pages){
			$tpl->assign("last_url", $base_url.$pages);
			$tpl->assign("next_url", $base_url.($page+1));
		}
		
		// Передаем в шаблон информацию о необходимости проверять, изменились ли данные в форме
		if($confirm_change){
			$tpl->assign("confirm_change", 1);
		}
		return $tpl->fetch($template);
	}

	/**
	 * Упаковывает дату из отображаемого формата в ГГГГММДДЧЧММСС
	 *
	 * из ДД.ММ.ГГГГ		- если $mode == 'short'
	 * из ДД.ММ.ГГГГ ЧЧ:ММ	- если $mode == 'long'
	 *
	 * @param string $date		дата в отображаемом формате
	 * @param string $mode		режим работы метода ( 'short' или 'long' )
	 * @return string
	 */
	public static function pack_date( $date = '', $mode = 'short' )
	{
		if ( $mode == 'short' )
			if ( preg_match( '/^(\d\d)\.(\d\d)\.(\d\d\d\d)$/', $date, $matches ) )
				return $matches[3].$matches[2].$matches[1].'000000';
		if ( $mode == 'long' )
			if ( preg_match( '/^(\d\d)\.(\d\d)\.(\d\d\d\d) (\d\d):(\d\d)$/', $date, $matches ) )
				return $matches[3].$matches[2].$matches[1].$matches[4].$matches[5].'00';
		if ( $mode == 'full' )
			if ( preg_match( '/^(\d\d)\.(\d\d)\.(\d\d\d\d) (\d\d):(\d\d):(\d\d)$/', $date, $matches ) )
				return $matches[3].$matches[2].$matches[1].$matches[4].$matches[5].$matches[6];
		return '';
	}

	/**
	 * Распаковывает дату из формата ГГГГММДДЧЧММСС в отображаемый
	 *
	 * в ДД.ММ.ГГГГ				- если $mode == 'short'
	 * в ДД.ММ.ГГГГ ЧЧ:ММ		- если $mode == 'long'
	 * в ДД.ММ.ГГГГ ЧЧ:ММ:СС	- если $mode == 'full'
	 * в формате RFC 2822		- если $mode == 'rfc'
	 *
	 * @param string $date		дата во внутреннем формате
	 * @param string $mode		режим работы метода ( 'short', 'long', 'full' )
	 * @return string
	 */
	public static function unpack_date( $date = '', $mode = 'short' )
	{
		if ( preg_match( '/^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/', $date, $matches ) )
		{
			if ( $mode == 'short' )
				return $matches[3].'.'.$matches[2].'.'.$matches[1];
			if ( $mode == 'long' )
				return $matches[3].'.'.$matches[2].'.'.$matches[1].' '.$matches[4].':'.$matches[5];
			if ( $mode == 'full' )
				return $matches[3].'.'.$matches[2].'.'.$matches[1].' '.$matches[4].':'.$matches[5].':'.$matches[6];
			if ( $mode == 'rfc' )
				return date( 'r', mktime( $matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1] ) );
		}
		return '';
	}
	
	/**
	 * Метод для отправки сообщения по электронной почте
	 *
	 * @param string $to_address	адрес получателя
	 * @param string $to_name		имя получателя
	 * @param string $from_address	адрес отправителя
	 * @param string $from_name		имя отправителя
	 * @param string $subject		тема сообщения
	 * @param string $message		тело сообщения
	 * @param string $to_encoding	кодировка сообщения
	 * @param string $attach_files	прикрепленные файлы
	 * @return boolean
	 */
	public static function post_mail( $to_address = '', $to_name = '', $from_address = '', $from_name = '', $subject = '', $message = '', $to_encoding = '', $attach_files = array() )
	{
		$email = new vlibMimeMail();
		
		$from_encoding = strtolower( params::$params['encoding']['value'] );
		$to_encoding = strtolower( $to_encoding === '' ? params::$params['encoding']['value'] : $to_encoding );
		
		$email -> to( $to_address, $to_name ? '=?'.$to_encoding.'?B?'.base64_encode( iconv( $from_encoding, $to_encoding, $to_name ) ).'?=' : null );
		$email -> from( $from_address, $from_name ? '=?'.$to_encoding.'?B?'.base64_encode( iconv( $from_encoding, $to_encoding, $from_name ) ).'?=' : null );
		$email -> subject( $subject ? '=?'.$to_encoding.'?B?'.base64_encode( iconv( $from_encoding, $to_encoding, $subject ) ).'?=' : null );
		$email -> body( iconv( $from_encoding, $to_encoding, strip_tags( $message ) ), $to_encoding );
		$email -> htmlBody( iconv( $from_encoding, $to_encoding, $message ), $to_encoding );
		
		foreach ( $attach_files as $file_name )
			$email -> attach( $file_name, 'attachment', 'application/octet-stream' );
		
		return $email -> send();
	}
	
	/**
	* Функция для создания временного каталога
	* @param string $dir Каталог, в котором нужно создать временный
	* @param string $prefix префикс к названию создаваемого каталога
	* @param int $mode Права на создаваемый каталог
	* @return string Название созданного каталога
	*/

	public static function tempdir($dir, $prefix='', $mode=0700) {
		if (substr($dir, -1) != '/') $dir .= '/';

		do {
			$path = $dir.$prefix.mt_rand(0, 9999999);
		} 
		while (!mkdir($path, $mode));
		
		return $path;
	}
	
	/**
	 * array_merge_recursive2()
	 *
	 * Similar to array_merge_recursive but keyed-valued are always overwritten.
	 * Priority goes to the 2nd array.
	 *
	 * @author http://ru.php.net/manual/en/function.array-merge-recursive.php#42663
	 * @param array $paArray1
	 * @param array $paArray2
	 * @return array
	 */
	public static function array_merge_recursive2($paArray1, $paArray2){
		if (!is_array($paArray1) or !is_array($paArray2)){
			return $paArray2;
		}
		foreach ($paArray2 AS $sKey2 => $sValue2){
			$paArray1[$sKey2] = self::array_merge_recursive2(@$paArray1[$sKey2], $sValue2);
		}
		return $paArray1;
	}
	
	/**
	 * Метод строит пути от исходных записей до корневого элемента в иерархических таблицах
	 * 
	 * Оптимизирован с точки зрения работы с БД - количество запросов равно глубине самой глубокой записи
	 * Возвращает два массива:
	 *    1. массив массивов – ключами являются идентификаторы исходных записей, а значениями списки родителей
	 *    2. список всех идентификаторов всех пройденных уровней, не включая исходные записи
	 * 
	 * @param array $records		исходный список записей
	 * @param array $table_name		название таблицы
	 * @param string $primary_field	название поля с первичным ключом
	 * @param string $parent_field	название поля с идентификатором родителя
	 * @param array $ext_pk_fields	список полей - составляющих первичный ключ
	 * @return array
	 */
	public static function get_path( $records, $table_name, $primary_field, $parent_field, $ext_pk_fields = array() )
	{
		$parents = $records;
		$records_by_id = array();
	
		while ( true )
		{
			// Подготавливаем список записей, чьих родителей хотим узнать
			$children = array();
			foreach ( $parents as $record )
			{
				// Убираем из списка корневые разделы - записи, у который родитель равен 0
				if ( $record[$parent_field] != 0 )
					$children[$record[$primary_field]] = $record;
				
				// Сохраняем связь записи и ее родителя для дальнейшего построения пути
				$records_by_id[$record[$primary_field]] = $record[$parent_field];
			}
			
			// Пустой список означает, что мы достигли корневого уровня для всех записей
			if ( count( $children ) == 0 ) break;
			
			// Подготавливаем единый запрос для выборки родителей сразу для всех записей
			$where = $binds = array();
			
			foreach ( $children as $record_id => $record )
			{
				$where_item = array( $primary_field . ' = :' . $primary_field . '_' . $record_id );
				$binds[$primary_field . '_' . $record_id] = $record[$parent_field];
				
				// При необходимости учитываем дополнительные ключевые поля (например, "VERSION", "LANG_ID")
				foreach ( $ext_pk_fields as $field_name )
				{
					$where_item[] = $field_name . ' = :' . $field_name . '_' . $record_id;
					$binds[$field_name . '_' . $record_id] = $record[$field_name];
				}
				
				$where[] = '( ' . join( ' and ', $where_item ) . ' )';
			}
			
			$select = 'select ' . $primary_field . ', ' . $parent_field .
				( count( $ext_pk_fields ) ? ( ', ' . join( ', ', $ext_pk_fields ) ) : '' ) .
				' from ' . $table_name . ' where ' . join( ' or ', $where );
			
			$parents = db::sql_select( $select, $binds );
		}
		
		// На основании собранных данных строим пути до корневого элемента
		$paths = array();
		
		foreach ( $records as $record_index => $record )
		{
			$paths[$record_index] = array();
			
			$page_id = $record[$primary_field];
			
			while( $parent_id = $records_by_id[$page_id] )
				$paths[$record_index][] = $page_id = $parent_id;
			
			// Переворачиваем список родителей - корневой элемент оказывается вначале массива
			$paths[$record_index] = is_null( $parent_id ) ? '' : array_reverse( $paths[$record_index] );
		}
		
		return array( $paths, array_unique( array_values( $records_by_id ) ) );
	}
	
	/**
	 * Метод рекурсивно преобразует в элементах массива специальные символы в HTML сущности
	 * 
	 * Внимание! В отличие от оригинальной функции по умолчанию используется режим ENT_QUOTES
	 * 
	 * @param array $array		исходный массив
	 * @return array
	 */
	public static function array_htmlspecialchars( $array, $quote_style = ENT_QUOTES )
	{
		if ( is_array( $array ) )
			foreach( $array as $key => $value )
				$array[$key] = self::array_htmlspecialchars( $value, $quote_style );
		else
			$array = htmlspecialchars( $array, $quote_style );
		
		return $array;
	}
}
?>